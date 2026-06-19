-- Migration Phase 4 — chạy nếu DB đã tạo từ trước (schema.sql cũ).
-- Cài mới (schema.sql) đã có sẵn cột này, không cần chạy.
-- Chạy trên DB đã chọn: mysql -u<user> -p <ten_db> < 2026_06_13_phase4.sql

ALTER TABLE tours
    ADD COLUMN access_password_hash VARCHAR(255) NULL AFTER bg_audio_url;
