-- =====================================================================
-- Dữ liệu demo: 1 admin, 1 tour, 2 ngôn ngữ, 1 scene, 1 hotspot
-- Admin đăng nhập:  admin / admin123
-- (Hash bên dưới do password_hash() sinh; nếu cần đổi, dùng tools/make_hash.php)
-- DB của bạn tên 'vr360'. Nếu deploy cho DB tên khác, sửa dòng USE bên dưới.
-- =====================================================================
USE vr360;

INSERT INTO admins (username, password_hash, role) VALUES
('admin', '$2y$10$UIvg4hzqvcAOC41pU0JcZOVU5cjOXT69f/eUeCLXH6Y4laafc4zTm', 'superadmin');

INSERT INTO tours (id_path, title, default_lang, theme, bg_audio_url, status) VALUES
('20240705', 'Dinh Độc Lập (demo)', 'vn', 'heritage', '/upload/audio/summertime-low.mp3', 'active');

SET @tour := LAST_INSERT_ID();

INSERT INTO languages (tour_id, code, name, display_name, flag_img, sort) VALUES
(@tour, 'vn', 'VIE', 'Tiếng Việt', 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/21/Flag_of_Vietnam.svg/1200px-Flag_of_Vietnam.svg.png', 1),
(@tour, 'en', 'ENG', 'English',    'https://upload.wikimedia.org/wikipedia/commons/thumb/a/ae/Flag_of_the_United_Kingdom.svg/1200px-Flag_of_the_United_Kingdom.svg.png', 2);

-- pano_url là đường dẫn tiles TƯƠNG ĐỐI trong /upload/panos/<id_path>/
INSERT INTO scenes (tour_id, name, title, thumb_url, pano_url, hlookat, vlookat, fov, sort) VALUES
(@tour, 'scene_phong_trinh_quoc_thu', 'Phòng Trình Quốc Thư',
 'phong_trinh_quoc_thu.tiles/thumb.jpg',
 'phong_trinh_quoc_thu.tiles/%s/l%l/%v/l%l_%s_%v_%h.jpg',
 0, 0, 120, 1);

SET @scene := LAST_INSERT_ID();

INSERT INTO hotspots (tour_id, scene_id, uuid, uuid_parent, type, style, style_hover, ath, atv, link_scene, tooltip, sort) VALUES
(@tour, @scene, 'hs_to_working_room', 'scene_phong_trinh_quoc_thu', 'nav', 'default', 'callout', 35.0, 2.0, 'scene_phong_lam_viec', 'Sang phòng làm việc', 1);

INSERT INTO hotspot_i18n (hotspot_id, lang_code, title, content) VALUES
(LAST_INSERT_ID(), 'vn', 'Phòng làm việc của Tổng thống', 'Mô tả tiếng Việt...'),
(LAST_INSERT_ID(), 'en', 'President working room', 'English description...');

INSERT INTO settings (tour_id, `key`, `value`) VALUES
(@tour, 'gyro', 'true'),
(@tour, 'webvr', 'true'),
(@tour, 'autotour', 'false');
