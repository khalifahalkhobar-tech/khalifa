<?php

date_default_timezone_set('Asia/Riyadh');

$conn = mysqli_connect('localhost', 'root', '', 'project');
$db_error = '';

if (!$conn) {
    $db_error = 'تعذر الاتصال بقاعدة البيانات. تأكد من تشغيل MySQL وإنشاء قاعدة البيانات project.';
} else {
    mysqli_set_charset($conn, 'utf8mb4');
    require_once __DIR__ . '/draw_state.php';
    $draw_state = ensure_draw_state($conn);
}