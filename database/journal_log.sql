CREATE TABLE IF NOT EXISTS journal_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type ENUM('journal', 'graditude', 'dialy_dream', 'worry_tree') NOT NULL,
    app_user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    favorite TINYINT(1) NOT NULL DEFAULT 0,
    added_date_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_journal_log_user_type_added (app_user_id, type, added_date_time),
    CONSTRAINT fk_journal_log_app_user FOREIGN KEY (app_user_id) REFERENCES ju_app_users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
