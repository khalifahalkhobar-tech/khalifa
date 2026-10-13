<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$draw_state = ensure_draw_state($conn);

echo json_encode([
    'success' => true,
    'status' => $draw_state['status'] ?? 'open',
    'draw_end_at' => $draw_state['draw_end_at'] ?? null,
    'winner' => $draw_state['winner'] ?? null,
], JSON_UNESCAPED_UNICODE);

if ($db_error === '' && isset($conn)) {
    mysqli_close($conn);
}
