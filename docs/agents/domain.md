# Tài liệu domain

Hướng dẫn để các engineering skill sử dụng tài liệu domain của repository khi khám phá codebase.

## Đọc trước khi khám phá

- Đọc `CONTEXT.md` ở root; hoặc
- Đọc `CONTEXT-MAP.md` ở root nếu file này tồn tại, rồi đọc từng `CONTEXT.md` liên quan; và
- Đọc các ADR liên quan trong `docs/adr/`. Với repository đa ngữ cảnh, cũng kiểm tra `src/<context>/docs/adr/`.

Nếu những file hoặc thư mục trên chưa tồn tại, tiếp tục làm việc bình thường: không báo thiếu và không đề nghị tạo trước. Skill `domain-modeling` sẽ tạo chúng khi dự án có thuật ngữ hoặc quyết định kiến trúc cần ghi nhận.

## Cấu trúc tài liệu

Repository này dùng layout một ngữ cảnh:

```text
/
├── CONTEXT.md
├── docs/adr/
│   ├── 0001-<quyet-dinh>.md
│   └── 0002-<quyet-dinh>.md
└── src/
```

## Dùng từ vựng của glossary

Khi đặt tên khái niệm domain trong issue, đề xuất refactor, giả thuyết hoặc test, dùng thuật ngữ định nghĩa trong `CONTEXT.md`. Không tự đổi sang từ đồng nghĩa mà glossary đã tránh dùng.

Nếu khái niệm cần dùng chưa có trong glossary, hãy cân nhắc liệu đó có phải ngôn ngữ không phù hợp với dự án hay là một khoảng trống thực sự để bổ sung qua `domain-modeling`.

## Nêu rõ xung đột ADR

Nếu đề xuất mâu thuẫn với một ADR đang có, phải nêu rõ thay vì âm thầm thay thế, ví dụ:

> Mâu thuẫn với ADR-0007, nhưng nên xem xét lại vì...
