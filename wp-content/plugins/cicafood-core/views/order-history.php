<?php
defined('ABSPATH') || exit;
if (!session_id()) session_start();

$user_id  = get_current_user_id();
$site_url = get_site_url();
$ajax_url = admin_url('admin-ajax.php');
$nonce    = wp_create_nonce('cicafood_nonce');
$orders   = Cicafood_Order::get_by_user($user_id, 20);

$status_colors = [
    'pending'    => ['bg' => '#fef9c3', 'text' => '#854d0e', 'icon' => 'fa-clock'],
    'confirmed'  => ['bg' => '#dbeafe', 'text' => '#1e40af', 'icon' => 'fa-check-circle'],
    'preparing'  => ['bg' => '#ffedd5', 'text' => '#9a3412', 'icon' => 'fa-fire-flame-curved'],
    'delivering' => ['bg' => '#f3e8ff', 'text' => '#6b21a8', 'icon' => 'fa-motorcycle'],
    'completed'  => ['bg' => '#dcfce7', 'text' => '#166534', 'icon' => 'fa-circle-check'],
    'cancelled'  => ['bg' => '#fee2e2', 'text' => '#991b1b', 'icon' => 'fa-circle-xmark'],
];
?>

<div style="max-width:760px;margin:0 auto;padding:1.5rem 1rem">
    <h1 style="font-size:1.4rem;font-weight:900;color:#111827;margin:0 0 1.5rem;display:flex;align-items:center;gap:0.5rem">
        <i class="fas fa-receipt" style="color:#ee2624"></i> Đơn hàng của tôi
    </h1>

    <?php if (empty($orders)): ?>
    <div style="background:white;border-radius:16px;border:1px solid #e5e7eb;padding:3rem;text-align:center;color:#9ca3af">
        <i class="fas fa-box-open" style="font-size:3rem;margin-bottom:1rem;display:block"></i>
        <p style="font-size:1rem;font-weight:600;margin:0 0 1rem">Bạn chưa có đơn hàng nào</p>
        <a href="<?= esc_url($site_url) ?>"
           style="display:inline-block;background:#ee2624;color:white;padding:0.75rem 1.5rem;border-radius:10px;font-weight:700;text-decoration:none;font-size:0.9rem">
            Đặt món ngay
        </a>
    </div>
    <?php else: ?>

    <div style="display:flex;flex-direction:column;gap:1rem">
        <?php foreach ($orders as $order):
            $s = $status_colors[$order['status']] ?? ['bg' => '#f3f4f6', 'text' => '#374151', 'icon' => 'fa-question'];
            $items = Cicafood_Order::get_items((int)$order['id']);
            $item_names = implode(', ', array_map(fn($i) => $i['quantity'] . 'x ' . $i['item_name'], $items));
        ?>
        <div style="background:white;border-radius:16px;border:1px solid #e5e7eb;padding:1.25rem;transition:box-shadow 0.2s"
             onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.08)'"
             onmouseout="this.style.boxShadow='none'">

            <!-- Header row -->
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:0.75rem">
                <div style="display:flex;align-items:center;gap:0.75rem">
                    <?php if ($order['restaurant_image']): ?>
                    <img src="<?= esc_url($order['restaurant_image']) ?>" alt=""
                         style="width:44px;height:44px;border-radius:10px;object-fit:cover;flex-shrink:0">
                    <?php else: ?>
                    <div style="width:44px;height:44px;border-radius:10px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i class="fas fa-store" style="color:#9ca3af"></i>
                    </div>
                    <?php endif; ?>
                    <div>
                        <p style="font-weight:700;font-size:0.95rem;color:#111827;margin:0"><?= esc_html($order['restaurant_name']) ?></p>
                        <p style="font-size:0.75rem;color:#9ca3af;margin:0.15rem 0 0">
                            <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                            · <span style="font-weight:600;color:#6b7280"><?= esc_html($order['order_code']) ?></span>
                        </p>
                    </div>
                </div>
                <span style="background:<?= $s['bg'] ?>;color:<?= $s['text'] ?>;padding:4px 12px;border-radius:999px;font-weight:700;font-size:0.75rem;white-space:nowrap;flex-shrink:0">
                    <i class="fas <?= $s['icon'] ?>"></i>
                    <?= esc_html(Cicafood_Order::status_label($order['status'])['label']) ?>
                </span>
            </div>

            <!-- Items preview -->
            <?php if ($item_names): ?>
            <p style="font-size:0.8rem;color:#6b7280;margin:0 0 0.75rem;padding:0.5rem 0.75rem;background:#f9fafb;border-radius:8px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                <i class="fas fa-utensils" style="margin-right:0.4rem;color:#d1d5db"></i><?= esc_html($item_names) ?>
            </p>
            <?php endif; ?>

            <!-- Footer row -->
            <div style="display:flex;align-items:center;justify-content:space-between">
                <span style="font-size:1rem;font-weight:800;color:#ee2624">
                    <?= cica_format_price((int)$order['total_amount']) ?>
                </span>
                <div style="display:flex;gap:0.5rem;flex-wrap:wrap">
                    <a href="<?= esc_url($site_url . '/dat-hang-thanh-cong/?order_id=' . $order['id']) ?>"
                       style="padding:0.5rem 1rem;border-radius:8px;font-weight:600;font-size:0.8rem;background:#f3f4f6;color:#374151;text-decoration:none">
                        <i class="fas fa-eye"></i> Chi tiết
                    </a>
                    <?php if ($order['status'] === 'completed'):
                        // Kiểm tra đã review chưa
                        global $wpdb;
                        $rv = $wpdb->prefix . 'cica_reviews';
                        $reviewed = $wpdb->get_var($wpdb->prepare(
                            "SELECT id FROM $rv WHERE order_id=%d AND wp_user_id=%d",
                            $order['id'], $user_id
                        ));
                    ?>
                    <?php if (!$reviewed): ?>
                    <button onclick="openReviewModal(<?= (int)$order['id'] ?>, '<?= esc_js($order['restaurant_name']) ?>')"
                            style="padding:0.5rem 1rem;border-radius:8px;font-weight:600;font-size:0.8rem;background:#fef9c3;color:#854d0e;border:none;cursor:pointer">
                        <i class="fas fa-star"></i> Đánh giá
                    </button>
                    <?php else: ?>
                    <span style="padding:0.5rem 1rem;border-radius:8px;font-size:0.8rem;background:#f0fdf4;color:#16a34a;font-weight:600">
                        <i class="fas fa-check"></i> Đã đánh giá
                    </span>
                    <?php endif; ?>
                    <?php endif; ?>
                    <?php if (in_array($order['status'], ['pending', 'confirmed'])): ?>
                    <button onclick="cancelOrder(<?= (int)$order['id'] ?>, this)"
                            style="padding:0.5rem 1rem;border-radius:8px;font-weight:600;font-size:0.8rem;background:#fee2e2;color:#991b1b;border:none;cursor:pointer">
                        <i class="fas fa-times"></i> Huỷ
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php endif; ?>
</div>

<script>
function cancelOrder(orderId, btn) {
    if (!confirm('Bạn có chắc muốn huỷ đơn hàng này?')) return;
    btn.disabled = true;
    btn.textContent = 'Đang huỷ...';

    var fd = new FormData();
    fd.append('action',   'cica_cancel_order');
    fd.append('order_id', orderId);
    fd.append('nonce', REVIEW_NONCE);

    fetch(REVIEW_AJAX, { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                location.reload();
            } else {
                alert(d.message || 'Không thể huỷ đơn');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-times"></i> Huỷ';
            }
        })
        .catch(function() {
            alert('Lỗi kết nối');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-times"></i> Huỷ';
        });
}
</script>

<!-- ===== MODAL ĐÁNH GIÁ ===== -->
<div id="review-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;padding:1rem">
    <div style="background:white;border-radius:20px;width:100%;max-width:460px;padding:1.5rem;box-shadow:0 24px 60px rgba(0,0,0,0.2)">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem">
            <h3 style="font-size:1rem;font-weight:800;color:#111827;margin:0">
                <i class="fas fa-star" style="color:#eab308"></i> Đánh giá nhà hàng
            </h3>
            <button onclick="closeReviewModal()" style="background:none;border:none;font-size:1.2rem;color:#9ca3af;cursor:pointer">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <p id="review-restaurant-name" style="font-size:0.875rem;color:#6b7280;margin:0 0 1.25rem;font-weight:600"></p>

        <!-- Star rating -->
        <div style="margin-bottom:1rem">
            <label style="display:block;font-size:0.75rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:0.5rem">Đánh giá của bạn</label>
            <div id="star-rating" style="display:flex;gap:0.4rem">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <button type="button" onclick="setRating(<?= $i ?>)" data-star="<?= $i ?>"
                        style="background:none;border:none;font-size:2rem;cursor:pointer;color:#d1d5db;transition:color 0.15s;padding:0">
                    <i class="fas fa-star"></i>
                </button>
                <?php endfor; ?>
            </div>
            <input type="hidden" id="review-rating" value="5">
        </div>

        <!-- Comment -->
        <div style="margin-bottom:1.25rem">
            <label style="display:block;font-size:0.75rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:0.4rem">Nhận xét (tuỳ chọn)</label>
            <textarea id="review-comment" rows="3" placeholder="Chia sẻ trải nghiệm của bạn về nhà hàng này..."
                      style="width:100%;padding:0.7rem 1rem;border:1.5px solid #e5e7eb;border-radius:12px;font-size:0.875rem;outline:none;resize:none;box-sizing:border-box;font-family:inherit"
                      onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'"></textarea>
        </div>

        <div id="review-msg" style="display:none;margin-bottom:0.75rem;border-radius:10px;padding:0.65rem 1rem;font-size:0.875rem;font-weight:600"></div>

        <div style="display:flex;gap:0.75rem">
            <button onclick="closeReviewModal()" style="flex:1;background:#f3f4f6;color:#374151;border:none;border-radius:12px;padding:0.75rem;font-weight:700;cursor:pointer">Huỷ</button>
            <button onclick="submitReview()" id="review-submit-btn" style="flex:2;background:#ee2624;color:white;border:none;border-radius:12px;padding:0.75rem;font-weight:700;cursor:pointer">
                <i class="fas fa-paper-plane"></i> Gửi đánh giá
            </button>
        </div>
    </div>
</div>

<script>
const REVIEW_AJAX  = '<?= esc_js($ajax_url) ?>';
const REVIEW_NONCE = '<?= esc_js($nonce) ?>';
let currentOrderId = 0;

function openReviewModal(orderId, restaurantName) {
    currentOrderId = orderId;
    document.getElementById('review-restaurant-name').textContent = restaurantName;
    document.getElementById('review-comment').value = '';
    document.getElementById('review-msg').style.display = 'none';
    setRating(5);
    document.getElementById('review-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeReviewModal() {
    document.getElementById('review-modal').style.display = 'none';
    document.body.style.overflow = '';
}

function setRating(val) {
    document.getElementById('review-rating').value = val;
    document.querySelectorAll('#star-rating button').forEach(btn => {
        const star = parseInt(btn.dataset.star);
        btn.style.color = star <= val ? '#eab308' : '#d1d5db';
    });
}

// Hover effect on stars
document.querySelectorAll('#star-rating button').forEach(btn => {
    btn.addEventListener('mouseenter', function() {
        const val = parseInt(this.dataset.star);
        document.querySelectorAll('#star-rating button').forEach(b => {
            b.style.color = parseInt(b.dataset.star) <= val ? '#fbbf24' : '#d1d5db';
        });
    });
    btn.addEventListener('mouseleave', function() {
        const current = parseInt(document.getElementById('review-rating').value);
        document.querySelectorAll('#star-rating button').forEach(b => {
            b.style.color = parseInt(b.dataset.star) <= current ? '#eab308' : '#d1d5db';
        });
    });
});

function submitReview() {
    const btn = document.getElementById('review-submit-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi...';

    const fd = new FormData();
    fd.append('action',   'cica_submit_order_review');
    fd.append('nonce',    REVIEW_NONCE);
    fd.append('order_id', currentOrderId);
    fd.append('rating',   document.getElementById('review-rating').value);
    fd.append('comment',  document.getElementById('review-comment').value);

    fetch(REVIEW_AJAX, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            const msg = document.getElementById('review-msg');
            msg.style.display = 'block';
            if (d.success) {
                msg.style.background = '#f0fdf4'; msg.style.color = '#16a34a'; msg.style.border = '1px solid #86efac';
                msg.innerHTML = '<i class="fas fa-check-circle"></i> ' + d.message;
                setTimeout(() => { closeReviewModal(); location.reload(); }, 1500);
            } else {
                msg.style.background = '#fef2f2'; msg.style.color = '#dc2626'; msg.style.border = '1px solid #fca5a5';
                msg.innerHTML = '<i class="fas fa-triangle-exclamation"></i> ' + d.message;
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> Gửi đánh giá';
            }
        });
}

document.getElementById('review-modal').addEventListener('click', function(e) {
    if (e.target === this) closeReviewModal();
});
</script>


