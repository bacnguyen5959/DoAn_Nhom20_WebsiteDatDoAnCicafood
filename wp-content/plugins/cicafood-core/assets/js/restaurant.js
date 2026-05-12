/* Cicafood Restaurant Detail JS */
(function () {
    'use strict';

    var DELIVERY_FEE   = window.cicaRestaurant ? parseInt(window.cicaRestaurant.deliveryFee,  10) || 0 : 0;
    var LOGIN_URL      = window.cicaRestaurant ? window.cicaRestaurant.loginUrl      : '';
    var AJAX_URL       = window.cicafoodAjax   ? window.cicafoodAjax.ajaxurl        : '';
    var NONCE          = window.cicafoodAjax   ? window.cicafoodAjax.nonce          : '';

    // ============================================================
    // ADD TO CART
    // ============================================================
    function addToCart(itemId, restaurantId, btn) {
        var originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size:0.8rem"></i>';
        btn.disabled = true;

        var fd = new FormData();
        fd.append('action',        'cica_add_to_cart');
        fd.append('item_id',       itemId);
        fd.append('restaurant_id', restaurantId);
        fd.append('quantity',      1);
        fd.append('nonce',         NONCE);

        fetch(AJAX_URL, { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.success) {
                    updateCartSidebar(d.cart);
                    if (typeof updateCartBadge === 'function') updateCartBadge(d.cart_count);
                    showToast('success', d.message || 'Đã thêm vào giỏ hàng!');
                } else if (d.conflict) {
                    if (confirm('Giỏ hàng đang có món từ nhà hàng khác. Xoá và thêm mới?')) {
                        var fd2 = new FormData();
                        fd2.append('action', 'cica_clear_cart');
                        fd2.append('nonce',  NONCE);
                        fetch(AJAX_URL, { method: 'POST', body: fd2 })
                            .then(function () { addToCart(itemId, restaurantId, btn); });
                        return; // giữ spinner cho đến khi gọi lại
                    }
                } else {
                    showToast('error', d.message || 'Có lỗi xảy ra!');
                }
            })
            .catch(function () { showToast('error', 'Lỗi kết nối'); })
            .finally(function () { btn.innerHTML = originalHTML; btn.disabled = false; });
    }

    // Expose để onclick inline (nếu cần)
    window.addToCartCica = addToCart;

    // ============================================================
    // UPDATE CART SIDEBAR
    // ============================================================
    function updateCartSidebar(cart) {
        var list    = document.getElementById('cart-items-list');
        var summary = document.getElementById('cart-summary');
        if (!list) return;

        if (!cart || Object.keys(cart).length === 0) {
            list.innerHTML = '<div style="text-align:center;padding:2rem 0;color:#9ca3af;font-size:0.9rem">'
                + '<i class="fas fa-shopping-bag" style="font-size:2rem;margin-bottom:0.5rem;display:block"></i>'
                + 'Chưa có món nào</div>';
            if (summary) summary.style.display = 'none';
            return;
        }

        var html     = '';
        var subtotal = 0;
        Object.entries(cart).forEach(function (entry) {
            var id        = entry[0];
            var item      = entry[1];
            var itemTotal = parseInt(item.price, 10) * parseInt(item.quantity, 10);
            subtotal += itemTotal;
            html += '<div style="display:flex;align-items:center;gap:0.75rem;padding:0.6rem 0;border-bottom:1px solid #f3f4f6">'
                + '<span style="flex:1;font-size:0.875rem;font-weight:600;color:#374151">' + item.name + '</span>'
                + '<div style="display:flex;align-items:center;gap:0.4rem">'
                + '<button onclick="updateQtyCica(' + id + ',' + (item.quantity - 1) + ')" style="width:26px;height:26px;border-radius:50%;border:2px solid #e5e7eb;background:white;cursor:pointer">-</button>'
                + '<span style="font-weight:700;font-size:0.9rem;min-width:20px;text-align:center">' + item.quantity + '</span>'
                + '<button onclick="updateQtyCica(' + id + ',' + (item.quantity + 1) + ')" style="width:26px;height:26px;border-radius:50%;border:2px solid #e5e7eb;background:white;cursor:pointer">+</button>'
                + '</div>'
                + '<span style="font-size:0.875rem;font-weight:600;color:#374151;min-width:60px;text-align:right">' + itemTotal.toLocaleString('vi-VN') + 'đ</span>'
                + '</div>';
        });

        list.innerHTML = html;
        var total      = subtotal + DELIVERY_FEE;
        var subtotalEl = document.getElementById('cart-subtotal');
        var totalEl    = document.getElementById('cart-total');
        if (subtotalEl) subtotalEl.textContent = subtotal.toLocaleString('vi-VN') + 'đ';
        if (totalEl)    totalEl.textContent    = total.toLocaleString('vi-VN') + 'đ';
        if (summary)    summary.style.display  = 'block';
    }

    window.updateCartSidebar = updateCartSidebar;

    // ============================================================
    // UPDATE QUANTITY
    // ============================================================
    function updateQtyCica(itemId, qty) {
        var fd = new FormData();
        fd.append('action',   'cica_update_cart');
        fd.append('item_id',  itemId);
        fd.append('quantity', qty);
        fd.append('nonce',    NONCE);
        fetch(AJAX_URL, { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.success) {
                    updateCartSidebar(d.cart);
                    if (typeof updateCartBadge === 'function') updateCartBadge(d.cart_count);
                }
            });
    }

    window.updateQtyCica = updateQtyCica;

    // ============================================================
    // TOGGLE FAVORITE
    // ============================================================
    function toggleFavorite(e, id, btn) {
        e.preventDefault();
        e.stopPropagation();
        var fd = new FormData();
        fd.append('action',        'cica_toggle_favorite');
        fd.append('restaurant_id', id);
        fd.append('nonce',         NONCE);
        fetch(AJAX_URL, { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.login_required) { window.location.href = LOGIN_URL; return; }
                if (d.success) {
                    var icon = btn.querySelector('i');
                    if (d.favorited) {
                        icon.className = 'fas fa-heart';
                        icon.style.color = '#ee2624';
                        showToast('success', 'Đã thêm vào yêu thích!');
                    } else {
                        icon.className = 'far fa-heart';
                        icon.style.color = '#9ca3af';
                        showToast('info', 'Đã bỏ yêu thích');
                    }
                }
            });
    }

    window.toggleFavorite = toggleFavorite;

    // ============================================================
    // TOAST
    // ============================================================
    function showToast(type, msg) {
        var container = document.getElementById('cica-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'cica-toast-container';
            container.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:8px';
            document.body.appendChild(container);
        }
        var colors = { success: '#16a34a', error: '#dc2626', info: '#2563eb' };
        var toast = document.createElement('div');
        toast.style.cssText = 'background:' + (colors[type] || '#374151') + ';color:white;padding:12px 20px;border-radius:12px;font-weight:600;font-size:14px;box-shadow:0 8px 30px rgba(0,0,0,0.15)';
        toast.textContent = msg;
        container.appendChild(toast);
        setTimeout(function () { toast.remove(); }, 3500);
    }

    window.showCicaToast = showToast;

    // ============================================================
    // DOM READY — gắn event listener và load cart hiện tại
    // ============================================================
    document.addEventListener('DOMContentLoaded', function () {
        // Gắn sự kiện cho tất cả nút thêm món
        document.querySelectorAll('.cica-add-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var itemId       = parseInt(this.dataset.itemId, 10);
                var restaurantId = parseInt(this.dataset.restaurantId, 10);
                addToCart(itemId, restaurantId, this);
            });
        });

        // Hiển thị cart hiện tại (truyền từ PHP qua wp_localize_script)
        var currentCart = window.cicaRestaurant && window.cicaRestaurant.currentCart;
        if (currentCart && typeof currentCart === 'object' && Object.keys(currentCart).length > 0) {
            updateCartSidebar(currentCart);
        }
    });

}());
