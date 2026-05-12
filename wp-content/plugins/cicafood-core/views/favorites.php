<?php
defined('ABSPATH') || exit;
if (!session_id()) session_start();

$user_id  = get_current_user_id();
$site_url = get_site_url();
$ajax_url = admin_url('admin-ajax.php');
$nonce    = wp_create_nonce('cicafood_nonce');

$favorites = Cicafood_Restaurant::get_favorites($user_id);

?>

<div style="max-width:1100px;margin:0 auto;padding:1.5rem 1rem">

    <!-- Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:0.75rem">
        <div>
            <h1 style="font-size:1.4rem;font-weight:900;color:#111827;margin:0;display:flex;align-items:center;gap:0.5rem">
                <i class="fas fa-heart" style="color:#ee2624"></i> Nhà Hàng Yêu Thích
            </h1>
            <p style="font-size:0.85rem;color:#6b7280;margin:0.3rem 0 0">
                <?= count($favorites) ?> nhà hàng đã lưu
            </p>
        </div>
        <a href="<?= esc_url($site_url . '/nha-hang/') ?>"
           style="display:inline-flex;align-items:center;gap:0.5rem;background:#ee2624;color:white;padding:0.6rem 1.25rem;border-radius:10px;font-weight:700;font-size:0.875rem;text-decoration:none">
            <i class="fas fa-store"></i> Khám phá thêm
        </a>
    </div>

    <?php if (empty($favorites)): ?>
    <!-- Empty state -->
    <div style="background:white;border-radius:20px;border:1px solid #e5e7eb;padding:4rem 2rem;text-align:center">
        <div style="width:80px;height:80px;background:#fef2f2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem">
            <i class="fas fa-heart-crack" style="font-size:2rem;color:#fca5a5"></i>
        </div>
        <h2 style="font-size:1.2rem;font-weight:800;color:#374151;margin:0 0 0.5rem">Chưa có nhà hàng yêu thích</h2>
        <p style="font-size:0.875rem;color:#9ca3af;margin:0 0 1.5rem">Nhấn vào biểu tượng ❤️ trên trang nhà hàng để lưu lại những quán bạn thích</p>
        <a href="<?= esc_url($site_url . '/nha-hang/') ?>"
           style="display:inline-flex;align-items:center;gap:0.5rem;background:#ee2624;color:white;padding:0.75rem 1.5rem;border-radius:12px;font-weight:700;font-size:0.9rem;text-decoration:none">
            <i class="fas fa-search"></i> Tìm nhà hàng ngay
        </a>
    </div>

    <?php else: ?>

    <!-- Filter bar -->
    <div style="background:white;border-radius:14px;border:1px solid #e5e7eb;padding:0.875rem 1.25rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap">
        <span style="font-size:0.8rem;font-weight:700;color:#6b7280">Lọc:</span>
        <button onclick="filterFav('all', this)"
                style="padding:0.35rem 0.875rem;border-radius:999px;font-size:0.8rem;font-weight:700;border:none;cursor:pointer;background:#ee2624;color:white"
                class="fav-filter-btn active">Tất cả</button>
        <button onclick="filterFav('open', this)"
                style="padding:0.35rem 0.875rem;border-radius:999px;font-size:0.8rem;font-weight:700;border:none;cursor:pointer;background:#f3f4f6;color:#374151"
                class="fav-filter-btn">Đang mở</button>
        <button onclick="filterFav('freeship', this)"
                style="padding:0.35rem 0.875rem;border-radius:999px;font-size:0.8rem;font-weight:700;border:none;cursor:pointer;background:#f3f4f6;color:#374151"
                class="fav-filter-btn">Freeship</button>
        <button onclick="filterFav('deal', this)"
                style="padding:0.35rem 0.875rem;border-radius:999px;font-size:0.8rem;font-weight:700;border:none;cursor:pointer;background:#f3f4f6;color:#374151"
                class="fav-filter-btn">Deal hời</button>
        <div style="margin-left:auto;display:flex;align-items:center;gap:0.5rem">
            <span style="font-size:0.8rem;color:#9ca3af">Sắp xếp:</span>
            <select id="fav-sort" onchange="sortFav(this.value)"
                    style="padding:0.35rem 0.75rem;border:1.5px solid #e5e7eb;border-radius:8px;font-size:0.8rem;font-weight:600;outline:none;cursor:pointer">
                <option value="default">Mới lưu nhất</option>
                <option value="rating">Đánh giá cao</option>
                <option value="delivery">Giao nhanh nhất</option>
            </select>
        </div>
    </div>

    <!-- Grid -->
    <div id="fav-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1.25rem">
        <?php foreach ($favorites as $r): ?>
        <div class="fav-card"
             data-open="<?= (int)$r['is_open'] ?>"
             data-freeship="<?= (int)$r['has_freeship'] ?>"
             data-deal="<?= (int)$r['has_deal'] ?>"
             data-rating="<?= (float)$r['rating'] ?>"
             data-time="<?= (int)$r['delivery_time'] ?>"
             style="background:white;border-radius:18px;overflow:hidden;border:1px solid #e5e7eb;transition:transform 0.2s,box-shadow 0.2s;position:relative"
             onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 16px 40px rgba(0,0,0,0.1)'"
             onmouseout="this.style.transform='';this.style.boxShadow=''">

            <!-- Image -->
            <a href="<?= esc_url($site_url . '/nha-hang/' . $r['slug'] . '/') ?>" style="display:block;text-decoration:none">
                <div style="position:relative;height:160px;overflow:hidden">
                    <img src="<?= esc_url($r['image'] ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&q=80') ?>"
                         alt="<?= esc_attr($r['name']) ?>"
                         style="width:100%;height:100%;object-fit:cover;transition:transform 0.5s"
                         onmouseover="this.style.transform='scale(1.08)'" onmouseout="this.style.transform=''"
                         loading="lazy">

                    <!-- Badges -->
                    <div style="position:absolute;top:0.6rem;left:0.6rem;display:flex;gap:0.3rem;flex-wrap:wrap">
                        <?php if ($r['has_freeship']): ?>
                        <span style="background:#22c55e;color:white;font-size:0.65rem;font-weight:800;padding:2px 7px;border-radius:6px">Freeship</span>
                        <?php endif; ?>
                        <?php if ($r['has_deal']): ?>
                        <span style="background:#f97316;color:white;font-size:0.65rem;font-weight:800;padding:2px 7px;border-radius:6px">Deal</span>
                        <?php endif; ?>
                    </div>

                    <!-- Status -->
                    <div style="position:absolute;top:0.6rem;right:0.6rem">
                        <span style="background:<?= $r['is_open'] ? 'rgba(22,163,74,0.9)' : 'rgba(107,114,128,0.85)' ?>;color:white;font-size:0.65rem;font-weight:700;padding:3px 8px;border-radius:6px;backdrop-filter:blur(4px)">
                            <?= $r['is_open'] ? '● Đang mở' : '○ Đóng cửa' ?>
                        </span>
                    </div>

                    <!-- Overlay khi đóng cửa -->
                    <?php if (!$r['is_open']): ?>
                    <div style="position:absolute;inset:0;background:rgba(0,0,0,0.35)"></div>
                    <?php endif; ?>
                </div>
            </a>

            <!-- Remove favorite button -->
            <button onclick="removeFavorite(<?= (int)$r['id'] ?>, this)"
                    title="Bỏ yêu thích"
                    style="position:absolute;top:0.6rem;right:0.6rem;width:30px;height:30px;background:white;border:none;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,0.15);transition:transform 0.2s;z-index:2"
                    onmouseover="this.style.transform='scale(1.15)'" onmouseout="this.style.transform=''">
                <i class="fas fa-heart" style="color:#ee2624;font-size:0.75rem"></i>
            </button>

            <!-- Info -->
            <a href="<?= esc_url($site_url . '/nha-hang/' . $r['slug'] . '/') ?>" style="display:block;text-decoration:none;color:inherit">
                <div style="padding:0.875rem">
                    <h3 style="font-weight:800;font-size:0.95rem;color:#111827;margin:0 0 0.2rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                        <?= esc_html($r['name']) ?>
                    </h3>
                    <p style="font-size:0.75rem;color:#9ca3af;margin:0 0 0.6rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                        <?= esc_html($r['category_name'] ?? 'Ẩm thực') ?> · <?= esc_html($r['address']) ?>
                    </p>
                    <div style="display:flex;align-items:center;justify-content:space-between;font-size:0.78rem;color:#6b7280;border-top:1px solid #f3f4f6;padding-top:0.6rem">
                        <div style="display:flex;align-items:center;gap:0.75rem">
                            <span style="display:flex;align-items:center;gap:0.25rem;font-weight:700;color:#eab308">
                                <i class="fas fa-star" style="font-size:0.7rem"></i>
                                <?= number_format((float)$r['rating'], 1) ?>
                            </span>
                            <span><i class="far fa-clock" style="color:#ee2624;margin-right:2px"></i><?= (int)$r['delivery_time'] ?>'</span>
                        </div>
                        <span style="<?= (int)$r['delivery_fee'] === 0 ? 'color:#22c55e;font-weight:700' : '' ?>">
                            <?= (int)$r['delivery_fee'] === 0 ? '🚚 Miễn phí' : cica_format_price((int)$r['delivery_fee']) ?>
                        </span>
                    </div>
                </div>
            </a>

            <!-- Order button -->
            <div style="padding:0 0.875rem 0.875rem">
                <a href="<?= esc_url($site_url . '/nha-hang/' . $r['slug'] . '/') ?>"
                   style="display:block;text-align:center;background:<?= $r['is_open'] ? '#ee2624' : '#f3f4f6' ?>;color:<?= $r['is_open'] ? 'white' : '#9ca3af' ?>;padding:0.55rem;border-radius:10px;font-weight:700;font-size:0.8rem;text-decoration:none;transition:background 0.2s"
                   <?= $r['is_open'] ? 'onmouseover="this.style.background=\'#c41e1c\'" onmouseout="this.style.background=\'#ee2624\'"' : '' ?>>
                    <?= $r['is_open'] ? '<i class="fas fa-utensils"></i> Đặt ngay' : '<i class="fas fa-clock"></i> Đang đóng cửa' ?>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Empty after filter -->
    <div id="fav-empty-filter" style="display:none;text-align:center;padding:3rem;color:#9ca3af">
        <i class="fas fa-filter" style="font-size:2rem;margin-bottom:0.75rem;display:block"></i>
        <p style="font-weight:600;margin:0">Không có nhà hàng nào phù hợp với bộ lọc</p>
    </div>

    <?php endif; ?>
</div>

<script>
const AJAX_URL = '<?= $ajax_url ?>';
const NONCE    = '<?= $nonce ?>';
let currentFilter = 'all';

function removeFavorite(restaurantId, btn) {
    const card = btn.closest('.fav-card');
    if (!confirm('Bỏ nhà hàng này khỏi danh sách yêu thích?')) return;

    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size:0.7rem;color:#9ca3af"></i>';

    fetch(AJAX_URL, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=cica_toggle_favorite&restaurant_id=${restaurantId}&nonce=${NONCE}`
    })
    .then(r => r.json())
    .then(d => {
        if (d.success && !d.favorited) {
            // Animate out
            card.style.transition = 'all 0.3s ease';
            card.style.opacity = '0';
            card.style.transform = 'scale(0.85)';
            setTimeout(() => {
                card.remove();
                updateCount();
                checkEmpty();
            }, 300);
            if (typeof showCicaToast === 'function') showCicaToast('info', 'Đã bỏ yêu thích');
        }
    })
    .catch(() => {
        btn.innerHTML = '<i class="fas fa-heart" style="color:#ee2624;font-size:0.75rem"></i>';
    });
}

function updateCount() {
    const count = document.querySelectorAll('.fav-card:not([style*="display: none"])').length;
    const el = document.querySelector('p[style*="0.85rem"][style*="6b7280"]');
    if (el) el.textContent = count + ' nhà hàng đã lưu';
}

function checkEmpty() {
    const visible = document.querySelectorAll('.fav-card').length;
    if (visible === 0) location.reload();
}

function filterFav(type, btn) {
    currentFilter = type;

    // Update button styles
    document.querySelectorAll('.fav-filter-btn').forEach(b => {
        b.style.background = '#f3f4f6';
        b.style.color = '#374151';
    });
    btn.style.background = '#ee2624';
    btn.style.color = 'white';

    applyFilters();
}

function sortFav(val) {
    const grid = document.getElementById('fav-grid');
    const cards = Array.from(grid.querySelectorAll('.fav-card'));

    cards.sort((a, b) => {
        if (val === 'rating')   return parseFloat(b.dataset.rating) - parseFloat(a.dataset.rating);
        if (val === 'delivery') return parseInt(a.dataset.time) - parseInt(b.dataset.time);
        return 0; // default: keep original order
    });

    cards.forEach(c => grid.appendChild(c));
    applyFilters();
}

function applyFilters() {
    const cards = document.querySelectorAll('.fav-card');
    let visibleCount = 0;

    cards.forEach(card => {
        let show = true;
        if (currentFilter === 'open'     && card.dataset.open     !== '1') show = false;
        if (currentFilter === 'freeship' && card.dataset.freeship !== '1') show = false;
        if (currentFilter === 'deal'     && card.dataset.deal     !== '1') show = false;

        card.style.display = show ? '' : 'none';
        if (show) visibleCount++;
    });

    document.getElementById('fav-empty-filter').style.display = visibleCount === 0 ? 'block' : 'none';
}
</script>

<style>
@media (max-width: 640px) {
    #fav-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 0.75rem !important; }
}
@media (max-width: 400px) {
    #fav-grid { grid-template-columns: 1fr !important; }
}
</style>

