CREATE TABLE IF NOT EXISTS Notification_settings (
    app_user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    daily_reminder TINYINT(1) NOT NULL DEFAULT 0,
    streak_end_reminder TINYINT(1) NOT NULL DEFAULT 0,
    push_notification TINYINT(1) NOT NULL DEFAULT 0,
    lunar_notification TINYINT(1) NOT NULL DEFAULT 0,
    added_date_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_date_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_notification_settings_app_user
        FOREIGN KEY (app_user_id) REFERENCES ju_app_users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
