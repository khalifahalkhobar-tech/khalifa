CREATE DATABASE IF NOT EXISTS project CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE project;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    firstName VARCHAR(100) NOT NULL,
    lastName VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(30) NOT NULL DEFAULT '',
    eligible_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS winners (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    participant_id INT UNSIGNED NOT NULL,
    winner_name VARCHAR(201) NOT NULL,
    winner_email VARCHAR(255) NOT NULL,
    draw_number INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_participant_winner (participant_id),
    UNIQUE KEY unique_draw_number (draw_number),
    CONSTRAINT fk_winners_participant FOREIGN KEY (participant_id) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS draw_state (
    id TINYINT UNSIGNED NOT NULL,
    draw_started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    draw_end_at DATETIME NOT NULL,
    status ENUM('open', 'closed') NOT NULL DEFAULT 'open',
    winner_id INT UNSIGNED NULL DEFAULT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_draw_state_winner FOREIGN KEY (winner_id) REFERENCES winners (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO draw_state (id, draw_started_at, draw_end_at, status)
VALUES (1, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), 'open')
ON DUPLICATE KEY UPDATE id = id;

CREATE TABLE IF NOT EXISTS profile (
    id TINYINT UNSIGNED NOT NULL,
    display_name VARCHAR(100) NOT NULL DEFAULT 'اسمي',
    bio VARCHAR(300) NOT NULL DEFAULT 'مدير نظام السحب',
    photo_path VARCHAR(255) NOT NULL DEFAULT '',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO profile (id, display_name, bio)
VALUES (1, 'اسمي', 'مدير نظام السحب')
ON DUPLICATE KEY UPDATE id = id;
