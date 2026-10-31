ALTER TABLE ju_library
    ADD COLUMN title VARCHAR(255) NULL AFTER id,
    ADD COLUMN thumbnail VARCHAR(500) NULL AFTER slug,
    ADD COLUMN media_file VARCHAR(500) NULL AFTER thumbnail,
    ADD COLUMN media_type ENUM('audio', 'video') NOT NULL DEFAULT 'audio' AFTER media_file,
    ADD COLUMN play_time VARCHAR(20) NULL AFTER media_type,
    ADD COLUMN artist VARCHAR(255) NULL AFTER play_time,
    ADD COLUMN description TEXT NULL AFTER artist,
    ADD COLUMN updated_by BIGINT UNSIGNED NULL AFTER description;

UPDATE ju_library
SET title = REPLACE(REPLACE(slug, '-', ' '), '_', ' '),
    thumbnail = CASE WHEN image LIKE 'uploads/library/%' THEN image ELSE NULL END,
    media_file = CASE
        WHEN audio LIKE 'uploads/library/%' THEN audio
        WHEN video LIKE 'uploads/library/%' THEN video
        ELSE NULL
    END,
    media_type = CASE
        WHEN video LIKE 'uploads/library/%' THEN 'video'
        ELSE 'audio'
    END;

ALTER TABLE ju_library
    MODIFY COLUMN title VARCHAR(255) NOT NULL,
    DROP COLUMN youtube,
    DROP COLUMN audio,
    DROP COLUMN video,
    DROP COLUMN link,
    DROP COLUMN image,
    ADD INDEX idx_ju_library_updated_by (updated_by),
    ADD CONSTRAINT fk_ju_library_updated_by FOREIGN KEY (updated_by) REFERENCES ju_users (id) ON DELETE SET NULL;
