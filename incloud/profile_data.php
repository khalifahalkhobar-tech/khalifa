<?php

function load_profile(mysqli $connection): array
{
    $profile = [
        'display_name' => 'اسمي',
        'bio' => 'مدير نظام السحب',
        'photo_path' => ''
    ];

    $result = mysqli_query($connection, 'SELECT display_name, bio, photo_path FROM profile WHERE id = 1 LIMIT 1');
    if ($result) {
        $stored_profile = mysqli_fetch_assoc($result);
        mysqli_free_result($result);
        if ($stored_profile) {
            $profile = array_merge($profile, $stored_profile);
        }
    }

    return $profile;
}
