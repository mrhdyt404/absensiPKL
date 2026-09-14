<?php
// api/attendance.php - Attendance & GPS Radius Validation API
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$userId = requireAuth();
$db = getDB();
$role = $_SESSION['role'] ?? 'pkl';
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Helper to save base64 photo
function saveBase64Photo($base64Data, $prefix = 'selfie_') {
    if (empty($base64Data)) return null;
    
    // Check if it's base64 data URL
    if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
        $data = substr($base64Data, strpos($base64Data, ',') + 1);
        $type = strtolower($type[1]); // jpg, png, jpeg, webp
        if (!in_array($type, ['jpg', 'jpeg', 'png', 'webp'])) {
            $type = 'jpg';
        }
        $data = base64_decode($data);
        if ($data === false) {
            return null;
        }
    } else {
        return null;
    }

    $uploadDir = __DIR__ . '/../uploads/selfies/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $filename = $prefix . time() . '_' . bin2hex(random_bytes(6)) . '.' . $type;
    $filePath = $uploadDir . $filename;
    file_put_contents($filePath, $data);
    return 'uploads/selfies/' . $filename;
}

// 1. GET Requests: List or check attendance
if ($method === 'GET') {
    $today = date('Y-m-d');

    // A. Check today status for current PKL
    if ($action === 'today_status') {
        $stmt = $db->prepare("SELECT * FROM attendances WHERE user_id = ? AND attendance_date = ? LIMIT 1");
        $stmt->execute([$userId, $today]);
        $att = $stmt->fetch();

        // Get office settings
        $settings = $db->query("SELECT * FROM settings_location LIMIT 1")->fetch();

        sendJsonResponse([
            'success' => true,
            'today_attendance' => $att,
            'today_date' => $today,
            'current_time' => date('H:i:s'),
            'settings' => $settings
        ]);
    }

    // B. History for current user
    if ($action === 'history') {
        $month = $_GET['month'] ?? date('m');
        $year = $_GET['year'] ?? date('Y');

        $stmt = $db->prepare("
            SELECT * FROM attendances 
            WHERE user_id = ? AND MONTH(attendance_date) = ? AND YEAR(attendance_date) = ?
            ORDER BY attendance_date DESC
        ");
        $stmt->execute([$userId, $month, $year]);
        $history = $stmt->fetchAll();

        // Count stats
        $statStmt = $db->prepare("
            SELECT 
                COUNT(*) as total_records,
                SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir,
                SUM(CASE WHEN status = 'terlambat' THEN 1 ELSE 0 END) as terlambat,
                SUM(CASE WHEN status = 'pulang_cepat' THEN 1 ELSE 0 END) as pulang_cepat,
                SUM(CASE WHEN status = 'izin' THEN 1 ELSE 0 END) as izin,
                SUM(CASE WHEN status = 'sakit' THEN 1 ELSE 0 END) as sakit,
                SUM(CASE WHEN status = 'alpha' THEN 1 ELSE 0 END) as alpha
            FROM attendances 
            WHERE user_id = ? AND MONTH(attendance_date) = ? AND YEAR(attendance_date) = ?
        ");
        $statStmt->execute([$userId, $month, $year]);
        $stats = $statStmt->fetch();

        sendJsonResponse(['success' => true, 'data' => $history, 'stats' => $stats]);
    }

    // C. Admin Monitoring: List all attendances for specific date
    if ($action === 'admin_monitoring') {
        requireAuth(['admin']);
        $date = $_GET['date'] ?? date('Y-m-d');
        $type = $_GET['type'] ?? '';
        $division = $_GET['division'] ?? '';
        $search = $_GET['search'] ?? '';

        $query = "
            SELECT 
                u.id as user_id, u.full_name, u.identifier_no, u.user_type, u.institution, u.major, u.division, u.phone,
                a.id as attendance_id, a.attendance_date, a.time_in, a.time_out, a.lat_in, a.long_in, a.lat_out, a.long_out,
                a.distance_in_meters, a.distance_out_meters, a.photo_in, a.photo_out, a.status, a.notes_in, a.notes_out,
                lp.leave_type, lp.status as leave_status
            FROM users u
            LEFT JOIN attendances a ON u.id = a.user_id AND a.attendance_date = ?
            LEFT JOIN leaves_permits lp ON u.id = lp.user_id AND ? BETWEEN lp.start_date AND lp.end_date AND lp.status = 'approved'
            WHERE u.role = 'pkl' AND u.is_active = 1
        ";
        $params = [$date, $date];

        if (!empty($type)) {
            $query .= " AND u.user_type = ?";
            $params[] = $type;
        }

        if (!empty($division)) {
            $query .= " AND u.division LIKE ?";
            $params[] = "%$division%";
        }

        if (!empty($search)) {
            $query .= " AND (u.full_name LIKE ? OR u.identifier_no LIKE ? OR u.institution LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $query .= " ORDER BY u.full_name ASC";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $list = $stmt->fetchAll();

        // Calculate summary for the day
        $totalPKL = count($list);
        $hadir = 0; $terlambat = 0; $izin = 0; $sakit = 0; $belumAbsen = 0;
        foreach ($list as $row) {
            if ($row['status'] === 'hadir') $hadir++;
            elseif ($row['status'] === 'terlambat') $terlambat++;
            elseif ($row['status'] === 'izin' || $row['leave_type'] === 'izin_pribadi' || $row['leave_type'] === 'izin_akademik') $izin++;
            elseif ($row['status'] === 'sakit' || $row['leave_type'] === 'sakit') $sakit++;
            else $belumAbsen++;
        }

        sendJsonResponse([
            'success' => true,
            'data' => $list,
            'summary' => [
                'date' => $date,
                'total_pkl' => $totalPKL,
                'hadir' => $hadir,
                'terlambat' => $terlambat,
                'izin' => $izin,
                'sakit' => $sakit,
                'belum_absen' => $belumAbsen
            ]
        ]);
    }
}

// 2. POST Requests: Clock In and Clock Out
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $input['action'] ?? $_GET['action'] ?? '';
    $today = date('Y-m-d');
    $currentTime = date('H:i:s');

    // Get location settings
    $settings = $db->query("SELECT * FROM settings_location LIMIT 1")->fetch();
    $officeLat = floatval($settings['latitude']);
    $officeLon = floatval($settings['longitude']);
    $maxRadius = intval($settings['radius_meters']);
    $workStart = $settings['work_start_time'];
    $lateTolerance = $settings['work_late_tolerance'];
    $workEnd = $settings['work_end_time'];

    // A. CLOCK IN (Absen Masuk)
    if ($action === 'clock_in') {
        // Check if user already clocked in today
        $stmt = $db->prepare("SELECT * FROM attendances WHERE user_id = ? AND attendance_date = ? LIMIT 1");
        $stmt->execute([$userId, $today]);
        $existing = $stmt->fetch();

        if ($existing && !empty($existing['time_in'])) {
            sendJsonResponse(['success' => false, 'message' => 'Anda sudah melakukan absen masuk hari ini pada pukul ' . substr($existing['time_in'], 0, 5) . ' WIB.'], 400);
        }

        $lat = floatval($input['latitude'] ?? 0);
        $lon = floatval($input['longitude'] ?? 0);
        $notes = trim($input['notes'] ?? '');
        $photoBase64 = $input['photo'] ?? '';

        if ($lat == 0 || $lon == 0) {
            sendJsonResponse(['success' => false, 'message' => 'Gagal membaca koordinat GPS perangkat. Pastikan izin lokasi GPS aktif.'], 400);
        }

        if (empty($photoBase64)) {
            sendJsonResponse(['success' => false, 'message' => 'Foto selfie wajib diambil saat melakukan presensi masuk.'], 400);
        }

        // Calculate distance to office
        $distance = calculateDistance($lat, $lon, $officeLat, $officeLon);

        // Strict radius check
        if ($distance > $maxRadius) {
            sendJsonResponse([
                'success' => false,
                'message' => "Posisi Anda berada di luar radius kantor PTPN IV!\nJarak saat ini: {$distance} meter (Maksimal diperbolehkan: {$maxRadius} meter).",
                'distance' => $distance,
                'max_radius' => $maxRadius
            ], 403);
        }

        // Determine status (Hadir / Terlambat)
        $status = 'hadir';
        if ($currentTime > $lateTolerance) {
            $status = 'terlambat';
        }

        // Save photo
        $photoPath = saveBase64Photo($photoBase64, "in_u{$userId}_");

        if ($existing) {
            // Update existing record
            $update = $db->prepare("
                UPDATE attendances 
                SET time_in = ?, lat_in = ?, long_in = ?, distance_in_meters = ?, photo_in = ?, notes_in = ?, status = ?
                WHERE id = ?
            ");
            $update->execute([$currentTime, $lat, $lon, $distance, $photoPath, $notes, $status, $existing['id']]);
        } else {
            // Insert new record
            $insert = $db->prepare("
                INSERT INTO attendances 
                (user_id, attendance_date, time_in, lat_in, long_in, distance_in_meters, photo_in, notes_in, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insert->execute([$userId, $today, $currentTime, $lat, $lon, $distance, $photoPath, $notes, $status]);
        }

        $statusLabel = $status === 'terlambat' ? 'Terlambat' : 'Tepat Waktu';
        sendJsonResponse([
            'success' => true,
            'message' => "Absen masuk berhasil tercatat pada pukul " . substr($currentTime, 0, 5) . " WIB ({$statusLabel}). Jarak ke kantor: {$distance}m.",
            'data' => [
                'time_in' => $currentTime,
                'status' => $status,
                'distance' => $distance,
                'photo' => $photoPath
            ]
        ]);
    }

    // B. CLOCK OUT (Absen Pulang)
    if ($action === 'clock_out') {
        $stmt = $db->prepare("SELECT * FROM attendances WHERE user_id = ? AND attendance_date = ? LIMIT 1");
        $stmt->execute([$userId, $today]);
        $existing = $stmt->fetch();

        if (!$existing || empty($existing['time_in'])) {
            sendJsonResponse(['success' => false, 'message' => 'Anda belum melakukan absen masuk hari ini. Silakan absen masuk terlebih dahulu.'], 400);
        }

        if (!empty($existing['time_out'])) {
            sendJsonResponse(['success' => false, 'message' => 'Anda sudah melakukan absen pulang hari ini pada pukul ' . substr($existing['time_out'], 0, 5) . ' WIB.'], 400);
        }

        $lat = floatval($input['latitude'] ?? 0);
        $lon = floatval($input['longitude'] ?? 0);
        $notes = trim($input['notes'] ?? '');
        $photoBase64 = $input['photo'] ?? '';

        if ($lat == 0 || $lon == 0) {
            sendJsonResponse(['success' => false, 'message' => 'Gagal membaca koordinat GPS perangkat. Pastikan izin lokasi GPS aktif.'], 400);
        }

        if (empty($photoBase64)) {
            sendJsonResponse(['success' => false, 'message' => 'Foto selfie wajib diambil saat melakukan presensi pulang.'], 400);
        }

        // Calculate distance
        $distance = calculateDistance($lat, $lon, $officeLat, $officeLon);

        if ($distance > $maxRadius) {
            sendJsonResponse([
                'success' => false,
                'message' => "Posisi Anda berada di luar radius kantor PTPN IV!\nJarak saat ini: {$distance} meter (Maksimal diperbolehkan: {$maxRadius} meter).",
                'distance' => $distance,
                'max_radius' => $maxRadius
            ], 403);
        }

        $status = $existing['status'];
        // Check if leaving early before end time
        if ($currentTime < $workEnd && $status === 'hadir') {
            $status = 'pulang_cepat';
        }

        $photoPath = saveBase64Photo($photoBase64, "out_u{$userId}_");

        $update = $db->prepare("
            UPDATE attendances 
            SET time_out = ?, lat_out = ?, long_out = ?, distance_out_meters = ?, photo_out = ?, notes_out = ?, status = ?
            WHERE id = ?
        ");
        $update->execute([$currentTime, $lat, $lon, $distance, $photoPath, $notes, $status, $existing['id']]);

        sendJsonResponse([
            'success' => true,
            'message' => "Absen pulang berhasil tercatat pada pukul " . substr($currentTime, 0, 5) . " WIB. Selamat beristirahat!",
            'data' => [
                'time_out' => $currentTime,
                'status' => $status,
                'distance' => $distance,
                'photo' => $photoPath
            ]
        ]);
    }
}

sendJsonResponse(['success' => false, 'message' => 'Invalid action.'], 400);
