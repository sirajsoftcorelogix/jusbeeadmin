CREATE TABLE IF NOT EXISTS Media_Categories (
    category_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    slug VARCHAR(160) NOT NULL UNIQUE,
    parent_id BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    added_date_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_date_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_media_categories_parent (parent_id),
    INDEX idx_media_categories_updated_by (updated_by),
    CONSTRAINT fk_media_categories_parent FOREIGN KEY (parent_id) REFERENCES Media_Categories (category_id) ON DELETE SET NULL,
    CONSTRAINT fk_media_categories_updated_by FOREIGN KEY (updated_by) REFERENCES ju_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS Category_media_map (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_slug VARCHAR(160) NOT NULL,
    media_slug VARCHAR(160) NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    added_date_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_date_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_category_media_map_category_media (category_slug, media_slug),
    INDEX idx_category_media_map_media (media_slug),
    INDEX idx_category_media_map_updated_by (updated_by),
    CONSTRAINT fk_category_media_map_category FOREIGN KEY (category_slug) REFERENCES Media_Categories (slug) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_category_media_map_media FOREIGN KEY (media_slug) REFERENCES ju_library (slug) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_category_media_map_updated_by FOREIGN KEY (updated_by) REFERENCES ju_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
