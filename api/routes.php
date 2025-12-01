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
    // Add new stop
    // Depending on form data, it might be multipart/form-data or JSON.
    // Since we used FormData in JS, it comes as $_POST.

    $day = $_POST['day'] ?? '';
    $city = $_POST['city'] ?? '';
    $name = $_POST['name'] ?? '';
    $address = $_POST['address'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $lat = !empty($_POST['lat']) ? floatval($_POST['lat']) : null;
    $lng = !empty($_POST['lng']) ? floatval($_POST['lng']) : null;

    if (empty($day) || empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Day and Name are required']);
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

    // Optimization Logic (Basic Nearest Neighbor Insertion)
    // If lat/lng are provided, find the closest existing stop and insert after it.
    // If not provided, just append to the end.

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
                // Simple Euclidean distance (good enough for local ordering)
                $dist = sqrt(pow($stop['lat'] - $lat, 2) + pow($stop['lng'] - $lng, 2));
                if ($dist < $min_dist) {
                    $min_dist = $dist;
                    $closest_stop = $stop;
                }
            }

            if ($closest_stop) {
                // Insert after the closest stop
                $target_order = $closest_stop['order_index'] + 1;

                // Shift indices of subsequent stops
                $update_stmt = $pdo->prepare("UPDATE stops SET order_index = order_index + 1 WHERE route_id = :rid AND order_index >= :target");
                $update_stmt->execute(['rid' => $route_id, 'target' => $target_order]);

                $new_order_index = $target_order;
            } else {
                 // Should not happen if count > 0
                 $new_order_index = 1;
            }
        } else {
            // First stop with coords
            $new_order_index = 1;
        }
    } else {
        // Append to end
        $stmt = $pdo->prepare("SELECT MAX(order_index) as max_idx FROM stops WHERE route_id = :rid");
        $stmt->execute(['rid' => $route_id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        $new_order_index = ($res['max_idx'] ?? 0) + 1;
    }

    // Insert new stop
    $stmt = $pdo->prepare("
        INSERT INTO stops (route_id, city, name, address, phone, notes, lat, lng, order_index)
        VALUES (:rid, :city, :name, :addr, :phone, :notes, :lat, :lng, :idx)
    ");

    try {
        $stmt->execute([
            'rid' => $route_id,
            'city' => $city,
            'name' => $name,
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
?>
