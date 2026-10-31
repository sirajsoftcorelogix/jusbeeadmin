UPDATE ju_app_users
SET first_name = COALESCE(NULLIF(first_name, ''), SUBSTRING_INDEX(TRIM(name), ' ', 1)),
    last_name = COALESCE(NULLIF(last_name, ''), TRIM(SUBSTRING(TRIM(name), LENGTH(SUBSTRING_INDEX(TRIM(name), ' ', 1)) + 1)), '');

ALTER TABLE ju_app_users
    DROP COLUMN name;
