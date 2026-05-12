#!/usr/bin/env python3
"""
Cicafood Analytics — Python Script phân tích dữ liệu đơn hàng
Tích hợp đa ngôn ngữ PHP + Python (tiêu chí e5)

Sử dụng:
    python analytics.py                    # Phân tích tất cả nhà hàng
    python analytics.py --restaurant 1     # Phân tích nhà hàng ID=1
    python analytics.py --export report    # Xuất file báo cáo Excel (.xlsx)

Yêu cầu:
    pip install mysql-connector-python openpyxl matplotlib
"""

import sys
import os
import argparse
from datetime import datetime, timedelta, timezone

# Fix Windows console encoding
if sys.platform == 'win32':
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')

try:
    import mysql.connector
except ImportError:
    print("Cần cài: pip install mysql-connector-python")
    sys.exit(1)

# ============================================================
# CẤU HÌNH DATABASE
# ============================================================
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'cicafood_wp',
    'charset': 'utf8mb4',
}

TABLE_PREFIX = 'wp_'
VN_TIMEZONE = timezone(timedelta(hours=7))


def vn_now():
    """Return current Vietnam time (GMT+7)."""
    return datetime.now(VN_TIMEZONE)


def format_vn_datetime(value):
    """Format DB datetimes consistently for reports."""
    if isinstance(value, datetime):
        return value.strftime('%d/%m/%Y %H:%M')
    return str(value or '')


def get_connection():
    """Kết nối MySQL"""
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        return conn
    except mysql.connector.Error as e:
        print(f"❌ Lỗi kết nối DB: {e}")
        sys.exit(1)


def analyze_all(conn):
    """Phân tích tổng quan tất cả nhà hàng"""
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SET time_zone = '+07:00'")
    rt = f"{TABLE_PREFIX}cica_restaurants"
    ot = f"{TABLE_PREFIX}cica_orders"
    mi = f"{TABLE_PREFIX}cica_menu_items"

    print("=" * 60)
    print("🍜 CICAFOOD ANALYTICS — BÁO CÁO TỔNG QUAN")
    print(f"📅 Ngày: {vn_now().strftime('%d/%m/%Y %H:%M:%S')}")
    print("=" * 60)

    # Tổng quan hệ thống
    cursor.execute(f"SELECT COUNT(*) as cnt FROM {rt}")
    total_rests = cursor.fetchone()['cnt']

    cursor.execute(f"SELECT COUNT(*) as cnt FROM {ot}")
    total_orders = cursor.fetchone()['cnt']

    cursor.execute(f"SELECT COALESCE(SUM(total_amount), 0) as rev FROM {ot} WHERE status='completed'")
    total_revenue = int(cursor.fetchone()['rev'])

    cursor.execute(f"SELECT COUNT(*) as cnt FROM {mi}")
    total_items = cursor.fetchone()['cnt']

    print(f"\n📊 TỔNG QUAN HỆ THỐNG:")
    print(f"   🏪 Nhà hàng: {total_rests}")
    print(f"   📦 Tổng đơn hàng: {total_orders}")
    print(f"   💰 Tổng doanh thu: {total_revenue:,.0f}đ")
    print(f"   🍽️  Món ăn: {total_items}")

    # Top nhà hàng theo doanh thu
    cursor.execute(f"""
        SELECT r.name, 
               COUNT(o.id) as total_orders,
               COALESCE(SUM(CASE WHEN o.status='completed' THEN o.total_amount ELSE 0 END), 0) as revenue,
               r.rating, r.total_reviews
        FROM {rt} r
        LEFT JOIN {ot} o ON r.id = o.restaurant_id
        GROUP BY r.id
        ORDER BY revenue DESC
        LIMIT 10
    """)
    restaurants = cursor.fetchall()

    print(f"\n🏆 TOP NHÀ HÀNG THEO DOANH THU:")
    print(f"{'STT':<5}{'Tên quán':<30}{'Đơn hàng':<12}{'Doanh thu':<18}{'Rating':<10}")
    print("-" * 75)
    for i, r in enumerate(restaurants, 1):
        rev = int(r['revenue'])
        print(f"{i:<5}{r['name'][:28]:<30}{r['total_orders']:<12}{rev:>14,}đ   ⭐{r['rating']}")

    # Thống kê theo trạng thái đơn hàng
    cursor.execute(f"""
        SELECT status, COUNT(*) as cnt, COALESCE(SUM(total_amount), 0) as total
        FROM {ot} GROUP BY status ORDER BY cnt DESC
    """)
    statuses = cursor.fetchall()
    status_labels = {
        'pending': '⏳ Chờ xác nhận',
        'confirmed': '✅ Đã xác nhận',
        'preparing': '🍳 Đang chuẩn bị',
        'delivering': '🚚 Đang giao',
        'completed': '✅ Hoàn thành',
        'cancelled': '❌ Đã hủy',
    }

    print(f"\n📋 THỐNG KÊ THEO TRẠNG THÁI:")
    for s in statuses:
        label = status_labels.get(s['status'], s['status'])
        total = int(s['total'])
        print(f"   {label}: {s['cnt']} đơn — {total:,}đ")

    # Doanh thu 7 ngày gần nhất
    print(f"\n📈 DOANH THU 7 NGÀY GẦN NHẤT:")
    for i in range(6, -1, -1):
        date = (vn_now() - timedelta(days=i)).strftime('%Y-%m-%d')
        date_label = (vn_now() - timedelta(days=i)).strftime('%d/%m')
        cursor.execute(f"""
            SELECT COALESCE(SUM(total_amount), 0) as rev, COUNT(*) as cnt
            FROM {ot} WHERE status='completed' AND DATE(created_at) = %s
        """, (date,))
        row = cursor.fetchone()
        rev = int(row['rev'])
        bar = '█' * min(int(rev / 10000), 40) if rev > 0 else '░'
        print(f"   {date_label} | {bar} {rev:>12,}đ ({row['cnt']} đơn)")

    print("\n" + "=" * 60)
    cursor.close()


def analyze_restaurant(conn, rest_id):
    """Phân tích chi tiết 1 nhà hàng"""
    cursor = conn.cursor(dictionary=True)
    rt = f"{TABLE_PREFIX}cica_restaurants"
    ot = f"{TABLE_PREFIX}cica_orders"
    mi = f"{TABLE_PREFIX}cica_menu_items"

    cursor.execute(f"SELECT * FROM {rt} WHERE id = %s", (rest_id,))
    rest = cursor.fetchone()
    if not rest:
        print(f"❌ Không tìm thấy nhà hàng ID={rest_id}")
        return

    print("=" * 60)
    print(f"🏪 PHÂN TÍCH: {rest['name']}")
    print(f"📍 {rest['address']}, {rest['province']}")
    print(f"⭐ Rating: {rest['rating']} ({rest['total_reviews']} đánh giá)")
    print("=" * 60)

    # Top món bán chạy
    cursor.execute(f"""
        SELECT name, price, is_best_seller, is_available
        FROM {mi} WHERE restaurant_id = %s
        ORDER BY is_best_seller DESC, price DESC
    """, (rest_id,))
    items = cursor.fetchall()

    print(f"\n🍽️  DANH SÁCH MÓN ({len(items)} món):")
    for item in items:
        status = "🔥" if item['is_best_seller'] else ("✅" if item['is_available'] else "⏸️")
        print(f"   {status} {item['name'][:30]:<32} {int(item['price']):>10,}đ")

    cursor.close()


def export_report(conn, filename='report'):
    """Xuất báo cáo ra file Excel (.xlsx)"""
    try:
        from openpyxl import Workbook
        from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
    except ImportError:
        print("Cần cài: pip install openpyxl")
        return

    cursor = conn.cursor(dictionary=True)
    cursor.execute("SET time_zone = '+07:00'")
    ot = f"{TABLE_PREFIX}cica_orders"
    rt = f"{TABLE_PREFIX}cica_restaurants"

    wb = Workbook()
    ws = wb.active
    ws.title = "Đơn hàng"

    # Styles
    header_font = Font(bold=True, color="FFFFFF", size=12)
    header_fill = PatternFill(start_color="EE2624", end_color="EE2624", fill_type="solid")
    title_font = Font(bold=True, size=14, color="EE2624")
    border = Border(
        left=Side(style='thin'), right=Side(style='thin'),
        top=Side(style='thin'), bottom=Side(style='thin')
    )

    # Title
    ws.merge_cells('A1:M1')
    ws['A1'] = '🍜 CICAFOOD — BÁO CÁO ĐƠN HÀNG'
    ws['A1'].font = title_font
    ws['A1'].alignment = Alignment(horizontal='center')

    ws.merge_cells('A2:M2')
    ws['A2'] = f'Ngày xuất: {vn_now().strftime("%d/%m/%Y %H:%M:%S")}'
    ws['A2'].alignment = Alignment(horizontal='center')

    # Headers
    headers = ['STT', 'Mã đơn', 'Nhà hàng', 'Người nhận', 'SĐT', 'Địa chỉ',
               'Tổng tiền', 'Phí ship', 'Giảm giá', 'PT Thanh toán', 'Trạng thái', 'Ghi chú', 'Ngày đặt']

    for col, header in enumerate(headers, 1):
        cell = ws.cell(row=4, column=col, value=header)
        cell.font = header_font
        cell.fill = header_fill
        cell.alignment = Alignment(horizontal='center')
        cell.border = border

    # Data
    cursor.execute(f"""
        SELECT o.*, r.name as restaurant_name
        FROM {ot} o LEFT JOIN {rt} r ON o.restaurant_id = r.id
        ORDER BY o.created_at DESC
    """)
    orders = cursor.fetchall()

    status_labels = {
        'pending': 'Chờ xác nhận', 'confirmed': 'Đã xác nhận',
        'preparing': 'Đang chuẩn bị', 'delivering': 'Đang giao',
        'completed': 'Hoàn thành', 'cancelled': 'Đã hủy',
    }
    payment_labels = {'cod': 'COD', 'bank_transfer': 'Chuyển khoản QR'}

    for i, o in enumerate(orders, 1):
        row = i + 4
        data = [
            i, o['order_code'], o.get('restaurant_name', ''),
            o['recipient_name'], str(o.get('recipient_phone') or ''), o['delivery_address'],
            int(o['total_amount']), int(o['delivery_fee']), int(o.get('discount_amount', 0)),
            payment_labels.get(o['payment_method'], o['payment_method']),
            status_labels.get(o['status'], o['status']),
            o.get('note', ''),
            format_vn_datetime(o.get('created_at')),
        ]
        for col, val in enumerate(data, 1):
            cell = ws.cell(row=row, column=col, value=val)
            cell.border = border
            if col in (7, 8, 9):
                cell.number_format = '#,##0'
                cell.alignment = Alignment(horizontal='right', vertical='top')
            elif col == 5:
                cell.number_format = '@'
                cell.alignment = Alignment(horizontal='left', vertical='top')
            elif col in (6, 12):
                cell.alignment = Alignment(wrap_text=True, vertical='top')
            else:
                cell.alignment = Alignment(vertical='top')

    # Excel-friendly widths: fit normal columns, cap long text and wrap it.
    max_widths = {
        1: 8, 2: 18, 3: 28, 4: 24, 5: 16, 6: 45, 7: 16,
        8: 14, 9: 14, 10: 20, 11: 18, 12: 35, 13: 20,
    }
    min_widths = {
        1: 6, 2: 12, 3: 14, 4: 14, 5: 13, 6: 22, 7: 12,
        8: 10, 9: 10, 10: 13, 11: 13, 12: 14, 13: 16,
    }
    for col in ws.columns:
        try:
            col_letter = col[0].column_letter
            col_index = col[0].column
        except AttributeError:
            continue
        max_len = max(len(str(cell.value or '')) for cell in col if cell.value)
        width = max(min_widths.get(col_index, 10), min(max_len + 2, max_widths.get(col_index, 30)))
        ws.column_dimensions[col_letter].width = width

    ws.freeze_panes = 'A5'
    ws.auto_filter.ref = f"A4:M{max(len(orders) + 4, 4)}"

    # Save
    filepath = os.path.join(os.path.dirname(__file__), f'{filename}.xlsx')
    wb.save(filepath)
    print(f"✅ Đã xuất báo cáo: {filepath}")

    cursor.close()


def main():
    parser = argparse.ArgumentParser(description='🍜 Cicafood Analytics — Phân tích dữ liệu đơn hàng')
    parser.add_argument('--restaurant', '-r', type=int, help='ID nhà hàng cần phân tích')
    parser.add_argument('--export', '-e', type=str, help='Xuất báo cáo Excel (tên file)')
    
    # DB arguments
    parser.add_argument('--host', type=str, help='MySQL Host')
    parser.add_argument('--user', type=str, help='MySQL User')
    parser.add_argument('--password', type=str, help='MySQL Password')
    parser.add_argument('--db', type=str, help='MySQL Database Name')
    parser.add_argument('--prefix', type=str, help='Table Prefix')
    
    args = parser.parse_args()

    # Update config from args if provided
    if args.host: DB_CONFIG['host'] = args.host
    if args.user: DB_CONFIG['user'] = args.user
    if args.password is not None: DB_CONFIG['password'] = args.password
    if args.db: DB_CONFIG['database'] = args.db
    
    global TABLE_PREFIX
    if args.prefix: TABLE_PREFIX = args.prefix

    conn = get_connection()

    if args.export:
        export_report(conn, args.export)
    elif args.restaurant:
        analyze_restaurant(conn, args.restaurant)
    else:
        analyze_all(conn)

    conn.close()


if __name__ == '__main__':
    main()
