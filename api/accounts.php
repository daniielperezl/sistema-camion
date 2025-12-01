<?php
// api/accounts.php
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
    // Get account history
    // Order by delivery date desc
    $stmt = $pdo->query("SELECT * FROM daily_accounts ORDER BY delivery_date DESC, created_at DESC LIMIT 50");
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($accounts);

} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    $required = ['planilla_number', 'delivery_date', 'delivery_day', 'total_planilla'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing field: $field"]);
            exit;
        }
    }

    $planilla_number = $data['planilla_number'];
    $delivery_date = $data['delivery_date'];
    $delivery_day = $data['delivery_day'];

    $total_planilla = floatval($data['total_planilla']);
    $total_devoluciones = floatval($data['total_devoluciones'] ?? 0);
    $parciales = floatval($data['parciales'] ?? 0);
    $total_consignado = floatval($data['total_consignado'] ?? 0);
    $total_qr = floatval($data['total_qr'] ?? 0);

    // Server-side calculation to ensure integrity
    $total_consignar = $total_planilla - ($total_devoluciones + $parciales);
    $total_entrega_quala = $total_consignado + $total_qr;
    // Updated Logic: Delivered - Expected. Positive = Surplus (Green), Negative = Deficit (Red)
    $total_descuadre = $total_entrega_quala - $total_consignar;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO daily_accounts
            (planilla_number, delivery_date, delivery_day, total_planilla, total_devoluciones, parciales, total_consignar, total_consignado, total_qr, total_entrega_quala, total_descuadre)
            VALUES
            (:pnum, :ddate, :dday, :tpl, :tdev, :par, :tcons, :tconsigned, :tqr, :teq, :tdesc)
        ");

        $stmt->execute([
            'pnum' => $planilla_number,
            'ddate' => $delivery_date,
            'dday' => $delivery_day,
            'tpl' => $total_planilla,
            'tdev' => $total_devoluciones,
            'par' => $parciales,
            'tcons' => $total_consignar,
            'tconsigned' => $total_consignado,
            'tqr' => $total_qr,
            'teq' => $total_entrega_quala,
            'tdesc' => $total_descuadre
        ]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
