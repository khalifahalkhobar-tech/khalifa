<?php
require_once __DIR__ . '/incloud/db.php';

$participant_id = filter_var($_POST['participant_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$message = 'invalid';

if ($db_error === '') {
    $winner_statement = mysqli_prepare($conn, 'SELECT id FROM winners WHERE participant_id = ? LIMIT 1');
    $has_winner = false;
    if ($winner_statement && $participant_id) {
        mysqli_stmt_bind_param($winner_statement, 'i', $participant_id);
        mysqli_stmt_execute($winner_statement);
        mysqli_stmt_store_result($winner_statement);
        $has_winner = mysqli_stmt_num_rows($winner_statement) > 0;
        mysqli_stmt_close($winner_statement);
    }

    if (!$participant_id) {
        $message = 'invalid';
    } elseif ($has_winner) {
        $message = 'winner';
    } else {
        $statement = mysqli_prepare($conn, 'DELETE FROM users WHERE id = ?');
        if ($statement) {
            mysqli_stmt_bind_param($statement, 'i', $participant_id);
            $deleted = mysqli_stmt_execute($statement);
            $deleted_rows = mysqli_stmt_affected_rows($statement);
            mysqli_stmt_close($statement);
            $message = $deleted && $deleted_rows === 1 ? 'deleted' : 'not_found';
        } else {
            $message = 'error';
        }
    }
}

header('Location: participants.php?message=' . urlencode($message));
exit;
