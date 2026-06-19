# VR360 CMS Test

Hệ thống quản trị tour ảo 360° (PHP + MySQL), kiến trúc mô phỏng `managements` của StarGlobal3D.
**Trạng thái: đầy đủ tính năng** (Phase 0→5). Lộ trình & nhật ký ở `../KE-HOACH.md`.

---

## Tính năng

**Quản trị (admin)**
- Đa tour (multi-tenant qua `id_path`)
- Scene: CRUD, upload pano (ảnh equirect hoặc **tiles ZIP** từ krpano Tools)
- Hotspot: CRUD + **đặt trực quan** (click trên panorama lấy ath/atv) + đa style
- Media: gallery (nhiều ảnh), audio, video
- Tương tác: iframe button, **polygon** (vẽ trực quan nhiều điểm), material (đổi texture)
- Minimap: bản đồ + đặt điểm scene trực quan (2D)
- Cấu hình & **Autotour**, gyro/webvr/little-planet
- **Chatbot** FAQ theo từ khoá (tiếng Việt)
- **Đa ngôn ngữ**: quản lý ngôn ngữ + dịch nội dung hotspot
- **Phân quyền**: khoá scene + mật khẩu truy cập tour
- **Thống kê**: dashboard lượt xem theo scene/ngày

**Viewer (công khai)**
- krpano + `tour.xml` sinh động từ DB
- API JSON khớp định dạng mẫu (`get_config`, `get_meta`, `php_data_json_by_scenename`, `get_language`, `iframe_button_list`, `get_gallery_image`, `get_scene_name`, `analytic/post`, `chatbot/ask`, `tour_login`)

---

## Yêu cầu
- PHP 8.0+ với `pdo_mysql`. (Khuyến nghị có `mbstring`, `fileinfo`, `zip` — nếu thiếu `mbstring` đã có polyfill tiếng Việt; thiếu `zip` thì không dùng được upload tiles ZIP.)
- MySQL 8 / MariaDB 10.4+

## Cài đặt
```bash
cp config/.env.example config/.env          # điền DB_*
mysql -u root -p < database/schema.sql
mysql -u root -p < database/seed.sql
php -S localhost:8000                         # hoặc cấu hình Apache/Nginx
```
Nâng cấp DB cũ: chạy thêm các file trong `database/migrations/`.

## Truy cập
| URL | Mô tả |
|---|---|
| `/admin/index.php` | Đăng nhập (**admin / admin123** — đổi ngay!) |
| `/public/viewer.php?id=20240705` | Viewer tour |
| `/user/includes/get_config.php?id=20240705` | Config JSON |

---

## Triển khai production

**Apache**: đã có sẵn `.htaccess` (gốc + `upload/`). Cần bật `mod_rewrite`, `mod_headers`. Đặt `DocumentRoot` vào thư mục `vr360-cms`.

**Nginx**: KHÔNG đọc `.htaccess` — dùng `nginx.conf.example` (đã chặn `config/core/database/storage`, chặn PHP trong `/upload`, set security headers).

**Checklist bảo mật khi go-live:**
1. Đổi mật khẩu admin: `php tools/make_hash.php 'matkhau-moi'` → `UPDATE admins ...`
2. Đổi `APP_SECRET` trong `.env`
3. Bật HTTPS (session cookie tự set `Secure` khi có HTTPS)
4. Đảm bảo `config/`, `core/`, `database/`, `storage/` **không** truy cập được từ web (test: mở `/config/.env` phải ra 403)
5. Đảm bảo `/upload/` không chạy được PHP (test: upload `.php` rồi gọi — phải 403/plain text)
6. Cấp quyền ghi cho `upload/` và `storage/` (vd `www-data`)

---

## Bảo mật đã tích hợp
- PDO prepared statements toàn bộ (chống SQL injection)
- CSRF token cho mọi form ghi trong admin
- Rate limit: đăng nhập admin (8/5'), tour_login (10/5'), chatbot (30/'), analytic (120/')
- Upload: kiểm tra đuôi + **MIME thực tế** (finfo) + tên file ngẫu nhiên + thư mục upload cấm thực thi
- Giải nén tiles ZIP có chống **zip-slip**
- Session HttpOnly + SameSite=Lax (+ Secure khi HTTPS)
- Security headers (nosniff, frame-options, referrer-policy)

## Cấu trúc
```
config/   — .env, hằng số
core/     — db, response, auth, i18n, upload, security, ratelimit, mbcompat
database/ — schema.sql, seed.sql, migrations/
admin/    — panel + modules/ + api/
user/, user_FE/ — API đọc cho viewer (khớp định dạng mẫu)
public/   — viewer.php, tour_xml.php, vtour/ (skin krpano)
upload/   — panos, image, audio (cấm thực thi PHP)
storage/  — ratelimit (cấm truy cập web)
tools/    — make_hash.php
```

> ⚖️ Bản quyền: FE tham khảo từ StarGlobal3D là sản phẩm thương mại; **krpano cần license riêng**.
