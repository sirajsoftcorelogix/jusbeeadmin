ALTER TABLE ju_app_users
    ADD COLUMN first_name VARCHAR(120) NULL AFTER name,
    ADD COLUMN last_name VARCHAR(120) NULL AFTER first_name,
    ADD COLUMN profile_thumbnail VARCHAR(255) NULL AFTER password,
    ADD COLUMN device_id VARCHAR(255) NULL AFTER profile_thumbnail,
    ADD COLUMN current_stage VARCHAR(120) NULL AFTER device_id,
    ADD COLUMN birth_date DATE NULL AFTER current_stage,
    ADD COLUMN reduce_stress TINYINT(1) NOT NULL DEFAULT 0 AFTER birth_date,
    ADD COLUMN sleep_better TINYINT(1) NOT NULL DEFAULT 0 AFTER reduce_stress,
    ADD COLUMN build_heithy_habits TINYINT(1) NOT NULL DEFAULT 0 AFTER sleep_better,
    ADD COLUMN journal_throughts TINYINT(1) NOT NULL DEFAULT 0 AFTER build_heithy_habits,
    ADD COLUMN find_professional_support TINYINT(1) NOT NULL DEFAULT 0 AFTER journal_throughts;
