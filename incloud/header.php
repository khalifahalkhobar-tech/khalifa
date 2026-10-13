<?php
$page_title = $page_title ?? 'الفائز';
$active_page = $active_page ?? 'home';
require_once __DIR__ . '/profile_data.php';
$profile = isset($conn) && $db_error === '' ? load_profile($conn) : ['display_name' => 'اسمي', 'photo_path' => ''];
?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/style.css">
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?> | الفائز</title>
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <div class="header-identity">
            <a class="profile-link <?php echo $active_page === 'profile' ? 'active' : ''; ?>" href="profile.php" aria-label="فتح الملف الشخصي">
                <?php if (!empty($profile['photo_path'])): ?>
                    <img class="profile-avatar profile-photo-trigger" src="./<?php echo htmlspecialchars($profile['photo_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="صورة <?php echo htmlspecialchars($profile['display_name'], ENT_QUOTES, 'UTF-8'); ?>" data-full-photo="./<?php echo htmlspecialchars($profile['photo_path'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php else: ?>
                    <span class="profile-avatar profile-placeholder" aria-hidden="true">ف</span>
                <?php endif; ?>
                <span class="profile-name"><?php echo htmlspecialchars($profile['display_name'], ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
            <a class="brand" href="index.php" aria-label="العودة إلى الصفحة الرئيسية">
                <span class="brand-mark">ف</span>
                <span>الفائز</span>
            </a>
        </div>
        <nav class="main-nav" aria-label="التنقل الرئيسي">
            <a class="<?php echo $active_page === 'home' ? 'active' : ''; ?>" href="index.php">الرئيسية</a>
            <a class="<?php echo $active_page === 'participants' ? 'active' : ''; ?>" href="participants.php">المشتركين</a>
            <a class="<?php echo $active_page === 'spin' ? 'active' : ''; ?>" href="spin.php">عجلة السحب</a>
            <a class="<?php echo $active_page === 'winners' ? 'active' : ''; ?>" href="winners.php">الفائزين</a>
        </nav>
    </div>
</header>
<?php if (!empty($profile['photo_path'])): ?>
<div class="profile-photo-modal" id="profile-photo-modal" hidden role="dialog" aria-modal="true" aria-label="عرض الصورة الشخصية">
    <div class="profile-photo-modal-backdrop" data-close-profile-photo></div>
    <div class="profile-photo-modal-content">
        <button type="button" class="profile-photo-close" data-close-profile-photo aria-label="إغلاق">&times;</button>
        <img src="./<?php echo htmlspecialchars($profile['photo_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="الصورة الشخصية بالحجم الكامل">
    </div>
</div>
<?php endif; ?>
