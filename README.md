# Đề 13 - Website Portfolio / Giới thiệu cá nhân

## 1. Thông tin sinh viên

- Họ tên: Đào Tú Uyên
- Mã sinh viên: DTC245200548
- Lớp: CNTT K23K
- Môn học: Triển khai và Quản trị Hệ thống Phần mềm

## 2. Chức năng

- Xem giới thiệu cá nhân, kỹ năng, dự án và thông tin liên hệ.
- Admin đăng nhập, đăng xuất.
- Cập nhật thông tin cá nhân.
- Thêm, sửa, xóa kỹ năng và dự án.

## 3. Công nghệ và kiến trúc

PHP, Apache, MySQL, phpMyAdmin, Nginx, Docker Compose,
Prometheus, Grafana, cAdvisor, Loki và Promtail.

Luồng truy cập website:

Trình duyệt → Nginx → PHP/Apache → MySQL.

Luồng giám sát:

- Prometheus thu thập metrics PHP, Nginx và cAdvisor.
- PHP cung cấp chỉ số kết nối database.
- Grafana hiển thị các chỉ số từ Prometheus.

Luồng log:

- Nginx và PHP ghi log vào các volume dùng chung.
- Promtail đọc log, gửi tới Loki.
- Grafana Explore truy vấn log bằng LogQL.

## 4. Chuẩn bị

- Git.
- Docker Desktop đang chạy.
- WSL Ubuntu và Docker Desktop WSL integration.
- Các lệnh dưới đây chạy trong terminal WSL tại thư mục dự án.

## 5. Tải mã nguồn và cấu hình

```bash
git clone https://github.com/tuuyengb06/portfolio-project.git
cd portfolio-project
cp .env.example .env
```

Mở `.env`, đặt mật khẩu riêng cho MySQL và Grafana theo
các biến trong file mẫu. Không đưa `.env` lên GitHub.

Các biến khởi tạo MySQL chỉ có tác dụng khi tạo database
trên volume mới. Sửa mật khẩu trong `.env` không tự đổi
mật khẩu tài khoản đã tồn tại trong MySQL.

## 6. Khởi động hệ thống

```bash
docker compose -f compose.yaml -f compose.monitoring.yaml -f compose.logging.yaml config --quiet
```

Nếu không có lỗi:

```bash
docker compose -f compose.yaml -f compose.monitoring.yaml -f compose.logging.yaml up -d --build
```

Kiểm tra:

```bash
docker compose -f compose.yaml -f compose.monitoring.yaml -f compose.logging.yaml ps
```

### Khởi tạo database lần đầu

Sau khi MySQL healthy và web đang chạy, nhập mật khẩu
admin ít nhất 12 ký tự. Khi nhập, terminal không hiển thị ký tự.

```bash
read -r -s -p "Nhap mat khau admin (it nhat 12 ky tu): " PORTFOLIO_ADMIN_PASSWORD
```

```bash
printf '%s' "$PORTFOLIO_ADMIN_PASSWORD" | docker compose exec -T web php /var/www/html/init.php
```

```bash
unset PORTFOLIO_ADMIN_PASSWORD
```

Script tạo các bảng `admins`, `profile`, `items` nếu chưa có
và thêm nội dung mẫu khi cần.

- Tên đăng nhập quản trị: `admin`.
- Mật khẩu: mật khẩu vừa nhập.
- Nếu admin đã tồn tại, script giữ nguyên mật khẩu.
- Vào trang admin để thay nội dung mẫu bằng thông tin cá nhân.

## 7. Địa chỉ truy cập

| Dịch vụ | Địa chỉ |
|---|---|
| Website | http://localhost:8090 |
| Admin | http://localhost:8090/admin.php |
| phpMyAdmin | http://localhost:8091 |
| Prometheus | http://localhost:9090 |
| Prometheus Targets | http://localhost:9090/targets |
| Grafana | http://localhost:3001 |
| Loki readiness | http://localhost:3100/ready |

phpMyAdmin: đăng nhập bằng tài khoản MySQL được cấu hình
trong `.env`, không dùng tài khoản admin của website.

Grafana: sử dụng tài khoản được cấu hình khi khởi tạo
volume Grafana lần đầu.

## 8. Giám sát với Prometheus và Grafana

Các target Prometheus:

- `prometheus:9090`
- `web:8080`, đường dẫn `/metrics.php`
- `nginx-exporter:9113`
- `cadvisor:8080`

Các truy vấn trạng thái:

```promql
up{job="portfolio-web", instance="web:8080"}
```

```promql
portfolio_database_up
```

```promql
nginx_up{job="nginx"}
```

Giá trị `1` biểu thị kiểm tra thành công.

`portfolio_database_up` chỉ phản ánh kiểm tra database
được cài đặt trong `metrics.php`; không thay thế kiểm thử
đầy đủ bảng dữ liệu và chức năng website.

### Nhập dashboard trên máy mới

1. Mở Grafana.
2. Kiểm tra nguồn dữ liệu Prometheus trỏ tới
   `http://prometheus:9090`.
3. Vào Dashboards → New → Import.
4. Tải file `monitoring/portfolio-dashboard.json`.
5. Chọn Prometheus nếu được yêu cầu, rồi Import.

Dashboard gồm trạng thái PHP, database, Nginx và
biểu đồ RAM/CPU container web.

### Hạn chế của biểu đồ CPU/RAM hiện tại

Trên môi trường triển khai này, cAdvisor cung cấp metrics
theo ID nhưng chưa lấy được tên container Docker.

Hai biểu đồ CPU/RAM hiện lọc theo ID container web.
Khi tạo lại container hoặc chạy trên máy khác, cần lấy ID mới:

```bash
docker inspect --format '{{.Id}}' portfolio-web
```

Trong hai panel CPU/RAM, thay giá trị nhãn `id` bằng
`/docker/` nối với ID đầy đủ vừa lấy, rồi lưu dashboard.

RAM được hiển thị bằng MiB.
CPU được tính theo phần trăm một lõi CPU, có thể vượt 100%
khi sử dụng nhiều lõi.

## 9. Log tập trung và LogQL

Kiểm tra Loki:

```bash
curl http://localhost:3100/ready
```

Kết quả mong đợi là `ready`. Khi mới khởi động,
Loki có thể cần thêm thời gian để sẵn sàng.

Trong Grafana, nguồn dữ liệu Loki trỏ tới:

```text
http://loki:3100
```

Vào Explore → chọn Loki → Code → chọn Last 15 minutes.

### Truy vấn 1: Xem log Nginx

```logql
{job="nginx"}
```

### Truy vấn 2: Lọc thông báo lỗi Nginx

```logql
{job="nginx"} |= "[error]"
```

Có thể kiểm tra cơ chế chặn file cấu hình bằng:

```bash
curl -I http://localhost:8090/config.php
```

Kết quả mong đợi: `403 Forbidden`.

### Truy vấn 3: Đếm dòng log trong cửa sổ 5 phút

```logql
sum(count_over_time({job="nginx"}[5m]))
```

Các truy vấn được lưu trong `logging/queries.logql`.

Promtail hiện chưa phân tích timestamp gốc trong dòng log.
Thời gian trên Loki phản ánh lúc Promtail đọc log; log cũ
được đọc lần đầu có thể xuất hiện cùng thời điểm thu nhận.

## 10. Các biện pháp hardening

- Container web chạy bằng user `www-data`, không phải root.
- Nginx thêm các security headers:
  `X-Content-Type-Options`, `X-Frame-Options`,
  `Referrer-Policy`, `Permissions-Policy`.
- Nginx chặn truy cập file ẩn, `config.php`, `init.php`
  và endpoint metrics từ phía truy cập công khai.
- Tách mạng frontend và backend; backend được cấu hình internal.
- Dịch vụ được công bố ra máy local bằng địa chỉ `127.0.0.1`.
- Mật khẩu được cấu hình qua `.env`, file này được Git bỏ qua.

cAdvisor là ngoại lệ cần quyền cao và quyền đọc thông tin
hệ thống để thu thập tài nguyên. Không mô tả toàn bộ
container trong hệ thống đều chạy non-root.

Lệnh kiểm tra:

```bash
docker compose exec web id
curl -I http://localhost:8090
curl -I http://localhost:8090/config.php
git check-ignore .env
```

## 11. Vận hành và lưu dữ liệu

MySQL, Grafana, Prometheus, Loki và vị trí đọc log của
Promtail được lưu bằng Docker volumes.

Dừng hệ thống và giữ dữ liệu:

```bash
docker compose -f compose.yaml -f compose.monitoring.yaml -f compose.logging.yaml down
```

Khởi động lại:

```bash
docker compose -f compose.yaml -f compose.monitoring.yaml -f compose.logging.yaml up -d
```

Không dùng `down -v` nếu cần giữ dữ liệu.

Sau khi sửa mã PHP, cần build lại image web:

```bash
docker compose -f compose.yaml -f compose.monitoring.yaml -f compose.logging.yaml up -d --build web
```

Nếu web được tạo lại và Nginx còn giữ địa chỉ IP cũ,
kiểm tra rồi reload Nginx:

```bash
docker compose exec nginx nginx -t
docker compose exec nginx nginx -s reload
```

Cập nhật ID trong panel CPU/RAM nếu ID web đã thay đổi.

## 12. Xử lý lỗi thường gặp

### Website trả 502

Kiểm tra trạng thái web và log Nginx:

```bash
docker compose ps web nginx
docker compose logs --tail=40 web
docker compose exec nginx tail -n 20 /var/log/nginx/error.log
```

Nếu lỗi do Nginx giữ IP cũ, reload theo mục 11.

### Website trả 503

Đọc log ứng dụng:

```bash
docker compose exec web tail -n 30 /var/log/portfolio/app.log
```

Nếu thiếu bảng `profile`, thực hiện bước khởi tạo database
ở mục 6. Không xóa volume để xử lý lỗi thiếu bảng.

### Grafana không có dữ liệu

- Chọn khoảng thời gian Last 15 minutes.
- Kiểm tra Prometheus Targets.
- Kiểm tra nguồn dữ liệu.
- Với CPU/RAM, kiểm tra ID container trong truy vấn.

## 13. Các mốc commit

- `2355e9d`: Website, database, Nginx và security headers.
- `0f1f45e`: Prometheus, Grafana và giám sát tài nguyên container.
- `a8283eb`: Loki, Promtail và ba truy vấn LogQL.

## 14. Phạm vi và hạn chế

- Hệ thống thực hành chạy local.
- Sử dụng HTTP và security headers, chưa triển khai HTTPS.
- CPU/RAM đang phụ thuộc ID container và cần cập nhật khi tạo lại.
- Log chưa phân tích timestamp gốc.
- Promtail được sử dụng theo yêu cầu đề thi; đây là thành phần
  đã hết vòng đời hỗ trợ.