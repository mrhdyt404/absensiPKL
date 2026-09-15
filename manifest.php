<?php
// manifest.php - Dynamic Web App Manifest reflecting active theme
header('Content-Type: application/manifest+json; charset=utf-8');

$theme = $_GET['theme'] ?? $_COOKIE['absensi_pkl_theme'] ?? 'hijau';
$customColor = $_GET['color'] ?? $_COOKIE['absensi_pkl_custom_color'] ?? '';

$themes = [
    'hijau' => ['theme' => '#0b6e4f', 'bg' => '#064e3b'],
    'biru'  => ['theme' => '#1d4ed8', 'bg' => '#1e3a8a'],
    'pink'  => ['theme' => '#be185d', 'bg' => '#831843'],
    'oren'  => ['theme' => '#c2410c', 'bg' => '#7c2d12'],
    'ungu'  => ['theme' => '#7e22ce', 'bg' => '#581c87'],
    'teal'  => ['theme' => '#0f766e', 'bg' => '#134e4a'],
    'merah' => ['theme' => '#b91c1c', 'bg' => '#7f1d1d'],
];

$themeColor = '#0b6e4f';
$bgColor = '#064e3b';

if ($theme === 'custom' && !empty($customColor)) {
    $themeColor = '#' . ltrim($customColor, '#');
    $hex = ltrim($customColor, '#');
    if (strlen($hex) === 6) {
        $r = max(0, min(255, (int)(hexdec(substr($hex, 0, 2)) * 0.55)));
        $g = max(0, min(255, (int)(hexdec(substr($hex, 2, 2)) * 0.55)));
        $b = max(0, min(255, (int)(hexdec(substr($hex, 4, 2)) * 0.55)));
        $bgColor = sprintf("#%02x%02x%02x", $r, $g, $b);
    } else {
        $bgColor = $themeColor;
    }
} elseif (isset($themes[$theme])) {
    $themeColor = $themes[$theme]['theme'];
    $bgColor = $themes[$theme]['bg'];
}

$manifest = [
    "name" => "Absensi PKL PTPN IV Regional 3",
    "short_name" => "Absensi PKL",
    "description" => "Sistem Absensi Digital Magang PKL Siswa & Mahasiswa PT Perkebunan Nusantara IV Regional 3",
    "start_url" => "./index.php",
    "scope" => "./",
    "display" => "standalone",
    "orientation" => "portrait",
    "background_color" => $bgColor,
    "theme_color" => $themeColor,
    "lang" => "id",
    "icons" => [
        [
            "src" => "assets/icons/icon-192.png",
            "sizes" => "192x192",
            "type" => "image/png",
            "purpose" => "any"
        ],
        [
            "src" => "assets/icons/icon-192.png",
            "sizes" => "192x192",
            "type" => "image/png",
            "purpose" => "maskable"
        ],
        [
            "src" => "assets/icons/icon-512.png",
            "sizes" => "512x512",
            "type" => "image/png",
            "purpose" => "any"
        ],
        [
            "src" => "assets/icons/icon-512.png",
            "sizes" => "512x512",
            "type" => "image/png",
            "purpose" => "maskable"
        ]
    ]
];

echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
