<?php
require_once __DIR__ . '/incloud/db.php';

$draw_state = $draw_state ?? ['status' => 'open'];
$is_registration_closed = ($draw_state['status'] ?? 'open') === 'closed';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($db_error !== '') {
        $message = 'تعذر الاتصال بقاعدة البيانات.';
    } elseif ($is_registration_closed) {
        $message = 'انتهت فترة التسجيل. تم إغلاق السحب تلقائيًا.';
    } elseif ($first_name === '') {
        $message = 'يرجى إدخال الاسم الأول.';
    } elseif ($last_name === '') {
        $message = 'يرجى إدخال اسم العائلة.';
    } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'يرجى إدخال بريد إلكتروني صحيح.';
    } elseif ($phone === '' || !preg_match('/^[0-9+()\-\s]{7,30}$/', $phone)) {
        $message = 'يرجى إدخال رقم هاتف صحيح.';
    } elseif (mb_strlen($first_name) > 100 || mb_strlen($last_name) > 100) {
        $message = 'الاسم لا يمكن أن يتجاوز 100 حرف.';
    } else {
        $duplicate_check = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? LIMIT 1');
        $duplicate_exists = false;
        if ($duplicate_check) {
            mysqli_stmt_bind_param($duplicate_check, 's', $email);
            mysqli_stmt_execute($duplicate_check);
            mysqli_stmt_store_result($duplicate_check);
            $duplicate_exists = mysqli_stmt_num_rows($duplicate_check) > 0;
            mysqli_stmt_close($duplicate_check);
        }
        if ($duplicate_exists) {
            $message = 'هذا البريد الإلكتروني مسجل بالفعل.';
        } else {
            $statement = mysqli_prepare($conn, 'INSERT INTO users (firstName, lastName, email, phone, eligible_at) VALUES (?, ?, ?, ?, NOW())');
            $saved = false;
            if ($statement) {
                mysqli_stmt_bind_param($statement, 'ssss', $first_name, $last_name, $email, $phone);
                $saved = mysqli_stmt_execute($statement);
                mysqli_stmt_close($statement);
            }
            if ($saved) {
                header('Location: participants.php?message=added');
                exit;
            }
            $message = 'تعذر إضافة المشترك.';
        }
    }
}

$page_title = 'إضافة مشترك';
$active_page = 'participants';
require __DIR__ . '/incloud/header.php';
?>
<main>
    <section class="page-section form-page">
        <div class="container narrow-container">
            <a class="back-link" href="participants.php">← العودة إلى المشتركين</a>
            <div class="form-card">
                <p class="eyebrow">مشترك جديد</p>
                <h1>إضافة مشترك</h1>
                <p class="form-description">أدخل بيانات المشترك. يُحفظ الآن ويصبح متاحًا للسحب بعد 24 ساعة.</p>
                <?php if (!empty($message)): ?><div class="form-message error" role="alert"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                <form method="post" action="add_participant.php">
                    <div class="form-row">
                        <div class="field"><label for="first_name">الاسم الأول</label><input type="text" name="first_name" id="first_name" required maxlength="100" value="<?php echo htmlspecialchars($_POST['first_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                        <div class="field"><label for="last_name">اسم العائلة</label><input type="text" name="last_name" id="last_name" required maxlength="100" value="<?php echo htmlspecialchars($_POST['last_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                    </div>
                    <div class="field"><label for="email">Gmail / البريد الإلكتروني</label><input type="email" name="email" id="email" required maxlength="255" dir="ltr" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div class="field"><label for="phone">رقم الهاتف</label><input type="tel" name="phone" id="phone" required maxlength="30" inputmode="tel" dir="ltr" value="<?php echo htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <button type="submit" class="submit-button">إضافة مشترك</button>
                </form>
            </div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/incloud/footer.php'; ?>
<?php if ($db_error === '' && isset($conn)) { mysqli_close($conn); } ?>
