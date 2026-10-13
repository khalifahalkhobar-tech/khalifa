<?php

$form_message = '';
$form_message_type = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['manage_action'])) {
     $action = $_POST['manage_action'];
     $participant_id = filter_var($_POST['participant_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

     if ($db_error !== '' || ($action !== 'add' && !$participant_id)) {
          $form_message = 'بيانات المشارك غير صالحة.';
     } elseif ($action === 'add') {
          $participant_name = trim($_POST['participant_name'] ?? '');
          $name_parts = preg_split('/\s+/u', $participant_name, 2, PREG_SPLIT_NO_EMPTY);

          if ($participant_name === '' || count($name_parts) > 2 || mb_strlen($participant_name) > 200) {
               $form_message = 'يرجى إدخال اسم مشارك صحيح.';
          } else {
               $first_name = $name_parts[0];
               $last_name = $name_parts[1] ?? '';
               $generated_email = 'participant-' . bin2hex(random_bytes(8)) . '@local.invalid';
               $statement = mysqli_prepare($conn, 'INSERT INTO users (firstName, lastName, email, eligible_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))');
               if ($statement) {
                    mysqli_stmt_bind_param($statement, 'sss', $first_name, $last_name, $generated_email);
                    $saved = mysqli_stmt_execute($statement);
                    mysqli_stmt_close($statement);
                    $form_message = $saved ? 'تمت إضافة المشارك بنجاح.' : 'تعذر إضافة المشارك.';
               } else {
                    $form_message = 'تعذر تجهيز إضافة المشارك.';
               }
          }
     } elseif ($action === 'edit') {
          $participant_name = trim($_POST['participant_name'] ?? '');
          $name_parts = preg_split('/\s+/u', $participant_name, 2, PREG_SPLIT_NO_EMPTY);

          if ($participant_name === '' || count($name_parts) > 2 || mb_strlen($participant_name) > 200) {
               $form_message = 'يرجى إدخال اسم المشارك بشكل صحيح.';
          } else {
               $first_name = $name_parts[0];
               $last_name = $name_parts[1] ?? '';
               $statement = mysqli_prepare($conn, 'UPDATE users SET firstName = ?, lastName = ? WHERE id = ?');
               if ($statement) {
                    mysqli_stmt_bind_param($statement, 'ssi', $first_name, $last_name, $participant_id);
                    $updated = mysqli_stmt_execute($statement);
                    $changed_rows = mysqli_stmt_affected_rows($statement);
                    mysqli_stmt_close($statement);
                    $form_message = $updated && $changed_rows >= 0 ? 'تم تعديل اسم المشارك بنجاح.' : 'تعذر تعديل بيانات المشارك.';
               } else {
                    $form_message = 'تعذر تجهيز تعديل المشارك.';
               }
          }
     } elseif ($action === 'delete') {
          $winner_statement = mysqli_prepare($conn, 'SELECT id FROM winners WHERE participant_id = ? LIMIT 1');
          $has_winner = false;
          if ($winner_statement) {
               mysqli_stmt_bind_param($winner_statement, 'i', $participant_id);
               mysqli_stmt_execute($winner_statement);
               mysqli_stmt_store_result($winner_statement);
               $has_winner = mysqli_stmt_num_rows($winner_statement) > 0;
               mysqli_stmt_close($winner_statement);
          }

          if ($has_winner) {
               $form_message = 'لا يمكن حذف مشارك تم اختياره كفائز.';
          } else {
               $statement = mysqli_prepare($conn, 'DELETE FROM users WHERE id = ?');
               if ($statement) {
                    mysqli_stmt_bind_param($statement, 'i', $participant_id);
                    $deleted = mysqli_stmt_execute($statement);
                    $deleted_rows = mysqli_stmt_affected_rows($statement);
                    mysqli_stmt_close($statement);
                    $form_message = $deleted && $deleted_rows === 1 ? 'تم حذف المشارك بنجاح.' : 'لم يتم العثور على هذا المشارك.';
               } else {
                    $form_message = 'تعذر تجهيز حذف المشارك.';
               }
          }
     } else {
          $form_message = 'إجراء غير صالح.';
     }

     $form_message_type = 'error';
     if (str_starts_with($form_message, 'تم ')) {
          $form_message_type = 'success';
     }
} elseif (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['submit'])) {
     $first_name = trim($_POST['firstName'] ?? '');
     $last_name = trim($_POST['lastName'] ?? '');
     $email = trim($_POST['email'] ?? '');

     if ($first_name === '') {
          $form_message = 'يرجى إدخال الاسم الأول.';
     } elseif ($last_name === '') {
          $form_message = 'يرجى إدخال اسم العائلة.';
     } elseif ($email === '') {
          $form_message = 'يرجى إدخال البريد الإلكتروني.';
     } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
          $form_message = 'يرجى إدخال بريد إلكتروني صحيح.';
     } elseif ($db_error !== '') {
          $form_message = $db_error;
     } else {
          $statement = mysqli_prepare($conn, 'INSERT INTO users (firstName, lastName, email) VALUES (?, ?, ?)');

          if ($statement) {
               mysqli_stmt_bind_param($statement, 'sss', $first_name, $last_name, $email);
               $saved = mysqli_stmt_execute($statement);
               mysqli_stmt_close($statement);

               if ($saved) {
                    $form_message = 'تم تسجيل بياناتك بنجاح.';
                    $form_message_type = 'success';
               } else {
                    $form_message = 'تعذر حفظ البيانات. تحقق من جدول users ثم حاول مرة أخرى.';
               }
          } else {
               $form_message = 'تعذر تجهيز عملية الحفظ في قاعدة البيانات.';
          }
     }

     if ($form_message_type !== 'success') {
          $form_message_type = 'error';
     }
}