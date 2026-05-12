# Cicafood Core Plugin — Hướng dẫn Setup

## Sau khi cài đặt WordPress xong, làm theo thứ tự:

### Bước 1 — Kích hoạt Theme
WP Admin → Appearance → Themes → Activate **Cicafood**

### Bước 2 — Kích hoạt Plugin
WP Admin → Plugins → Activate **Cicafood Core**
→ Plugin sẽ tự tạo 9 custom tables + insert sample data

### Bước 3 — Tạo các Pages với Shortcodes

| Tên Page | Slug | Shortcode |
|---|---|---|
| Trang chủ | / | (dùng front-page.php tự động) |
| Nhà hàng | nha-hang | `[cicafood_restaurants]` |
| Thanh toán | thanh-toan | `[cicafood_checkout]` |
| Đơn hàng | don-hang | `[cicafood_orders]` |
| Hồ sơ | ho-so | `[cicafood_profile]` |
| Voucher | voucher | `[cicafood_vouchers]` |
| Yêu thích | yeu-thich | `[cicafood_favorites]` |

### Bước 4 — Cài đặt Reading
WP Admin → Settings → Reading → Set "Your homepage displays" = **A static page** → chọn trang chủ

### Bước 5 — Permalinks
WP Admin → Settings → Permalinks → Post name → Save

### Bước 6 — Kiểm tra
- Vào http://localhost/cicafood/wordpress/ → thấy trang chủ Cicafood
- Vào /nha-hang/ → thấy danh sách nhà hàng
- Vào /wp-admin/admin.php?page=cicafood → thấy dashboard Cicafood

## Tài khoản test (từ sample data)
- Admin: admin@cicafood.vn / Password123
- Customer: an@gmail.com / Password123
