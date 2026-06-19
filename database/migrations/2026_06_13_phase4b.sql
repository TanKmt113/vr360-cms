-- Migration Phase 4b — chatbot. Chạy nếu DB tạo từ schema trước Phase 4b.
-- Chạy trên DB đã chọn: mysql -u<user> -p <ten_db> < 2026_06_13_phase4b.sql

CREATE TABLE IF NOT EXISTS chatbot_qa (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    tour_id   INT NOT NULL,
    question  VARCHAR(512) NOT NULL,
    answer    MEDIUMTEXT NOT NULL,
    keywords  VARCHAR(512) NULL,
    sort      INT NOT NULL DEFAULT 0,
    FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
