-- Migration Phase 5b — multires riêng cho mỗi scene (phục vụ import tour thật).
-- Chạy trên DB đã chọn: mysql -u<user> -p <ten_db> < 2026_06_13_phase5b.sql

ALTER TABLE scenes
    ADD COLUMN pano_multires VARCHAR(64) NOT NULL DEFAULT '512,640' AFTER pano_url;
