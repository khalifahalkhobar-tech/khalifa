<?php
require_once __DIR__ . '/incloud/db.php';

$page_title = 'المشاركون';
$active_page = 'participants';
$users = [];
$query_error = '';
$messages = [
    'added' => 'تمت إضافة المشترك بنجاح.',
    'updated' => 'تم تعديل اسم المشترك بنجاح.',
    'deleted' => 'تم حذف المشترك بنجاح.',
    'invalid' => 'بيانات المشترك غير صالحة.',
    'winner' => 'لا يمكن حذف المشترك تم اختياره كفائز.',
    'not_found' => 'لم يتم العثور على هذا المشترك.',
    'error' => 'حدث خطأ أثناء تحديث بيانات المشترك.'
];
$message_key = $_GET['message'] ?? '';
$message = $messages[$message_key] ?? '';
$message_type = in_array($message_key, ['added', 'updated', 'deleted'], true) ? 'success' : 'error';

if ($db_error === '') {
    $server_time_result = mysqli_query($conn, 'SELECT UNIX_TIMESTAMP(NOW()) AS server_now');
    $server_now = $server_time_result ? (int) mysqli_fetch_assoc($server_time_result)['server_now'] : time();
    $result = mysqli_query($conn, 'SELECT u.id, u.firstName, u.lastName, u.email, u.phone, u.eligible_at,
        UNIX_TIMESTAMP(u.eligible_at) AS eligible_at_timestamp,
        IF(w.participant_id IS NULL, 0, 1) AS has_won
        FROM users u
        LEFT JOIN winners w ON w.participant_id = u.id
        ORDER BY u.firstName ASC, u.lastName ASC');
    if ($result) {
        $users = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_free_result($result);
    } else {
        $query_error = 'تعذر تحميل قائمة المشتركين.';
    }
} else {
    $query_error = $db_error;
}

require __DIR__ . '/incloud/header.php';
?>
<main>
    <section class="page-section participants-page">
        <div class="container">
            <div class="page-intro">
                <div>
                    <p class="eyebrow">إدارة البيانات</p>
                    <h1>المشتركين</h1>
                    <p>أضف الأسماء وعدّلها وتابع حالة كل مشترك قبل إجراء السحب.</p>
                </div>
                <a class="submit-button action-link" href="add_participant.php">إضافة مشترك</a>
            </div>

            <?php if ($message !== ''): ?>
                <div class="form-message <?php echo $message_type; ?>" role="alert"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <?php if ($query_error !== ''): ?>
                <p class="empty-state error-text"><?php echo htmlspecialchars($query_error, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php elseif (empty($users)): ?>
                <div class="empty-state"><p>لا يوجد مشتركين مسجلون حتى الآن.</p><a class="secondary-link" href="add_participant.php">إضافة أول مشارك</a></div>
            <?php else: ?>
                <div class="management-table-wrap">
                    <table class="management-table">
                        <thead><tr><th>#</th><th>الاسم الأول</th><th>اسم العائلة</th><th>البريد الإلكتروني</th><th>الهاتف</th><th>الحالة</th><th>الإجراءات</th></tr></thead>
                        <tbody>
                        <?php foreach ($users as $index => $user): ?>
                            <?php $participant_name = trim($user['firstName'] . ' ' . $user['lastName']); ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($user['firstName'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($user['lastName'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td dir="ltr"><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td dir="ltr"><?php echo htmlspecialchars($user['phone'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <?php $is_waiting = !$user['has_won'] && (int) $user['eligible_at_timestamp'] > $server_now; ?>
                                <td>
                                    <span class="participant-status <?php echo $user['has_won'] ? 'status-winner' : ($is_waiting ? 'status-waiting' : 'status-available'); ?>">
                                        <?php echo $user['has_won'] ? 'فائز' : ($is_waiting ? 'انتظار 24 ساعة' : 'متاح'); ?>
                                    </span>
                                    <?php if ($is_waiting): ?><small class="countdown" data-eligible-at="<?php echo (int) $user['eligible_at_timestamp']; ?>" data-server-now="<?php echo $server_now; ?>">جارٍ حساب الوقت...</small><?php endif; ?>
                                </td>
                                <td class="management-actions">
                                    <a class="table-button" href="edit_participant.php?id=<?php echo (int) $user['id']; ?>">تعديل</a>
                                    <form method="post" action="delete_participant.php" class="inline-form delete-participant-form">
                                        <input type="hidden" name="participant_id" value="<?php echo (int) $user['id']; ?>">
                                        <button type="submit" class="table-button delete-button">حذف</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php require __DIR__ . '/incloud/footer.php'; ?>
<?php if ($db_error === '' && isset($conn)) { mysqli_close($conn); } ?>
