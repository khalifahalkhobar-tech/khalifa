<?php
require_once __DIR__ . '/incloud/db.php';
require_once __DIR__ . '/incloud/profile_data.php';

$page_title = 'الملف الشخصي';
$active_page = 'profile';
$message = '';
$message_type = 'error';
$profile = $db_error === '' ? load_profile($conn) : ['display_name' => 'اسمي', 'bio' => 'مدير نظام السحب', 'photo_path' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db_error === '') {
    $display_name = trim($_POST['display_name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $new_photo_path = $profile['photo_path'];
    $uploaded_absolute_path = '';

    if ($display_name === '' || mb_strlen($display_name) > 100) {
        $message = 'يرجى إدخال اسم صحيح لا يتجاوز 100 حرف.';
    } elseif (mb_strlen($bio) > 300) {
        $message = 'النبذة قصيرة بحد أقصى 300 حرف.';
    } elseif (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['profile_photo'];
        $allowed_types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        $image_info = $file['error'] === UPLOAD_ERR_OK ? @getimagesize($file['tmp_name']) : false;
        $mime_type = $image_info['mime'] ?? '';
        $upload_directory = __DIR__ . '/uploads/profile';

        if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024 || !$image_info || !isset($allowed_types[$mime_type])) {
            $message = 'يرجى اختيار صورة JPG أو PNG أو GIF أو WEBP لا يتجاوز حجمها 5MB.';
        } elseif (!is_dir($upload_directory) && !mkdir($upload_directory, 0755, true)) {
            $message = 'تعذر تجهيز مجلد حفظ الصورة.';
        } else {
            $filename = 'profile_' . bin2hex(random_bytes(12)) . '.' . $allowed_types[$mime_type];
            $uploaded_absolute_path = $upload_directory . DIRECTORY_SEPARATOR . $filename;
            if (move_uploaded_file($file['tmp_name'], $uploaded_absolute_path)) {
                $new_photo_path = 'uploads/profile/' . $filename;
            } else {
                $message = 'تعذر حفظ الصورة محليًا.';
            }
        }
    }

    if ($message === '') {
        $statement = mysqli_prepare($conn, 'INSERT INTO profile (id, display_name, bio, photo_path) VALUES (1, ?, ?, ?) ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), bio = VALUES(bio), photo_path = VALUES(photo_path)');
        if ($statement) {
            mysqli_stmt_bind_param($statement, 'sss', $display_name, $bio, $new_photo_path);
            $saved = mysqli_stmt_execute($statement);
            mysqli_stmt_close($statement);
            if ($saved) {
                $message = 'تم تحديث الملف الشخصي بنجاح.';
                $message_type = 'success';
                $profile = ['display_name' => $display_name, 'bio' => $bio, 'photo_path' => $new_photo_path];
            } else {
                if ($uploaded_absolute_path !== '' && is_file($uploaded_absolute_path)) {
                    unlink($uploaded_absolute_path);
                }
                $message = 'تعذر حفظ بيانات الملف الشخصي.';
            }
        } else {
            if ($uploaded_absolute_path !== '' && is_file($uploaded_absolute_path)) {
                unlink($uploaded_absolute_path);
            }
            $message = 'تعذر تجهيز تحديث الملف الشخصي.';
        }
    }
}

require __DIR__ . '/incloud/header.php';
?>
<main>
    <section class="page-section form-page profile-page">
        <div class="container narrow-container">
            <div class="page-intro profile-intro">
                <div>
                    <p class="eyebrow">الهوية الشخصية</p>
                    <h1>الملف الشخصي</h1>
                    <p>حدّث الاسم والنبذة والصورة التي تظهر في رأس الموقع.</p>
                </div>
            </div>
            <div class="form-card">
                <div class="profile-preview">
                    <?php if (!empty($profile['photo_path'])): ?>
                        <img class="profile-preview-image" src="./<?php echo htmlspecialchars($profile['photo_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="الصورة الشخصية">
                    <?php else: ?>
                        <span class="profile-preview-image profile-placeholder" aria-hidden="true">ف</span>
                    <?php endif; ?>
                    <div><strong><?php echo htmlspecialchars($profile['display_name'], ENT_QUOTES, 'UTF-8'); ?></strong><span>تظهر هذه البيانات في جميع الصفحات</span></div>
                </div>
                <?php if ($message !== ''): ?><div class="form-message <?php echo $message_type; ?>" role="alert"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                <form method="post" action="profile.php" enctype="multipart/form-data">
                    <div class="field">
                        <label for="display_name">الاسم</label>
                        <input type="text" name="display_name" id="display_name" required maxlength="100" value="<?php echo htmlspecialchars($profile['display_name'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="field">
                        <label for="bio">النبذة القصيرة</label>
                        <textarea name="bio" id="bio" maxlength="300" rows="4"><?php echo htmlspecialchars($profile['bio'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <div class="field">
                        <label for="profile_photo">الصورة الشخصية</label>
                        <input type="file" name="profile_photo" id="profile_photo" accept="image/jpeg,image/png,image/gif,image/webp">
                        <small class="field-help">صورة محلية JPG أو PNG أو GIF أو WEBP، بحد أقصى 5MB.</small>
                    </div>
                    <button type="submit" class="submit-button">حفظ الملف الشخصي</button>
                </form>
            </div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/incloud/footer.php'; ?>
<?php if ($db_error === '' && isset($conn)) { mysqli_close($conn); } ?>
