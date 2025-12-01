<?php
// api/routes.php
session_start();
require_once '../db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Get stops for a specific day
    $day = $_GET['day'] ?? '';
    if (empty($day)) {
        echo json_encode([]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT s.*
        FROM stops s
        JOIN routes r ON s.route_id = r.id
        WHERE r.day_name = :day
        ORDER BY s.order_index ASC
    ");
    $stmt->execute(['day' => $day]);
    $stops = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($stops);

} elseif ($method === 'POST') {
    // Depending on form data, it might be multipart/form-data or JSON.
    // Since we used FormData in JS, it comes as $_POST.

    // Check if it's an UPDATE or INSERT
    $id = $_POST['id'] ?? null;
    $day = $_POST['day'] ?? '';
    $city = $_POST['city'] ?? '';
    $name = $_POST['name'] ?? '';
    $owner = $_POST['owner'] ?? '';
    $address = $_POST['address'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $order_index = !empty($_POST['order_index']) ? intval($_POST['order_index']) : null;

    // Coords only updated on Insert or explicit edit (not always sent on update)
    $lat = !empty($_POST['lat']) ? floatval($_POST['lat']) : null;
    $lng = !empty($_POST['lng']) ? floatval($_POST['lng']) : null;

    if (empty($day)) {
         echo json_encode(['success' => false, 'message' => 'Day is required']);
         exit;
    }

    // Get Route ID
    $stmt = $pdo->prepare("SELECT id FROM routes WHERE day_name = :day");
    $stmt->execute(['day' => $day]);
    $route = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$route) {
        echo json_encode(['success' => false, 'message' => 'Invalid day']);
        exit;
    }
    $route_id = $route['id'];

    if ($id) {
        // --- UPDATE EXISTING STOP ---
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Name is required']);
            exit;
        }

        // Logic for reordering if order_index changed is complex.
        // For now, we update it directly. User might need to manually fix duplicates if any.
        // Better approach: If order changed, shift others.
        // Let's implement a simple shift if order changed.

        $stmt = $pdo->prepare("SELECT order_index FROM stops WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $old_order = $stmt->fetchColumn();

        if ($old_order != $order_index && $order_index !== null) {
            // If moving down (e.g. 1 -> 3), shift 2,3 down to 1,2? No, shift 2,3 UP to 1,2.
            // If moving up (e.g. 3 -> 1), shift 1,2 DOWN to 2,3.

            if ($order_index > $old_order) {
                // Moving down the list (increasing index)
                // Shift items between old+1 and new index DOWN by 1 (actually decrement index)
                 $pdo->prepare("UPDATE stops SET order_index = order_index - 1 WHERE route_id = :rid AND order_index > :old AND order_index <= :new")
                     ->execute(['rid' => $route_id, 'old' => $old_order, 'new' => $order_index]);
            } else {
                // Moving up the list (decreasing index)
                // Shift items between new and old-1 UP by 1 (actually increment index)
                $pdo->prepare("UPDATE stops SET order_index = order_index + 1 WHERE route_id = :rid AND order_index >= :new AND order_index < :old")
                    ->execute(['rid' => $route_id, 'old' => $old_order, 'new' => $order_index]);
            }
        }

        $sql = "UPDATE stops SET city=:city, name=:name, owner=:owner, address=:addr, phone=:phone, notes=:notes";
        $params = [
            'city' => $city,
            'name' => $name,
            'owner' => $owner,
            'addr' => $address,
            'phone' => $phone,
            'notes' => $notes,
            'id' => $id
        ];

        if ($order_index !== null) {
            $sql .= ", order_index=:idx";
            $params['idx'] = $order_index;
        }

        // If lat/lng provided, update them.
        if ($lat && $lng) {
             $sql .= ", lat=:lat, lng=:lng";
             $params['lat'] = $lat;
             $params['lng'] = $lng;
        }

        $sql .= " WHERE id=:id";

        $stmt = $pdo->prepare($sql);
        try {
            $stmt->execute($params);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }

    } else {
        // --- INSERT NEW STOP ---
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Name is required']);
            exit;
        }

        // Optimization Logic (Basic Nearest Neighbor Insertion)
        $new_order_index = 0;

        if ($lat && $lng) {
            // Fetch existing stops with coordinates
            $stmt = $pdo->prepare("SELECT id, lat, lng, order_index FROM stops WHERE route_id = :rid AND lat IS NOT NULL ORDER BY order_index ASC");
            $stmt->execute(['rid' => $route_id]);
            $existing_stops = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($existing_stops) > 0) {
                $closest_stop = null;
                $min_dist = PHP_FLOAT_MAX;

                foreach ($existing_stops as $stop) {
                    $dist = sqrt(pow($stop['lat'] - $lat, 2) + pow($stop['lng'] - $lng, 2));
                    if ($dist < $min_dist) {
                        $min_dist = $dist;
                        $closest_stop = $stop;
                    }
                }

                if ($closest_stop) {
                    $target_order = $closest_stop['order_index'] + 1;
                    // Shift indices
                    $update_stmt = $pdo->prepare("UPDATE stops SET order_index = order_index + 1 WHERE route_id = :rid AND order_index >= :target");
                    $update_stmt->execute(['rid' => $route_id, 'target' => $target_order]);
                    $new_order_index = $target_order;
                } else {
                     $new_order_index = 1;
                }
            } else {
                $new_order_index = 1;
            }
        } else {
            // Append to end
            $stmt = $pdo->prepare("SELECT MAX(order_index) as max_idx FROM stops WHERE route_id = :rid");
            $stmt->execute(['rid' => $route_id]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            $new_order_index = ($res['max_idx'] ?? 0) + 1;
        }

        $stmt = $pdo->prepare("
            INSERT INTO stops (route_id, city, name, owner, address, phone, notes, lat, lng, order_index)
            VALUES (:rid, :city, :name, :owner, :addr, :phone, :notes, :lat, :lng, :idx)
        ");

        try {
            $stmt->execute([
                'rid' => $route_id,
                'city' => $city,
                'name' => $name,
                'owner' => $owner,
                'addr' => $address,
                'phone' => $phone,
                'notes' => $notes,
                'lat' => $lat,
                'lng' => $lng,
                'idx' => $new_order_index
            ]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
?>
