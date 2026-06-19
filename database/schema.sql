-- =====================================================================
-- VR360 CMS — Database schema (MySQL 8 / MariaDB 10.4+)
-- Charset utf8mb4 để hỗ trợ tiếng Việt + emoji
-- =====================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Chọn database để import. DB của bạn tên 'vr360'.
-- Nếu deploy cho DB tên khác, sửa dòng USE bên dưới cho khớp.
USE vr360;

-- Xoá bảng cũ nếu có (cho phép import lại nhiều lần).
-- ⚠️ Sẽ XOÁ toàn bộ dữ liệu cũ trong các bảng này.
DROP TABLE IF EXISTS analytics, chatbot_qa, scene_permission, settings,
    materials, iframe_buttons, minimaps, audio_groups, videos, gallery,
    polygon_hotspots, hotspot_i18n, hotspots, scenes, languages, tours, admins;

-- ---------------------------------------------------------------------
-- Tài khoản quản trị
-- ---------------------------------------------------------------------
CREATE TABLE admins (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(64)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('superadmin','editor') NOT NULL DEFAULT 'editor',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Tour (multi-tenant qua id_path)
-- ---------------------------------------------------------------------
CREATE TABLE tours (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    id_path      VARCHAR(64)  NOT NULL UNIQUE,    -- vd: 20240705 (giống mẫu)
    title        VARCHAR(255) NOT NULL,
    default_lang VARCHAR(8)   NOT NULL DEFAULT 'vn',
    theme        VARCHAR(64)  NOT NULL DEFAULT 'heritage',
    bg_audio_url VARCHAR(512) NULL,               -- nhạc nền (get_meta)
    access_password_hash VARCHAR(255) NULL,       -- mật khẩu mở scene bị khoá (Phase 4)
    status       ENUM('active','draft','disabled') NOT NULL DEFAULT 'active',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Ngôn ngữ theo tour
-- ---------------------------------------------------------------------
CREATE TABLE languages (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    tour_id      INT NOT NULL,
    code         VARCHAR(8)   NOT NULL,           -- vn, en, ...
    name         VARCHAR(32)  NOT NULL,           -- VIE, ENG
    display_name VARCHAR(64)  NOT NULL,           -- Tiếng Việt
    flag_img     VARCHAR(512) NULL,
    sort         INT NOT NULL DEFAULT 0,
    UNIQUE KEY uq_lang (tour_id, code),
    FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Scene (panorama)
-- ---------------------------------------------------------------------
CREATE TABLE scenes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    tour_id    INT NOT NULL,
    name       VARCHAR(128) NOT NULL,             -- scene name nội bộ (krpano)
    title      VARCHAR(255) NOT NULL,
    thumb_url  VARCHAR(512) NULL,
    pano_url   VARCHAR(512) NULL,                 -- mẫu tiles: panos/xxx.tiles/...
    pano_multires VARCHAR(64) NOT NULL DEFAULT '512,640', -- mức tiles krpano
    hlookat    FLOAT  NOT NULL DEFAULT 0,
    vlookat    FLOAT  NOT NULL DEFAULT 0,
    fov        FLOAT  NOT NULL DEFAULT 120,
    lat        DECIMAL(10,7) NULL,
    lng        DECIMAL(10,7) NULL,
    sort       INT NOT NULL DEFAULT 0,
    UNIQUE KEY uq_scene (tour_id, name),
    FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Hotspot (điểm nóng) — khớp hotspot_data[uuid] của mẫu
-- ---------------------------------------------------------------------
CREATE TABLE hotspots (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    tour_id     INT NOT NULL,
    scene_id    INT NOT NULL,
    uuid        VARCHAR(64) NOT NULL,             -- mã hotspot
    uuid_parent VARCHAR(128) NULL,                -- scene chứa nó
    type        VARCHAR(32) NOT NULL DEFAULT 'nav', -- nav | info | media
    style       VARCHAR(64) NOT NULL DEFAULT 'default', -- default|giotnuoc|vongtron|khinhkhicau|thienvien
    style_hover VARCHAR(64) NULL,                 -- callout, ...
    ath         FLOAT NOT NULL DEFAULT 0,         -- vị trí ngang
    atv         FLOAT NOT NULL DEFAULT 0,         -- vị trí dọc
    link_scene  VARCHAR(128) NULL,                -- scene đích (nav)
    tooltip     VARCHAR(255) NULL,
    sort        INT NOT NULL DEFAULT 0,
    UNIQUE KEY uq_hs (tour_id, uuid),
    FOREIGN KEY (scene_id) REFERENCES scenes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE hotspot_i18n (
    hotspot_id INT NOT NULL,
    lang_code  VARCHAR(8) NOT NULL,
    title      VARCHAR(255) NULL,
    content    MEDIUMTEXT NULL,
    PRIMARY KEY (hotspot_id, lang_code),
    FOREIGN KEY (hotspot_id) REFERENCES hotspots(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Polygon hotspot (vùng đa giác)
-- ---------------------------------------------------------------------
CREATE TABLE polygon_hotspots (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    scene_id    INT NOT NULL,
    points_json JSON NOT NULL,                    -- [[ath,atv], ...]
    action      VARCHAR(32) NOT NULL DEFAULT 'link',
    link        VARCHAR(255) NULL,
    FOREIGN KEY (scene_id) REFERENCES scenes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Media: gallery / video / audio
-- ---------------------------------------------------------------------
CREATE TABLE gallery (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    tour_id    INT NOT NULL,
    scene_id   INT NULL,
    hotspot_id INT NULL,
    image_url  VARCHAR(512) NOT NULL,
    caption    VARCHAR(255) NULL,
    sort       INT NOT NULL DEFAULT 0,
    FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE videos (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    scene_id  INT NOT NULL,
    ath       FLOAT NOT NULL DEFAULT 0,
    atv       FLOAT NOT NULL DEFAULT 0,
    video_url VARCHAR(512) NOT NULL,
    type      VARCHAR(32) NOT NULL DEFAULT 'mp4',
    FOREIGN KEY (scene_id) REFERENCES scenes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE audio_groups (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    tour_id   INT NOT NULL,
    scene_id  INT NULL,
    audio_url VARCHAR(512) NOT NULL,
    name      VARCHAR(128) NULL,
    `loop`    TINYINT(1) NOT NULL DEFAULT 1,
    autoplay  TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Minimap / iframe / material 3D
-- ---------------------------------------------------------------------
CREATE TABLE minimaps (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    tour_id   INT NOT NULL,
    image_url VARCHAR(512) NOT NULL,
    spots_json JSON NULL,                         -- [{scene, x, y}, ...]
    FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE iframe_buttons (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    scene_id   INT NOT NULL,
    ath        FLOAT NOT NULL DEFAULT 0,
    atv        FLOAT NOT NULL DEFAULT 0,
    iframe_url VARCHAR(512) NOT NULL,
    icon       VARCHAR(512) NULL,
    FOREIGN KEY (scene_id) REFERENCES scenes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE materials (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    scene_id    INT NOT NULL,
    target      VARCHAR(128) NOT NULL,
    texture_url VARCHAR(512) NOT NULL,
    params_json JSON NULL,
    FOREIGN KEY (scene_id) REFERENCES scenes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Phân quyền scene + cấu hình tour
-- ---------------------------------------------------------------------
CREATE TABLE scene_permission (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    tour_id  INT NOT NULL,
    scene_id INT NOT NULL,
    rule     VARCHAR(64) NOT NULL DEFAULT 'login', -- login | public
    FOREIGN KEY (scene_id) REFERENCES scenes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE settings (
    tour_id    INT NOT NULL,
    `key`      VARCHAR(64) NOT NULL,
    `value`    TEXT NULL,
    PRIMARY KEY (tour_id, `key`),
    FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Chatbot: cặp hỏi-đáp (FAQ theo từ khoá)
-- ---------------------------------------------------------------------
CREATE TABLE chatbot_qa (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    tour_id   INT NOT NULL,
    question  VARCHAR(512) NOT NULL,
    answer    MEDIUMTEXT NOT NULL,
    keywords  VARCHAR(512) NULL,                  -- từ khoá phân tách bằng dấu phẩy
    sort      INT NOT NULL DEFAULT 0,
    FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Thống kê
-- ---------------------------------------------------------------------
CREATE TABLE analytics (
    id        BIGINT AUTO_INCREMENT PRIMARY KEY,
    tour_id   INT NOT NULL,
    scene_id  INT NULL,
    event     VARCHAR(64) NOT NULL,
    ip        VARBINARY(16) NULL,
    ua        VARCHAR(512) NULL,
    ts        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_an_tour (tour_id, ts)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
