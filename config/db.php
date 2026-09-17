<?php
// config/db.php - Database connection and global utilities

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    // Set session cookie parameters for security & PWA persistence
    session_set_cookie_params([
        'lifetime' => 86400 * 30, // 30 days
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'absensi_pkl');
define('DB_USER', 'absensi_user');
define('DB_PASS', 'pkl_secret_2026');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Koneksi database gagal: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    return $pdo;
}

// JSON response helper
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Check logged in user
function requireAuth($allowedRoles = []) {
    if (!isset($_SESSION['user_id'])) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Sesi login tidak valid atau telah berakhir. Silakan login kembali.'
        ], 401);
    }
    
    if (!empty($allowedRoles) && !in_array($_SESSION['role'], $allowedRoles)) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Akses ditolak. Anda tidak memiliki hak akses.'
        ], 403);
    }
    
    return $_SESSION['user_id'];
}

// Haversine formula to calculate distance in meters between two coordinates
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // in meters

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $distance = $earthRadius * $c;

    return round($distance);
}

// Helper to get SQL WHERE clause condition based on logged-in admin division
function getAdminDivisionFilter($tableAlias = 'u', &$params = []) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        return '';
    }

    $division = $_SESSION['division'] ?? '';
    if (empty($division) && isset($_SESSION['user_id'])) {
        $db = getDB();
        $stmt = $db->prepare("SELECT division FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $division = $stmt->fetchColumn() ?: '';
        $_SESSION['division'] = $division;
    }

    if (empty($division)) {
        return '';
    }

    $col = !empty($tableAlias) ? "{$tableAlias}.division" : "division";
    $divLower = strtolower(trim($division));

    // If division is Teknik & Pengolahan (handles '&', 'dan', 'Dan')
    if (strpos($divLower, 'teknik') !== false && strpos($divLower, 'pengolahan') !== false) {
        return " AND ({$col} LIKE '%Teknik%' AND {$col} LIKE '%Pengolahan%')";
    }

    // If division is Sistem Manajemen
    if (strpos($divLower, 'sistem manajemen') !== false || strpos($divLower, 'manajemen') !== false) {
        $params[] = "%Sistem Manajemen%";
        return " AND {$col} LIKE ?";
    }

    // Any other specific division
    $params[] = "%{$division}%";
    return " AND {$col} LIKE ?";
}

// Helper to get active admin division name
function getAdminDivisionName() {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        return '';
    }
    return $_SESSION['division'] ?? '';
}

