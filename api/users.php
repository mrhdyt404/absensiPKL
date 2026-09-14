<?php
// api/users.php - PKL User Management by Admin
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$adminId = requireAuth(['admin']);
$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// 1. GET: List users or get single user
if ($method === 'GET') {
    if (isset($_GET['id'])) {
        $stmt = $db->prepare("SELECT id, username, full_name, identifier_no, user_type, institution, major, division, phone, email, avatar, period_start, period_end, role, is_active, created_at FROM users WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $user = $stmt->fetch();
        if (!$user) {
            sendJsonResponse(['success' => false, 'message' => 'Peserta tidak ditemukan.'], 404);
        }
        sendJsonResponse(['success' => true, 'data' => $user]);
    }

    $type = $_GET['type'] ?? '';
    $search = $_GET['search'] ?? '';
    $division = $_GET['division'] ?? '';
    $role = $_GET['role'] ?? 'pkl';

    $query = "SELECT id, username, full_name, identifier_no, user_type, institution, major, division, phone, email, avatar, period_start, period_end, role, is_active, created_at FROM users WHERE 1=1";
    $params = [];

    if (!empty($role)) {
        $query .= " AND role = ?";
        $params[] = $role;
    }

    if (!empty($type)) {
        $query .= " AND user_type = ?";
        $params[] = $type;
    }

    if (!empty($division)) {
        $query .= " AND division LIKE ?";
        $params[] = "%$division%";
    }

    if (!empty($search)) {
        $query .= " AND (full_name LIKE ? OR identifier_no LIKE ? OR institution LIKE ? OR username LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $query .= " ORDER BY id DESC";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll();

    // Summary statistics for PKL
    $statStmt = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN user_type = 'siswa' AND role = 'pkl' THEN 1 ELSE 0 END) as total_siswa,
            SUM(CASE WHEN user_type = 'mahasiswa' AND role = 'pkl' THEN 1 ELSE 0 END) as total_mahasiswa,
            SUM(CASE WHEN is_active = 1 AND role = 'pkl' THEN 1 ELSE 0 END) as total_active
        FROM users WHERE role = 'pkl'
    ");
    $stats = $statStmt->fetch();

    sendJsonResponse(['success' => true, 'data' => $users, 'stats' => $stats]);
}

// 2. POST: Create, Update, Toggle Status, Delete
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $input['action'] ?? $_GET['action'] ?? '';

    // A. Create new user
    if ($action === 'create') {
        $username = trim($input['username'] ?? '');
        $password = trim($input['password'] ?? '');
        $fullName = trim($input['full_name'] ?? '');
        $identifierNo = trim($input['identifier_no'] ?? '');
        $userType = in_array($input['user_type'] ?? '', ['siswa', 'mahasiswa']) ? $input['user_type'] : 'siswa';
        $institution = trim($input['institution'] ?? '');
        $major = trim($input['major'] ?? '');
        $division = trim($input['division'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $email = trim($input['email'] ?? '');
        $periodStart = !empty($input['period_start']) ? $input['period_start'] : null;
        $periodEnd = !empty($input['period_end']) ? $input['period_end'] : null;

        if (empty($username) || empty($password) || empty($fullName) || empty($identifierNo)) {
            sendJsonResponse(['success' => false, 'message' => 'Username, Kata Sandi, Nama Lengkap, dan NIS/NIM wajib diisi.'], 400);
        }

        // Check if username already exists
        $checkStmt = $db->prepare("SELECT id FROM users WHERE username = ? OR identifier_no = ?");
        $checkStmt->execute([$username, $identifierNo]);
        if ($checkStmt->fetch()) {
            sendJsonResponse(['success' => false, 'message' => 'Username atau NIS/NIM sudah terdaftar dalam sistem.'], 400);
        }

        $hashedPass = password_hash($password, PASSWORD_BCRYPT);
        $insertStmt = $db->prepare("
            INSERT INTO users (username, password, full_name, identifier_no, user_type, institution, major, division, phone, email, period_start, period_end, role, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pkl', 1)
        ");
        $insertStmt->execute([
            $username, $hashedPass, $fullName, $identifierNo, $userType,
            $institution, $major, $division, $phone, $email, $periodStart, $periodEnd
        ]);

        sendJsonResponse(['success' => true, 'message' => 'Akun PKL ' . htmlspecialchars($fullName) . ' berhasil dibuat!', 'id' => $db->lastInsertId()]);
    }

    // B. Update user
    if ($action === 'update') {
        $id = intval($input['id'] ?? 0);
        if (!$id) {
            sendJsonResponse(['success' => false, 'message' => 'ID peserta tidak valid.'], 400);
        }

        $fullName = trim($input['full_name'] ?? '');
        $identifierNo = trim($input['identifier_no'] ?? '');
        $userType = in_array($input['user_type'] ?? '', ['siswa', 'mahasiswa']) ? $input['user_type'] : 'siswa';
        $institution = trim($input['institution'] ?? '');
        $major = trim($input['major'] ?? '');
        $division = trim($input['division'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $email = trim($input['email'] ?? '');
        $periodStart = !empty($input['period_start']) ? $input['period_start'] : null;
        $periodEnd = !empty($input['period_end']) ? $input['period_end'] : null;
        $isActive = isset($input['is_active']) ? intval($input['is_active']) : 1;
        $newPassword = trim($input['password'] ?? '');

        // Check duplicate NIS/NIM
        $checkStmt = $db->prepare("SELECT id FROM users WHERE identifier_no = ? AND id != ?");
        $checkStmt->execute([$identifierNo, $id]);
        if ($checkStmt->fetch()) {
            sendJsonResponse(['success' => false, 'message' => 'NIS/NIM ini sudah digunakan oleh peserta lain.'], 400);
        }

        $fields = "full_name = ?, identifier_no = ?, user_type = ?, institution = ?, major = ?, division = ?, phone = ?, email = ?, period_start = ?, period_end = ?, is_active = ?";
        $params = [$fullName, $identifierNo, $userType, $institution, $major, $division, $phone, $email, $periodStart, $periodEnd, $isActive];

        if (!empty($newPassword)) {
            $fields .= ", password = ?";
            $params[] = password_hash($newPassword, PASSWORD_BCRYPT);
        }

        $params[] = $id;
        $updateStmt = $db->prepare("UPDATE users SET $fields WHERE id = ?");
        $updateStmt->execute($params);

        sendJsonResponse(['success' => true, 'message' => 'Data peserta PKL berhasil diperbarui.']);
    }

    // C. Toggle Active Status
    if ($action === 'toggle_status') {
        $id = intval($input['id'] ?? 0);
        $status = intval($input['is_active'] ?? 1);
        $stmt = $db->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role = 'pkl'");
        $stmt->execute([$status, $id]);
        sendJsonResponse(['success' => true, 'message' => 'Status akun berhasil diubah.']);
    }

    // D. Delete User
    if ($action === 'delete') {
        $id = intval($input['id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role = 'pkl'");
        $stmt->execute([$id]);
        sendJsonResponse(['success' => true, 'message' => 'Akun peserta PKL berhasil dihapus.']);
    }
}

sendJsonResponse(['success' => false, 'message' => 'Aksi tidak valid.'], 400);
