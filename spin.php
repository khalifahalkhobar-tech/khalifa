<?php
require_once __DIR__ . '/incloud/db.php';

$page_title = 'عجلة السحب';
$active_page = 'spin';
$eligible_users = [];
$wheel_error = '';
$winner_count = 0;
$draw_state = $draw_state ?? ['status' => 'open', 'draw_end_at' => date('Y-m-d H:i:s', strtotime('+7 days')), 'winner' => null];
$winner = $draw_state['winner'] ?? null;

if ($db_error === '') {
    $eligible_result = mysqli_query($conn, 'SELECT u.id, u.firstName, u.lastName, u.email
        FROM users u
        LEFT JOIN winners w ON w.participant_id = u.id
        WHERE w.participant_id IS NULL
        ORDER BY u.firstName ASC, u.lastName ASC');
    $winner_count_result = mysqli_query($conn, 'SELECT COUNT(*) AS total FROM winners');
    if ($eligible_result) {
        $eligible_users = mysqli_fetch_all($eligible_result, MYSQLI_ASSOC);
        mysqli_free_result($eligible_result);
    } else {
        $wheel_error = 'تعذر تحميل المشتركين المتاحين للسحب.';
    }
    if ($winner_count_result) {
        $winner_count = (int) mysqli_fetch_assoc($winner_count_result)['total'];
    }
} else {
    $wheel_error = $db_error;
}

require __DIR__ . '/incloud/header.php';
?>
<main>
    <section class="draw-section dedicated-spin-page">
        <div class="container">
            <div class="page-intro">
                <div>
                    <p class="eyebrow">اختيار عادل</p>
                    <h1>عجلة السحب</h1>
                    <p>يتم اختيار فائز واحد تلقائيًا عند انتهاء فترة التسجيل.</p>
                </div>
                <div class="draw-stats" aria-live="polite">
                    <span><strong id="eligible-count"><?php echo count($eligible_users); ?></strong> متاح للسحب</span>
                    <span><strong id="winner-count"><?php echo $winner_count; ?></strong> فائز</span>
                </div>
            </div>

            <?php if ($wheel_error !== ''): ?>
                <p class="empty-state error-text"><?php echo htmlspecialchars($wheel_error, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php else: ?>
                <div class="draw-layout">
                    <div class="wheel-panel">
                        <div class="wheel-pointer" aria-hidden="true"></div>
                        <div class="wheel" id="winner-wheel" data-participants="<?php echo htmlspecialchars(json_encode($eligible_users, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>" data-draw-end-at="<?php echo (int) strtotime((string) ($draw_state['draw_end_at'] ?? date('Y-m-d H:i:s', strtotime('+7 days')))); ?>" data-auto-winner="<?php echo htmlspecialchars(json_encode($winner, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>" role="img" aria-label="عجلة أسماء المشاركين المتاحين للسحب">
                            <div class="wheel-labels" aria-hidden="true"></div>
                            <button type="button" class="wheel-spin-button" id="spin-button" <?php echo empty($eligible_users) || $draw_state['status'] === 'closed' ? 'disabled' : ''; ?>><?php echo $draw_state['status'] === 'closed' ? 'تم إغلاق السحب' : 'تدوير العجلة'; ?></button>
                        </div>
                    </div>
                    <div class="draw-result" id="draw-result" aria-live="polite" aria-atomic="true">
                        <?php if ($winner): ?>
                            <p class="result-kicker">مبروك!</p>
                            <h2>تم اختيار الفائز</h2>
                            <strong class="winner-name"><?php echo htmlspecialchars($winner['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            <p>رقم السحب: <?php echo (int) $winner['drawNumber']; ?></p>
                        <?php elseif (empty($eligible_users)): ?>
                            <p class="result-kicker">حالة السحب</p>
                            <h2>لا يوجد مشتركين متاحون للسحب الآن.</h2>
                            <p>سيتوفر السحب مرة أخرى عند بدء دورة جديدة.</p>
                        <?php else: ?>
                            <p class="result-kicker">النتيجة تظهر بعد توقف العجلة</p>
                            <h2>جاهز لاختيار الفائز</h2>
                            <p>اضغط على زر العجلة لبدء السحب.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php require __DIR__ . '/incloud/footer.php'; ?>
<?php if ($db_error === '' && isset($conn)) { mysqli_close($conn); } ?>
