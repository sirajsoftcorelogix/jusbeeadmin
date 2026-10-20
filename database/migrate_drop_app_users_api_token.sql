-- Run once if ju_app_users was created from the earlier version that had api_token.
ALTER TABLE ju_app_users DROP INDEX idx_ju_app_users_token, DROP COLUMN api_token;
