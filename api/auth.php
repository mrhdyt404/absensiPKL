<?php
// api/auth.php - Authentication API
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $username = trim($input['username'] ?? '');
    $password = trim($input['password'] ?? '');

    if (empty($username) || empty($password)) {
        sendJsonResponse(['success' => false, 'message' => 'Username dan kata sandi wajib diisi.'], 400);
    }

    $stmt = $db->prepare("SELECT * FROM users WHERE (username = ? OR email = ? OR identifier_no = ?) AND is_active = 1 LIMIT 1");
    $stmt->execute([$username, $username, $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        sendJsonResponse(['success' => false, 'message' => 'Username atau kata sandi salah / akun dinonaktifkan.'], 401);
    }

    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['user_type'] = $user['user_type'];

    unset($user['password']);

    sendJsonResponse([
        'success' => true,
        'message' => 'Login berhasil! Selamat datang, ' . htmlspecialchars($user['full_name']),
        'user' => $user
    ]);
}

if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    sendJsonResponse(['success' => true, 'message' => 'Berhasil logout.']);
}

if ($action === 'check') {
    if (!isset($_SESSION['user_id'])) {
        sendJsonResponse(['success' => false, 'authenticated' => false]);
    }

    $stmt = $db->prepare("SELECT id, username, full_name, identifier_no, user_type, institution, major, division, phone, email, avatar, period_start, period_end, role, is_active FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || !$user['is_active']) {
        session_destroy();
        sendJsonResponse(['success' => false, 'authenticated' => false]);
    }

    // Get today's attendance for this user if pkl
    $today = date('Y-m-d');
    $attStmt = $db->prepare("SELECT * FROM attendances WHERE user_id = ? AND attendance_date = ? LIMIT 1");
    $attStmt->execute([$user['id'], $today]);
    $todayAttendance = $attStmt->fetch();

    // Get today's leave if any
    $leaveStmt = $db->prepare("SELECT * FROM leaves_permits WHERE user_id = ? AND ? BETWEEN start_date AND end_date AND status = 'approved' LIMIT 1");
    $leaveStmt->execute([$user['id'], $today]);
    $todayLeave = $leaveStmt->fetch();

    // Get office location settings
    $settingsStmt = $db->query("SELECT * FROM settings_location LIMIT 1");
    $settings = $settingsStmt->fetch();

    sendJsonResponse([
        'success' => true,
        'authenticated' => true,
        'user' => $user,
        'today_attendance' => $todayAttendance,
        'today_leave' => $todayLeave,
        'settings' => $settings
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_profile') {
    $userId = requireAuth();
    
    // Fetch current user data from DB
    $userStmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $userStmt->execute([$userId]);
    $currentUser = $userStmt->fetch();

    if (!$currentUser) {
        sendJsonResponse(['success' => false, 'message' => 'Pengguna tidak ditemukan.'], 404);
    }

    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $fields = ["phone = ?", "email = ?"];
    $params = [$phone, $email];

    // Password change validation
    if (!empty($newPassword)) {
        if (empty($currentPassword)) {
            sendJsonResponse(['success' => false, 'message' => 'Kata sandi saat ini wajib diisi untuk verifikasi keamanan.'], 400);
        }

        if (!password_verify($currentPassword, $currentUser['password'])) {
            sendJsonResponse(['success' => false, 'message' => 'Kata sandi saat ini tidak sesuai.'], 400);
        }

        if (strlen($newPassword) < 6) {
            sendJsonResponse(['success' => false, 'message' => 'Kata sandi baru minimal 6 karakter.'], 400);
        }

        if ($newPassword !== $confirmPassword) {
            sendJsonResponse(['success' => false, 'message' => 'Konfirmasi kata sandi baru tidak cocok.'], 400);
        }

        $fields[] = "password = ?";
        $params[] = password_hash($newPassword, PASSWORD_BCRYPT);
    }

    // Handle Profile Picture Avatar Upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['avatar'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowedExts)) {
            sendJsonResponse(['success' => false, 'message' => 'Format foto profil harus JPG, PNG, atau WEBP.'], 400);
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            sendJsonResponse(['success' => false, 'message' => 'Ukuran file foto maksimal 5MB.'], 400);
        }

        $uploadDir = __DIR__ . '/../uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $filename = 'avatar_u' . $userId . '_' . time() . '.' . $ext;
        $destPath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            $avatarUrl = 'uploads/avatars/' . $filename;
            $fields[] = "avatar = ?";
            $params[] = $avatarUrl;

            // Delete previous avatar file if exists
            if (!empty($currentUser['avatar']) && file_exists(__DIR__ . '/../' . $currentUser['avatar'])) {
                @unlink(__DIR__ . '/../' . $currentUser['avatar']);
            }
        }
    }

    $params[] = $userId;
    $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    // Fetch updated user data
    $fetchUpdated = $db->prepare("SELECT id, username, full_name, identifier_no, user_type, institution, major, division, phone, email, avatar, period_start, period_end, role, is_active FROM users WHERE id = ?");
    $fetchUpdated->execute([$userId]);
    $updatedUser = $fetchUpdated->fetch();

    sendJsonResponse([
        'success' => true,
        'message' => 'Profil dan kata sandi berhasil diperbarui!',
        'user' => $updatedUser
    ]);
}

sendJsonResponse(['success' => false, 'message' => 'Invalid action.'], 400);
