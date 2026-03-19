<?php
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../classes/tools.class.php');
require_once(__DIR__ . '/../classes/security.class.php');
use Ramsey\Uuid\Uuid;

header('Content-Type: application/json');

Security::initSession();

if (empty($_SESSION['user_public_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Ei oikeuksia']);
    exit;
}

$user_public_id = $_SESSION['user_public_id'];

if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $code = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
    http_response_code(400);
    echo json_encode(['error' => 'Tiedoston lataus epäonnistui (koodi: ' . $code . ')']);
    exit;
}

$file = $_FILES['file'];

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']);

$allowed = [
    'image/jpeg'      => 'jpg',
    'image/png'       => 'png',
    'image/gif'       => 'gif',
    'image/webp'      => 'webp',
    'video/mp4'       => 'mp4',
    'video/webm'      => 'webm',
    'video/quicktime' => 'mov',
];

if (!array_key_exists($mime, $allowed)) {
    http_response_code(415);
    echo json_encode(['error' => 'Tiedostotyyppi ei ole sallittu: ' . $mime]);
    exit;
}

$is_video = str_starts_with($mime, 'video/');
$max_size = $is_video ? 100 * 1024 * 1024 : 10 * 1024 * 1024;

if ($file['size'] > $max_size) {
    $limit_mb = $max_size / (1024 * 1024);
    http_response_code(413);
    echo json_encode(['error' => "Tiedosto on liian suuri. Maksimikoko on {$limit_mb} MB."]);
    exit;
}

$ext        = $allowed[$mime];
$filename   = Uuid::uuid4()->toString() . '.' . $ext;
$upload_dir = __DIR__ . '/../uploads/' . $user_public_id . '/';

if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
    http_response_code(500);
    echo json_encode(['error' => 'Hakemiston luonti epäonnistui']);
    exit;
}

if (!move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
    http_response_code(500);
    echo json_encode(['error' => 'Tiedoston tallennus epäonnistui']);
    exit;
}

echo json_encode(['url' => ROOT_DIR . 'uploads/' . $user_public_id . '/' . $filename]);