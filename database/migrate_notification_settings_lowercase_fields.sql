ALTER TABLE Notification_settings
    CHANGE COLUMN `Daily_reminder` daily_reminder TINYINT(1) NOT NULL DEFAULT 0,
    CHANGE COLUMN `Streak_end_reminder` streak_end_reminder TINYINT(1) NOT NULL DEFAULT 0,
    CHANGE COLUMN `Push_notification` push_notification TINYINT(1) NOT NULL DEFAULT 0,
    CHANGE COLUMN `Lunar_notification` lunar_notification TINYINT(1) NOT NULL DEFAULT 0,
    CHANGE COLUMN `Added_date_time` added_date_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
