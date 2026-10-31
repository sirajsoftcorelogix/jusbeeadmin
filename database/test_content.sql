CREATE TABLE IF NOT EXISTS Content_Categories (
    category_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(255) NOT NULL,
    slug VARCHAR(160) NOT NULL UNIQUE,
    parent_id BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    added_date_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_date_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_content_categories_parent (parent_id),
    INDEX idx_content_categories_updated_by (updated_by),
    CONSTRAINT fk_content_categories_parent FOREIGN KEY (parent_id) REFERENCES Content_Categories (category_id) ON DELETE SET NULL,
    CONSTRAINT fk_content_categories_updated_by FOREIGN KEY (updated_by) REFERENCES ju_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS test_content (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(160) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    short_test VARCHAR(1000) NULL,
    full_text LONGTEXT NOT NULL,
    thumb_nail VARCHAR(500) NULL,
    updated_by BIGINT UNSIGNED NULL,
    added_date_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_date_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_test_content_updated_by (updated_by),
    CONSTRAINT fk_test_content_updated_by FOREIGN KEY (updated_by) REFERENCES ju_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS Category_content_map (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_slug VARCHAR(160) NOT NULL,
    content_slug VARCHAR(160) NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    added_date_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_date_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_category_content_map_category_content (category_slug, content_slug),
    INDEX idx_category_content_map_content (content_slug),
    INDEX idx_category_content_map_updated_by (updated_by),
    CONSTRAINT fk_category_content_map_category FOREIGN KEY (category_slug) REFERENCES Content_Categories (slug) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_category_content_map_content FOREIGN KEY (content_slug) REFERENCES test_content (slug) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_category_content_map_updated_by FOREIGN KEY (updated_by) REFERENCES ju_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
