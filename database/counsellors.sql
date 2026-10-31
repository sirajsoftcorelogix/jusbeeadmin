CREATE TABLE IF NOT EXISTS Counsellors (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    consellor_name VARCHAR(255) NOT NULL,
    profile_thumbnail VARCHAR(500) NULL,
    qualifications TEXT NULL,
    email VARCHAR(190) NULL,
    phone VARCHAR(30) NULL,
    verifies ENUM('Y', 'N') NOT NULL DEFAULT 'N',
    biography TEXT NULL,
    expertise TEXT NULL,
    standard_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discovery_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    updated_by BIGINT UNSIGNED NULL,
    added_date_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_date_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_counsellors_verifies (verifies),
    INDEX idx_counsellors_updated_by (updated_by),
    CONSTRAINT fk_counsellors_updated_by FOREIGN KEY (updated_by) REFERENCES ju_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
