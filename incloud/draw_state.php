<?php

function get_draw_state_row(mysqli $connection): ?array
{
    $result = mysqli_query($connection, 'SELECT d.id, d.draw_started_at, d.draw_end_at, d.status, d.winner_id, d.updated_at,
        w.participant_id, w.winner_name, w.winner_email, w.draw_number, w.created_at
        FROM draw_state d
        LEFT JOIN winners w ON w.id = d.winner_id
        WHERE d.id = 1
        LIMIT 1');

    if (!$result) {
        return null;
    }

    $row = mysqli_fetch_assoc($result);
    mysqli_free_result($result);

    if (!$row) {
        return null;
    }

    return $row;
}

function ensure_draw_state(mysqli $connection): array
{
    $current_state = get_draw_state_row($connection);

    if (!$current_state) {
        $insert_statement = mysqli_prepare($connection, 'INSERT INTO draw_state (id, draw_started_at, draw_end_at, status) VALUES (1, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), "open")');
        if ($insert_statement) {
            mysqli_stmt_execute($insert_statement);
            mysqli_stmt_close($insert_statement);
        }
        $current_state = get_draw_state_row($connection);
    }

    if (!$current_state) {
        return [
            'status' => 'open',
            'draw_started_at' => date('Y-m-d H:i:s'),
            'draw_end_at' => date('Y-m-d H:i:s', strtotime('+7 days')),
            'winner' => null,
        ];
    }

    $status = (string) ($current_state['status'] ?? 'open');
    $expired_check = mysqli_query($connection, 'SELECT 1 FROM draw_state WHERE id = 1 AND status = "open" AND draw_end_at <= NOW() LIMIT 1');
    $draw_expired = $expired_check ? mysqli_num_rows($expired_check) > 0 : false;
    if ($expired_check) {
        mysqli_free_result($expired_check);
    }

    if ($status === 'open' && $draw_expired) {
        mysqli_begin_transaction($connection);

        try {
            $eligible_row = null;
            $eligible_result = mysqli_query($connection, 'SELECT u.id, u.firstName, u.lastName, u.email
                FROM users u
                LEFT JOIN winners w ON w.participant_id = u.id
                WHERE w.participant_id IS NULL
                ORDER BY RAND()
                LIMIT 1');

            if ($eligible_result) {
                $eligible_row = mysqli_fetch_assoc($eligible_result);
                mysqli_free_result($eligible_result);
            }

            $winner_id = null;
            if ($eligible_row) {
                $winner_name = trim((string) $eligible_row['firstName'] . ' ' . (string) $eligible_row['lastName']);
                $draw_number_result = mysqli_query($connection, 'SELECT COALESCE(MAX(draw_number), 0) + 1 AS next_draw FROM winners');
                $draw_number_row = $draw_number_result ? mysqli_fetch_assoc($draw_number_result) : null;
                $draw_number = $draw_number_row ? (int) $draw_number_row['next_draw'] : 1;

                $insert_statement = mysqli_prepare($connection, 'INSERT INTO winners (participant_id, winner_name, winner_email, draw_number) VALUES (?, ?, ?, ?)');
                if ($insert_statement) {
                    mysqli_stmt_bind_param($insert_statement, 'issi', $eligible_row['id'], $winner_name, $eligible_row['email'], $draw_number);
                    $saved = mysqli_stmt_execute($insert_statement);
                    mysqli_stmt_close($insert_statement);

                    if ($saved) {
                        $winner_id = (int) mysqli_insert_id($connection);
                    }
                }
            }

            if ($winner_id !== null) {
                $update_statement = mysqli_prepare($connection, 'UPDATE draw_state SET status = "closed", winner_id = ?, updated_at = NOW() WHERE id = 1');
                if ($update_statement) {
                    mysqli_stmt_bind_param($update_statement, 'i', $winner_id);
                    mysqli_stmt_execute($update_statement);
                    mysqli_stmt_close($update_statement);
                }
            } else {
                $close_statement = mysqli_prepare($connection, 'UPDATE draw_state SET status = "closed", winner_id = NULL, updated_at = NOW() WHERE id = 1');
                if ($close_statement) {
                    mysqli_stmt_execute($close_statement);
                    mysqli_stmt_close($close_statement);
                }
            }

            mysqli_commit($connection);
        } catch (Throwable $exception) {
            mysqli_rollback($connection);
        }
    }

    $final_state = get_draw_state_row($connection);
    if (!$final_state) {
        return [
            'status' => 'open',
            'draw_started_at' => date('Y-m-d H:i:s'),
            'draw_end_at' => date('Y-m-d H:i:s', strtotime('+7 days')),
            'winner' => null,
        ];
    }

    $winner_payload = null;
    if (!empty($final_state['winner_id']) && !empty($final_state['winner_name'])) {
        $winner_payload = [
            'id' => (int) $final_state['participant_id'],
            'name' => trim((string) $final_state['winner_name']),
            'email' => (string) $final_state['winner_email'],
            'drawNumber' => (int) $final_state['draw_number'],
            'createdAt' => (string) $final_state['created_at'],
        ];
    }

    return [
        'status' => (string) ($final_state['status'] ?? 'open'),
        'draw_started_at' => (string) ($final_state['draw_started_at'] ?? date('Y-m-d H:i:s')),
        'draw_end_at' => (string) ($final_state['draw_end_at'] ?? date('Y-m-d H:i:s', strtotime('+7 days'))),
        'winner' => $winner_payload,
    ];
}

function get_draw_status_label(string $status): string
{
    return $status === 'closed' ? 'مغلق' : 'مفتوح';
}
