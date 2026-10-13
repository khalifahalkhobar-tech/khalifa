<?php
require_once __DIR__ . '/incloud/db.php';

$participant_id = filter_var($_GET['id'] ?? $_POST['participant_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$participant = null;
$message = '';

if ($db_error === '' && $participant_id) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $participant_name = trim($_POST['participant_name'] ?? '');
        $name_parts = preg_split('/\s+/u', $participant_name, 2, PREG_SPLIT_NO_EMPTY);
        if ($participant_name === '' || count($name_parts) > 2 || mb_strlen($participant_name) > 200) {
            $message = 'يرجى إدخال اسم المشترك بشكل صحيح.';
        } else {
            $first_name = $name_parts[0];
            $last_name = $name_parts[1] ?? '';
            $statement = mysqli_prepare($conn, 'UPDATE users SET firstName = ?, lastName = ? WHERE id = ?');
            if ($statement) {
                mysqli_stmt_bind_param($statement, 'ssi', $first_name, $last_name, $participant_id);
                $updated = mysqli_stmt_execute($statement);
                mysqli_stmt_close($statement);
                if ($updated) {
                    header('Location: participants.php?message=updated');
                    exit;
                }
            }
            $message = 'تعذر تعديل بيانات المشترك.';
        }
    }

    $statement = mysqli_prepare($conn, 'SELECT id, firstName, lastName FROM users WHERE id = ? LIMIT 1');
    if ($statement) {
        mysqli_stmt_bind_param($statement, 'i', $participant_id);
        mysqli_stmt_execute($statement);
        $result = mysqli_stmt_get_result($statement);
        $participant = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($statement);
    }
}

$page_title = 'تعديل المسترك';
$active_page = 'participants';
require __DIR__ . '/incloud/header.php';
?>
<main>
    <section class="page-section form-page">
        <div class="container narrow-container">
            <a class="back-link" href="participants.php">← العودة إلى المشتركين</a>
            <div class="form-card">
                <p class="eyebrow">إدارة البيانات</p>
                <h1>تعديل المشترك</h1>
                <?php if (!$participant): ?>
                    <p class="empty-state error-text">لم يتم العثور على هذا المشترك.</p>
                <?php else: ?>
                    <?php if ($message !== ''): ?><div class="form-message error" role="alert"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                    <form method="post" action="edit_participant.php?id=<?php echo (int) $participant['id']; ?>">
                        <input type="hidden" name="participant_id" value="<?php echo (int) $participant['id']; ?>">
                        <div class="field">
                            <label for="participant_name">اسم المشترك</label>
                            <input type="text" name="participant_name" id="participant_name" required maxlength="200" value="<?php echo htmlspecialchars(trim($participant['firstName'] . ' ' . $participant['lastName']), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="modal-actions">
                            <button type="submit" class="submit-button">حفظ التعديل</button>
                            <a class="cancel-button action-link" href="participants.php">إلغاء</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/incloud/footer.php'; ?>
<?php if ($db_error === '' && isset($conn)) { mysqli_close($conn); } ?>
