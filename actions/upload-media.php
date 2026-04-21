<?php
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../classes/tools.class.php');
require_once(__DIR__ . '/../classes/security.class.php');
use Ramsey\Uuid\Uuid;

header('Content-Type: application/json');

Security::initSession();

function mediaError(int $status, string $key, array $params = []): never
{
    http_response_code($status);
    echo json_encode(['error' => t($key, $params)]);
    exit;
}

if (empty($_SESSION['user_public_id'])) {
    mediaError(401, 'actions.upload_media.unauthorized');
}

$user_public_id = $_SESSION['user_public_id'];

if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $code = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
    mediaError(400, 'actions.upload_media.upload_failed', ['code' => $code]);
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
    'video/x-m4v'     => 'm4v',
];

if (!array_key_exists($mime, $allowed)) {
    mediaError(415, 'actions.upload_media.unsupported_type', ['mime' => $mime]);
}

$is_video = str_starts_with($mime, 'video/');
$max_size = $is_video ? UPLOAD_MAX_VIDEO_BYTES : UPLOAD_MAX_IMAGE_BYTES;

if ($file['size'] > $max_size) {
    $limit_mb = $max_size / (1024 * 1024);
    mediaError(413, 'actions.upload_media.file_too_large', ['limit' => $limit_mb]);
}

$ext        = $allowed[$mime];
$filename   = Uuid::uuid4()->toString() . '.' . $ext;
$upload_dir = __DIR__ . '/../uploads/' . $user_public_id . '/';

if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
    mediaError(500, 'actions.upload_media.directory_failed');
}

if (!move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
    mediaError(500, 'actions.upload_media.save_failed');
}

echo json_encode(['url' => ROOT_DIR . 'uploads/' . $user_public_id . '/' . $filename]);
