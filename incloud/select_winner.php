<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

function send_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    send_response(['success' => false, 'message' => 'طريقة الطلب غير مسموحة.'], 405);
}

if ($db_error !== '') {
    send_response(['success' => false, 'message' => 'تعذر الاتصال بقاعدة البيانات.'], 503);
}

$lock_result = mysqli_query($conn, "SELECT GET_LOCK('project_winner_draw', 10) AS lock_status");
$lock_row = $lock_result ? mysqli_fetch_assoc($lock_result) : null;

if (!$lock_row || (int) $lock_row['lock_status'] !== 1) {
    send_response(['success' => false, 'message' => 'تعذر بدء السحب الآن. حاول مرة أخرى.'], 409);
}

$release_lock = static function () use ($conn): void {
    mysqli_query($conn, "SELECT RELEASE_LOCK('project_winner_draw')");
};

mysqli_begin_transaction($conn);

try {
    $participant_query = 'SELECT u.id, u.firstName, u.lastName, u.email
        FROM users u
        LEFT JOIN winners w ON w.participant_id = u.id
        WHERE w.participant_id IS NULL AND u.eligible_at <= NOW()
        ORDER BY RAND()
        LIMIT 1';
    $participant_result = mysqli_query($conn, $participant_query);
    $participant = $participant_result ? mysqli_fetch_assoc($participant_result) : null;

    if (!$participant) {
        mysqli_rollback($conn);
        $release_lock();
        send_response(['success' => false, 'code' => 'empty', 'message' => 'لا يوجد مشاركون متاحون للسحب.'], 409);
    }

    $draw_result = mysqli_query($conn, 'SELECT COALESCE(MAX(draw_number), 0) + 1 AS next_draw FROM winners');
    $draw_row = $draw_result ? mysqli_fetch_assoc($draw_result) : null;
    $draw_number = $draw_row ? (int) $draw_row['next_draw'] : 1;
    $winner_name = trim($participant['firstName'] . ' ' . $participant['lastName']);

    $statement = mysqli_prepare($conn, 'INSERT INTO winners (participant_id, winner_name, winner_email, draw_number) VALUES (?, ?, ?, ?)');
    if (!$statement) {
        throw new RuntimeException('winner insert preparation failed');
    }

    mysqli_stmt_bind_param($statement, 'issi', $participant['id'], $winner_name, $participant['email'], $draw_number);
    $saved = mysqli_stmt_execute($statement);
    mysqli_stmt_close($statement);

    if (!$saved) {
        throw new RuntimeException('winner insert failed');
    }

    $winner_id = mysqli_insert_id($conn);
    $created_result = mysqli_query($conn, 'SELECT created_at FROM winners WHERE id = ' . (int) $winner_id);
    $created_row = $created_result ? mysqli_fetch_assoc($created_result) : null;

    mysqli_commit($conn);
    $release_lock();

    send_response([
        'success' => true,
        'winner' => [
            'id' => (int) $participant['id'],
            'name' => $winner_name,
            'email' => $participant['email'],
            'drawNumber' => $draw_number,
            'createdAt' => $created_row['created_at'] ?? ''
        ]
    ]);
} catch (Throwable $error) {
    mysqli_rollback($conn);
    $release_lock();
    send_response(['success' => false, 'message' => 'حدث خطأ أثناء إجراء السحب.'], 500);
}

