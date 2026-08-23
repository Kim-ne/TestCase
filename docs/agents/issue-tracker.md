# Issue tracker: GitHub

Issue và đặc tả của repository này được quản lý bằng GitHub Issues. Dùng CLI `gh` cho mọi thao tác.

## Quy ước

- **Tạo issue:** `gh issue create --title "..." --body "..."`. Dùng heredoc cho nội dung nhiều dòng.
- **Đọc issue:** `gh issue view <number> --comments`; khi cần, lọc comment bằng `jq` và lấy cả nhãn.
- **Liệt kê issue:** `gh issue list --state open --json number,title,body,labels,comments --jq '[.[] | {number, title, body, labels: [.labels[].name], comments: [.comments[].body]}]'`, kết hợp `--label` và `--state` phù hợp.
- **Bình luận vào issue:** `gh issue comment <number> --body "..."`.
- **Thêm hoặc gỡ nhãn:** `gh issue edit <number> --add-label "..."` hoặc `gh issue edit <number> --remove-label "..."`.
- **Đóng issue:** `gh issue close <number> --comment "..."`.

Suy ra repository từ `git remote -v`; khi chạy trong clone này, `gh` tự nhận diện repository.

## Pull request là nguồn triage

**PR là nguồn yêu cầu: không.** Nếu sau này repository xem PR bên ngoài là yêu cầu tính năng, đổi giá trị này thành `có`.

Khi đặt là `có`, PR sẽ dùng cùng nhãn và trạng thái với issue, bằng các lệnh `gh pr` tương ứng:

- **Đọc PR:** `gh pr view <number> --comments` và `gh pr diff <number>`.
- **Liệt kê PR bên ngoài để triage:** `gh pr list --state open --json number,title,body,labels,author,authorAssociation,comments`; chỉ giữ tác giả có `authorAssociation` là `CONTRIBUTOR`, `FIRST_TIME_CONTRIBUTOR` hoặc `NONE`.
- **Bình luận, gắn nhãn, đóng PR:** `gh pr comment`, `gh pr edit --add-label`/`--remove-label`, `gh pr close`.

GitHub dùng chung không gian số cho issue và PR. Một tham chiếu như `#42` có thể là một trong hai; kiểm tra bằng `gh pr view 42`, sau đó dùng `gh issue view 42` nếu không phải PR.

## Khi skill yêu cầu thao tác với issue tracker

- Khi cần **đăng lên issue tracker**, tạo một GitHub issue.
- Khi cần **lấy ticket liên quan**, chạy `gh issue view <number> --comments`.

## Thao tác wayfinding

Skill `/wayfinder` dùng một issue làm **bản đồ** và các issue con làm ticket:

- **Bản đồ:** một issue mang nhãn `wayfinder:map`, chứa Notes / Decisions-so-far / Fog; tạo bằng `gh issue create --label wayfinder:map`.
- **Ticket con:** liên kết với bản đồ dưới dạng GitHub sub-issue. Nếu sub-issue chưa khả dụng, dùng task list trong bản đồ và thêm `Part of #<map>` ở đầu ticket. Nhãn là `wayfinder:<type>` (`research`, `prototype`, `grilling`, `task`).
- **Phụ thuộc:** ưu tiên GitHub native issue dependencies. Nếu không khả dụng, thêm `Blocked by: #<n>, #<n>` ở đầu ticket.
- **Claim:** `gh issue edit <n> --add-assignee @me` là thao tác ghi đầu tiên.
- **Hoàn tất:** bình luận kết quả, đóng ticket, rồi thêm liên kết context vào phần Decisions-so-far của bản đồ.
