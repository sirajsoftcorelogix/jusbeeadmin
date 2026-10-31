CREATE TABLE IF NOT EXISTS Riyaz (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    media_id BIGINT UNSIGNED NOT NULL,
    synced_json VARCHAR(500) NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    added_date_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_date_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_riyaz_media_id (media_id),
    INDEX idx_riyaz_updated_by (updated_by),
    CONSTRAINT fk_riyaz_media FOREIGN KEY (media_id) REFERENCES ju_library (id) ON DELETE CASCADE,
    CONSTRAINT fk_riyaz_updated_by FOREIGN KEY (updated_by) REFERENCES ju_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
