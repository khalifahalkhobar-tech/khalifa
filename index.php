<?php
require_once __DIR__ . '/incloud/db.php';

$page_title = 'الرئيسية';
$active_page = 'home';
$registered_count = 0;
$winner_count = 0;
$draw_state = $draw_state ?? ['status' => 'open', 'draw_end_at' => date('Y-m-d H:i:s', strtotime('+7 days'))];
$draw_end_timestamp = strtotime((string) ($draw_state['draw_end_at'] ?? date('Y-m-d H:i:s', strtotime('+7 days'))));
$draw_status_text = ($draw_state['status'] ?? 'open') === 'closed' ? 'تم إغلاق التسجيل' : 'التسجيل مفتوح';
$remaining_seconds = max(0, (int) $draw_end_timestamp - time());
$days = (int) floor($remaining_seconds / 86400);
$hours = (int) floor(($remaining_seconds % 86400) / 3600);
$minutes = (int) floor(($remaining_seconds % 3600) / 60);
$seconds = (int) $remaining_seconds % 60;
$initial_countdown_text = sprintf('%02d أيام : %02d ساعة : %02d دقيقة : %02d ثانية', $days, $hours, $minutes, $seconds);

if ($db_error === '') {
    $user_count_result = mysqli_query($conn, 'SELECT COUNT(*) AS total FROM users');
    $winner_count_result = mysqli_query($conn, 'SELECT COUNT(*) AS total FROM winners');
    $registered_count = $user_count_result ? (int) mysqli_fetch_assoc($user_count_result)['total'] : 0;
    $winner_count = $winner_count_result ? (int) mysqli_fetch_assoc($winner_count_result)['total'] : 0;
}

require __DIR__ . '/incloud/header.php';
?>
<main>
    <section class="hero home-hero">
        <div class="container hero-grid">
            <div class="hero-copy">
                <p class="eyebrow">منصة تنظيم السحب</p>
                <h1>الفائز<br><span>اختيار واضح وعادل.</span></h1>
                <p class="hero-text">أدر المشاركين، نفّذ السحب بعجلة تفاعلية، واحتفظ بتاريخ الفائزين في مكان واحد.</p>
                <div class="draw-countdown-box" aria-live="polite">
                    <p class="card-kicker">الوقت المتبقي</p>
                    <div class="draw-timer" data-countdown-end="<?php echo (int) $draw_end_timestamp; ?>">
                        <?php echo htmlspecialchars($initial_countdown_text, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <small><?php echo htmlspecialchars($draw_status_text, ENT_QUOTES, 'UTF-8'); ?></small>
                </div>
                <div class="hero-actions">
                    <a class="submit-button action-link" href="participants.php">إدارة المشاركين</a>
                    <a class="secondary-link" href="spin.php">الانتقال إلى العجلة</a>
                </div>
            </div>
            <div class="home-overview">
                <p class="card-kicker">نظرة سريعة</p>
                <h2>كل ما تحتاجه للقرعة الدراسية</h2>
                <div class="overview-stats">
                    <div><strong><?php echo $registered_count; ?></strong><span>مشارك</span></div>
                    <div><strong><?php echo $winner_count; ?></strong><span>فائز</span></div>
                </div>
                <div class="overview-links">
                    <a href="participants.php"><span>01</span><strong>إدارة الأسماء</strong><small>إضافة وتعديل وحذف المشاركين</small></a>
                    <a href="spin.php"><span>02</span><strong>إجراء السحب</strong><small>اختيار فائز غير مكرر</small></a>
                    <a href="winners.php"><span>03</span><strong>مراجعة النتائج</strong><small>عرض التاريخ المحفوظ</small></a>
                </div>
            </div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/incloud/footer.php'; ?>
<?php if ($db_error === '' && isset($conn)) { mysqli_close($conn); } ?>
