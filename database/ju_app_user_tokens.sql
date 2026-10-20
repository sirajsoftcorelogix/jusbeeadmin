CREATE TABLE IF NOT EXISTS ju_app_user_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    device VARCHAR(120) NULL DEFAULT NULL,
    expires_at TIMESTAMP NOT NULL,
    last_used_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ju_app_user_tokens_user (user_id),
    INDEX idx_ju_app_user_tokens_expires (expires_at),
    CONSTRAINT fk_ju_app_user_tokens_user FOREIGN KEY (user_id) REFERENCES ju_app_users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
