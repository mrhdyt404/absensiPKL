<?php
// api/report.php - Attendance Report & Export API
require_once __DIR__ . '/../config/db.php';

$userId = requireAuth(['admin']);
$db = getDB();

$format = $_GET['format'] ?? 'json'; // json, csv, print
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');
$institution = $_GET['institution'] ?? '';
$division = $_GET['division'] ?? '';
$userType = $_GET['user_type'] ?? '';

// Build query for summary recap
$query = "
    SELECT 
        u.id as user_id, u.full_name, u.identifier_no, u.user_type, u.institution, u.major, u.division, u.phone,
        COUNT(a.id) as total_presensi,
        SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END) as total_hadir,
        SUM(CASE WHEN a.status = 'terlambat' THEN 1 ELSE 0 END) as total_terlambat,
        SUM(CASE WHEN a.status = 'pulang_cepat' THEN 1 ELSE 0 END) as total_pulang_cepat,
        SUM(CASE WHEN a.status = 'izin' THEN 1 ELSE 0 END) as total_izin,
        SUM(CASE WHEN a.status = 'sakit' THEN 1 ELSE 0 END) as total_sakit,
        SUM(CASE WHEN a.status = 'alpha' THEN 1 ELSE 0 END) as total_alpha
    FROM users u
    LEFT JOIN attendances a ON u.id = a.user_id AND MONTH(a.attendance_date) = ? AND YEAR(a.attendance_date) = ?
    WHERE u.role = 'pkl' AND u.is_active = 1
";
$params = [$month, $year];

if (!empty($institution)) {
    $query .= " AND u.institution LIKE ?";
    $params[] = "%$institution%";
}

// Enforce admin division filter
$adminDivFilter = getAdminDivisionFilter('u', $params);
if (!empty($adminDivFilter)) {
    $query .= $adminDivFilter;
} elseif (!empty($division)) {
    $query .= " AND u.division LIKE ?";
    $params[] = "%$division%";
}

if (!empty($userType)) {
    $query .= " AND u.user_type = ?";
    $params[] = $userType;
}

$query .= " GROUP BY u.id ORDER BY u.institution ASC, u.full_name ASC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$reportData = $stmt->fetchAll();

// CSV Export format
if ($format === 'csv') {
    $monthNames = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];
    $monthLabel = $monthNames[str_pad($month, 2, '0', STR_PAD_LEFT)] ?? $month;
    $adminDiv = getAdminDivisionName();
    $divTag = !empty($adminDiv) ? "_" . preg_replace('/[^a-zA-Z0-9]/', '', $adminDiv) : "";
    $filename = "Rekap_Absensi_PKL_PTPN4{$divTag}_{$monthLabel}_{$year}.csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    // Add UTF-8 BOM for Excel compatibility
    fputs($output, "\xEF\xBB\xBF");

    // Title rows
    fputcsv($output, ['REKAPITULASI ABSENSI PESERTA PKL / MAGANG']);
    fputcsv($output, ['PT PERKEBUNAN NUSANTARA IV REGIONAL 3']);
    if (!empty($adminDiv)) {
        fputcsv($output, ['Divisi Penempatan:', $adminDiv]);
    }
    fputcsv($output, ['Periode:', "{$monthLabel} {$year}"]);
    fputcsv($output, []);

    // Headers
    fputcsv($output, [
        'No', 'Nama Lengkap', 'NIS / NIM', 'Kategori', 'Asal Sekolah / Kampus', 
        'Jurusan / Prodi', 'Divisi Penempatan', 'Hadir Tepat Waktu', 'Terlambat', 
        'Pulang Cepat', 'Izin', 'Sakit', 'Alpha', 'Total Kehadiran'
    ]);

    $no = 1;
    foreach ($reportData as $row) {
        fputcsv($output, [
            $no++,
            $row['full_name'],
            $row['identifier_no'],
            ucfirst($row['user_type']),
            $row['institution'],
            $row['major'],
            $row['division'],
            intval($row['total_hadir']),
            intval($row['total_terlambat']),
            intval($row['total_pulang_cepat']),
            intval($row['total_izin']),
            intval($row['total_sakit']),
            intval($row['total_alpha']),
            intval($row['total_presensi'])
        ]);
    }

    fclose($output);
    exit;
}

// JSON format
header('Content-Type: application/json; charset=utf-8');
sendJsonResponse([
    'success' => true,
    'month' => $month,
    'year' => $year,
    'data' => $reportData
]);
