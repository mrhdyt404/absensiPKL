<?php
// api/settings.php - Office Location & Radius Settings API
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Anyone logged in can view current location settings
    requireAuth();
    $stmt = $db->query("SELECT * FROM settings_location LIMIT 1");
    $settings = $stmt->fetch();
    sendJsonResponse(['success' => true, 'data' => $settings]);
}

if ($method === 'POST') {
    // Only Admin can update settings
    requireAuth(['admin']);
    
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    $officeName = trim($input['office_name'] ?? 'Kantor Direksi PTPN IV Regional 3');
    $address = trim($input['address'] ?? '');
    $latitude = floatval($input['latitude'] ?? 0.528383);
    $longitude = floatval($input['longitude'] ?? 101.447781);
    $radiusMeters = intval($input['radius_meters'] ?? 200);
    $workStartTime = trim($input['work_start_time'] ?? '07:30:00');
    $workLateTolerance = trim($input['work_late_tolerance'] ?? '08:00:00');
    $workEndTime = trim($input['work_end_time'] ?? '16:30:00');

    if ($radiusMeters < 10 || $radiusMeters > 50000) {
        sendJsonResponse(['success' => false, 'message' => 'Radius harus antara 10 meter sampai 50.000 meter.'], 400);
    }

    $stmt = $db->prepare("
        UPDATE settings_location 
        SET office_name = ?, address = ?, latitude = ?, longitude = ?, radius_meters = ?,
            work_start_time = ?, work_late_tolerance = ?, work_end_time = ?
        WHERE id = 1
    ");
    $stmt->execute([
        $officeName, $address, $latitude, $longitude, $radiusMeters,
        $workStartTime, $workLateTolerance, $workEndTime
    ]);

    sendJsonResponse([
        'success' => true,
        'message' => 'Pengaturan lokasi kantor & radius berhasil disimpan.',
        'data' => [
            'office_name' => $officeName,
            'address' => $address,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'radius_meters' => $radiusMeters,
            'work_start_time' => $workStartTime,
            'work_late_tolerance' => $workLateTolerance,
            'work_end_time' => $workEndTime
        ]
    ]);
}

sendJsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
