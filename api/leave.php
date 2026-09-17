<?php
// api/leave.php - Leave & Permit (Izin / Sakit) API
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$userId = requireAuth();
$db = getDB();
$role = $_SESSION['role'] ?? 'pkl';
$method = $_SERVER['REQUEST_METHOD'];
$rawInput = file_get_contents('php://input');
$jsonInput = json_decode($rawInput, true) ?? [];
$inputData = !empty($jsonInput) ? $jsonInput : $_POST;
$action = $inputData['action'] ?? $_GET['action'] ?? '';
$db = getDB();

// 1. GET: List leave requests
if ($method === 'GET') {
    if ($role === 'admin') {
        // Admin: List all leaves from all PKL students
        $status = $_GET['status'] ?? '';
        $type = $_GET['type'] ?? '';
        $search = $_GET['search'] ?? '';

        $query = "
            SELECT 
                lp.*, 
                u.full_name, u.identifier_no, u.user_type, u.institution, u.major, u.division, u.phone,
                adm.full_name as reviewer_name
            FROM leaves_permits lp
            JOIN users u ON lp.user_id = u.id
            LEFT JOIN users adm ON lp.reviewed_by = adm.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($status)) {
            $query .= " AND lp.status = ?";
            $params[] = $status;
        }

        if (!empty($type)) {
            $query .= " AND lp.leave_type = ?";
            $params[] = $type;
        }

        if (!empty($search)) {
            $query .= " AND (u.full_name LIKE ? OR u.identifier_no LIKE ? OR u.institution LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        // Enforce admin division filter
        $adminDivFilter = getAdminDivisionFilter('u', $params);
        if (!empty($adminDivFilter)) {
            $query .= $adminDivFilter;
        }

        $query .= " ORDER BY lp.created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $leaves = $stmt->fetchAll();

        // Pending count filtered by admin division
        $pendingParams = [];
        $pendingDivFilter = getAdminDivisionFilter('u', $pendingParams);
        $pendingStmt = $db->prepare("SELECT COUNT(*) FROM leaves_permits lp JOIN users u ON lp.user_id = u.id WHERE lp.status = 'pending' {$pendingDivFilter}");
        $pendingStmt->execute($pendingParams);
        $pendingCount = $pendingStmt->fetchColumn();

        sendJsonResponse(['success' => true, 'data' => $leaves, 'pending_count' => intval($pendingCount)]);
    } else {
        // PKL: List current user's leaves
        $stmt = $db->prepare("
            SELECT lp.*, adm.full_name as reviewer_name
            FROM leaves_permits lp
            LEFT JOIN users adm ON lp.reviewed_by = adm.id
            WHERE lp.user_id = ?
            ORDER BY lp.created_at DESC
        ");
        $stmt->execute([$userId]);
        $leaves = $stmt->fetchAll();

        sendJsonResponse(['success' => true, 'data' => $leaves]);
    }
}

// 2. POST: Submit leave or Review leave
if ($method === 'POST') {
    // A. Submit Leave (by PKL)
    if ($action === 'submit_leave') {
        $leaveType = $_POST['leave_type'] ?? 'izin_pribadi';
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';
        $reason = trim($_POST['reason'] ?? '');

        if (empty($startDate) || empty($endDate) || empty($reason)) {
            sendJsonResponse(['success' => false, 'message' => 'Jenis izin, tanggal mulai, tanggal selesai, dan alasan wajib diisi.'], 400);
        }

        if ($startDate > $endDate) {
            sendJsonResponse(['success' => false, 'message' => 'Tanggal mulai tidak boleh lebih besar dari tanggal selesai.'], 400);
        }

        // Handle proof file upload
        $proofFilePath = null;
        if (isset($_FILES['proof_file']) && $_FILES['proof_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['proof_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'pdf', 'webp'];

            if (!in_array($ext, $allowedExts)) {
                sendJsonResponse(['success' => false, 'message' => 'Format file bukti harus berupa JPG, PNG, WEBP, atau PDF.'], 400);
            }

            if ($file['size'] > 5 * 1024 * 1024) { // 5MB max
                sendJsonResponse(['success' => false, 'message' => 'Ukuran file maksimal adalah 5MB.'], 400);
            }

            $uploadDir = __DIR__ . '/../uploads/proofs/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $filename = 'proof_u' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                $proofFilePath = 'uploads/proofs/' . $filename;
            }
        }

        // Insert leave record
        $stmt = $db->prepare("
            INSERT INTO leaves_permits (user_id, leave_type, start_date, end_date, reason, proof_file, status)
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$userId, $leaveType, $startDate, $endDate, $reason, $proofFilePath]);

        sendJsonResponse([
            'success' => true,
            'message' => 'Pengajuan izin / sakit berhasil dikirim! Menunggu konfirmasi dari pembimbing / admin SDM.'
        ]);
    }

    // B. Review Leave (Admin only: Approve / Reject)
    if ($action === 'review_leave') {
        requireAuth(['admin']);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $leaveId = intval($input['leave_id'] ?? 0);
        $newStatus = in_array($input['status'] ?? '', ['approved', 'rejected']) ? $input['status'] : '';
        $adminNotes = trim($input['admin_notes'] ?? '');

        if (!$leaveId || empty($newStatus)) {
            sendJsonResponse(['success' => false, 'message' => 'Data verifikasi tidak lengkap.'], 400);
        }

        // Get leave details
        $stmt = $db->prepare("SELECT * FROM leaves_permits WHERE id = ?");
        $stmt->execute([$leaveId]);
        $leave = $stmt->fetch();

        if (!$leave) {
            sendJsonResponse(['success' => false, 'message' => 'Pengajuan izin tidak ditemukan.'], 404);
        }

        // Update leave status
        $update = $db->prepare("
            UPDATE leaves_permits 
            SET status = ?, admin_notes = ?, reviewed_by = ?, reviewed_at = NOW()
            WHERE id = ?
        ");
        $update->execute([$newStatus, $adminNotes, $userId, $leaveId]);

        // If approved, create or update attendances records for all days in date range
        if ($newStatus === 'approved') {
            $attStatus = ($leave['leave_type'] === 'sakit') ? 'sakit' : 'izin';
            $period = new DatePeriod(
                new DateTime($leave['start_date']),
                new DateInterval('P1D'),
                (new DateTime($leave['end_date']))->modify('+1 day')
            );

            foreach ($period as $date) {
                $dStr = $date->format('Y-m-d');
                // Insert or update attendance for that date
                $attStmt = $db->prepare("
                    INSERT INTO attendances (user_id, attendance_date, status, notes_in)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE status = VALUES(status), notes_in = VALUES(notes_in)
                ");
                $notes = "Izin disetujui: " . $leave['reason'];
                $attStmt->execute([$leave['user_id'], $dStr, $attStatus, $notes]);
            }
        }

        $statusLabel = ($newStatus === 'approved') ? 'disetujui' : 'ditolak';
        sendJsonResponse([
            'success' => true,
            'message' => "Pengajuan izin berhasil {$statusLabel}."
        ]);
    }
}

sendJsonResponse(['success' => false, 'message' => 'Aksi tidak valid.'], 400);
