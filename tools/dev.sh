#!/usr/bin/env bash
# Khởi động server dev cho VR360 CMS (chạy ngay trong thư mục project).
# Sửa file là refresh thấy ngay, KHÔNG cần upload lên aaPanel.
cd "$(dirname "$0")/.."
PORT="${1:-8000}"
echo "▶ VR360 CMS dev: http://localhost:$PORT"
echo "  Admin : http://localhost:$PORT/admin/index.php"
echo "  Viewer: http://localhost:$PORT/public/viewer.php?id=20240705"
echo "  (Ctrl+C để dừng)"
# Cho phép upload ZIP tour lớn (post_max_size / upload_max_filesize = 2G)
php -d post_max_size=2G -d upload_max_filesize=2G -d memory_limit=512M \
    -d max_execution_time=600 -d max_input_time=600 \
    -S "localhost:$PORT" -t .
