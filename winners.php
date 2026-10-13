<?php
require_once __DIR__ . '/incloud/db.php';

$page_title = 'الفائزين';
$active_page = 'winners';
$winner_history = [];
$query_error = '';

if ($db_error === '') {
    $result = mysqli_query($conn, 'SELECT winner_name, winner_email, draw_number, created_at FROM winners ORDER BY draw_number DESC');
    if ($result) {
        $winner_history = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_free_result($result);
    } else {
        $query_error = 'تعذر تحميل سجل الفائزين.';
    }
} else {
    $query_error = $db_error;
}

require __DIR__ . '/incloud/header.php';
?>
<main>
    <section class="page-section winners-page">
        <div class="container">
            <div class="page-intro">
                <div>
                    <p class="eyebrow">السجل المحفوظ</p>
                    <h1>الفائزين</h1>
                    <p>تاريخ السحوبات محفوظه في قاعدة البيانات ولا تتأثر بتحديث الصفحة.</p>
                </div>
                <a class="secondary-link" href="spin.php">إجراء سحب جديد</a>
            </div>
            <?php if ($query_error !== ''): ?>
                <p class="empty-state error-text"><?php echo htmlspecialchars($query_error, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php elseif (empty($winner_history)): ?>
                <p class="empty-state">لم يتم اختيار أي فائز حتى الآن.</p>
            <?php else: ?>
                <div class="history-table-wrap">
                    <table class="history-table">
                        <thead><tr><th>#</th><th>اسم الفائز</th><th>البريد الإلكتروني</th><th>رقم السحب</th><th>التاريخ</th></tr></thead>
                        <tbody>
                        <?php foreach ($winner_history as $winner): ?>
                            <tr>
                                <td><?php echo (int) $winner['draw_number']; ?></td>
                                <td><?php echo htmlspecialchars($winner['winner_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td dir="ltr"><?php echo htmlspecialchars($winner['winner_email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo (int) $winner['draw_number']; ?></td>
                                <td dir="ltr"><?php echo htmlspecialchars($winner['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
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
