# Hướng dẫn làm việc cho AutoGen

## Mục tiêu dự án

Đây là ứng dụng Laravel hỗ trợ người dùng nhập hoặc tải tài liệu yêu cầu, trích xuất và chuẩn hoá nội dung, rồi dùng AI để tạo test case.

Luồng hiện tại:

1. Người dùng gửi dữ liệu tới `POST /test-case-input`.
2. `TestCaseInputController` nhận request và gọi các service phù hợp.
3. Service trích xuất nội dung từ PDF/Word hoặc xử lý văn bản trực tiếp.
4. `TextNormalizerService` chuẩn hoá nội dung trước khi gửi cho dịch vụ tạo test case.
5. `TestCaseGeneratorService` sinh kết quả AI.

## Công nghệ và quy ước

- PHP 8.3 và Laravel 13.
- Front end dùng Vite, Tailwind CSS 4 và JavaScript.
- Test dùng PHPUnit với `php artisan test`.
- Dùng PSR-4: mã ứng dụng trong `app/`, test trong `tests/`.
- Dùng dependency injection và interface cho các service có thể thay thế hoặc mock khi test.
- Ưu tiên dùng package có sẵn của Laravel để khởi tạo feature.
- Môi trường phát triển: Windows (PowerShell). Có thể chạy local hoặc qua Docker Desktop.

## Authentication & API

- API authentication dùng Laravel Sanctum (token-based).
- Các route API cần bảo vệ phải dùng middleware `auth:sanctum`.

## Xử lý File

- Validation file (mime type, kích thước) phải đặt trong Form Request.
- Logic trích xuất nội dung từ PDF/Word đặt trong `app/Services/FileExtraction`.
- Không lưu file tạm quá lâu; xử lý xong nên dọn dẹp nếu cần.

## Bảo mật & Cấu hình nhạy cảm

- Cấu hình dịch vụ AI đặt trong `config/ai.php`.
- API key chỉ lấy từ biến môi trường (`.env`), ví dụ `OPENAI_API_KEY`, `ANTHROPIC_API_KEY`…
- Tuyệt đối không hard-code API key.
- Không log token, response thô, nội dung tài liệu hoặc dữ liệu nhạy cảm.
- Xử lý lỗi an toàn; trả về message chung cho client, không lộ chi tiết nội bộ.
- Không commit file `.env` hoặc secret.

## Cách tổ chức mã nguồn

- Controller chỉ điều phối HTTP: nhận request, gọi service, trả response; không đưa logic nghiệp vụ phức tạp vào controller.
- Validation đặt trong Form Request tại `app/Http/Requests`.
- Logic nghiệp vụ đặt trong `app/Services`; interface tương ứng đặt trong `app/Services/Contracts`.
- Logic trích xuất tệp đặt trong `app/Services/FileExtraction`.
- Đăng ký interface-to-implementation binding trong `app/Providers/AppServiceProvider.php` (hoặc provider chuyên biệt khi cần).
- Thay đổi schema phải đi kèm migration trong `database/migrations`; không sửa cấu trúc database thủ công.

## Khi thực hiện thay đổi

1. Đọc các controller, request, service, interface và test liên quan trước khi sửa.
2. Giữ thay đổi nhỏ, đúng phạm vi yêu cầu; không xoá hoặc ghi đè các thay đổi chưa commit của người dùng.
3. Khi thêm hành vi mới, bổ sung test phù hợp:
   - Unit test cho normalizer, extractor, service và các nhánh xử lý độc lập.
   - Feature test cho request validation, route, controller và luồng tích hợp.
4. Unit test **không được** ghi/sửa database thật. Phải dùng database test (cấu hình trong `phpunit.xml` / `.env.testing`) và trait như `RefreshDatabase` hoặc `DatabaseTransactions` khi cần tương tác DB. Ưu tiên mock dependency thay vì chạm DB nếu có thể.
5. Khi sửa service có interface, phải cập nhật cả interface và binding trong ServiceProvider (nếu thay đổi chữ ký method).
6. Dùng tên biến, phương thức và lớp rõ nghĩa; ưu tiên code dễ đọc cho người mới học Laravel.
7. Xử lý lỗi tải/trích xuất tệp hoặc lỗi AI một cách an toàn; không để lộ secret, API key hay nội dung nhạy cảm trong response/log.
8. Chỉ thay đổi dependency khi thực sự cần; cập nhật cả `composer.json` và `composer.lock`.

### Lưu ý unit test + DB (kể cả khi dùng Docker)

- DB test vẫn phải dùng database **test** (cấu hình trong `.env.testing` hoặc `phpunit.xml`).
- Khi chạy Docker: đảm bảo container kết nối đúng DB test (service `mysql`/`postgres` riêng, hoặc cùng server nhưng database name khác, ví dụ `app_test`).
- Trait `RefreshDatabase` dùng bình thường khi chạy `php artisan test` **bên trong** container.
- Trên Windows + Docker Desktop: không chạy test trực tiếp trên host nếu PHP/vendor chỉ có trong container.

## Kiểm tra trước khi bàn giao

Sau khi code xong (có kết quả cuối cùng), **bắt buộc** chạy lại các lệnh kiểm tra phù hợp (trên Windows dùng PowerShell).

**Nếu chạy local (không Docker):**

```powershell
php artisan test
./vendor/bin/pint --dirty
npm run build
```

**Nếu chạy bằng Docker (Docker Desktop):**

```powershell
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint --dirty
docker compose exec app npm run build
```

- Luôn chạy ít nhất test liên quan; chạy toàn bộ `php artisan test` nếu khả thi.
- **Sau khi code xong phải chạy `./vendor/bin/pint --dirty`** (hoặc qua Docker như trên) để format code PHP đã sửa. Không tự động sửa toàn bộ code không liên quan chỉ để format.
- Chạy `npm run build` khi có thay đổi front end hoặc cấu hình Vite/Tailwind.
- Báo rõ lệnh nào đã chạy, kết quả, và những phần chưa thể kiểm chứng.
- Nếu tên service Docker khác (`php`, `laravel`…), dùng đúng tên service trong project.
- Nếu dùng `docker-compose` (bản cũ) thay vì `docker compose`, hãy thay cho đúng lệnh trên máy bạn.

## Cách giao tiếp với Kim

- Trả lời bằng tiếng Việt, trừ khi được yêu cầu dùng ngôn ngữ khác.
- Giải thích theo trình tự từng bước, phù hợp với lập trình viên fresher.
- Khi đưa code, giải thích đoạn code làm gì và vì sao chọn cách đó.
- Với thông tin chưa chắc chắn, nêu rõ giới hạn thay vì suy đoán.
- Khi không chắc chắn về yêu cầu, hãy hỏi lại trước khi viết code lớn.
- Khi báo cáo hoàn thành, tóm tắt file đã đổi, hành vi thay đổi và kết quả kiểm thử.

## Agent skills

### Issue tracker

Các issue được theo dõi trên GitHub Issues của repository này. Xem `docs/agents/issue-tracker.md`.

### Domain docs

Đây là repository một ngữ cảnh (single-context). Xem `docs/agents/domain.md`.
