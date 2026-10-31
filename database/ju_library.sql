CREATE TABLE IF NOT EXISTS ju_library (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(160) NOT NULL UNIQUE,
    thumbnail VARCHAR(500) NULL DEFAULT NULL,
    media_file VARCHAR(500) NOT NULL,
    media_type ENUM('audio', 'video') NOT NULL,
    play_time VARCHAR(20) NULL DEFAULT NULL,
    artist VARCHAR(255) NULL DEFAULT NULL,
    description TEXT NULL,
    updated_by BIGINT UNSIGNED NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modified_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ju_library_status (status),
    INDEX idx_ju_library_updated_by (updated_by),
    CONSTRAINT fk_ju_library_updated_by FOREIGN KEY (updated_by) REFERENCES ju_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
