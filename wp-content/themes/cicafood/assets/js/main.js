/**
 * Cicafood Theme - main.js
 * Global JS: cart, toast, favorites
 */

(function ($) {
    'use strict';

    // ============================================================
    // ADD TO CART
    // ============================================================
    window.addToCart = function (itemId, restaurantId, btn) {
        const originalHTML = btn ? btn.innerHTML : '';
        if (btn) { btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; btn.disabled = true; }

        $.ajax({
            url: cicafoodAjax.ajaxurl,
            method: 'POST',
            data: {
                action: 'cica_add_to_cart',
                nonce: cicafoodAjax.nonce,
                item_id: itemId,
                restaurant_id: restaurantId,
                quantity: 1,
            },
            success: function (res) {
                if (res.success) {
                    showCicaToast('success', res.message || 'Đã thêm vào giỏ hàng!');
                    updateCartBadge(res.cart_count);
                } else if (res.conflict) {
                    if (confirm('Giỏ hàng đang có món từ nhà hàng khác. Xoá và thêm mới?')) {
                        $.ajax({
                            url: cicafoodAjax.ajaxurl,
                            method: 'POST',
                            data: { action: 'cica_clear_cart', nonce: cicafoodAjax.nonce },
                            success: function () { window.addToCart(itemId, restaurantId, btn); }
                        });
                        return;
                    }
                } else {
                    showCicaToast('error', res.message || 'Có lỗi xảy ra!');
                }
            },
            error: function () { showCicaToast('error', 'Lỗi kết nối. Vui lòng thử lại!'); },
            complete: function () {
                if (btn) { btn.innerHTML = originalHTML; btn.disabled = false; }
            }
        });
    };

    // ============================================================
    // TOGGLE FAVORITE
    // ============================================================
    window.toggleFavorite = function (event, restaurantId, btn) {
        event.preventDefault();
        event.stopPropagation();

        $.ajax({
            url: cicafoodAjax.ajaxurl,
            method: 'POST',
            data: { action: 'cica_toggle_favorite', nonce: cicafoodAjax.nonce, restaurant_id: restaurantId },
            success: function (res) {
                if (res.login_required) { window.location.href = cicafoodAjax.siteurl + '/wp-login.php'; return; }
                if (res.success) {
                    const icon = btn.querySelector('i');
                    if (res.favorited) {
                        icon.className = 'fas fa-heart'; icon.style.color = '#ee2624';
                        showCicaToast('success', 'Đã thêm vào yêu thích!');
                    } else {
                        icon.className = 'far fa-heart'; icon.style.color = '#9ca3af';
                        showCicaToast('info', 'Đã bỏ yêu thích');
                    }
                }
            }
        });
    };

    // ============================================================
    // TOAST
    // ============================================================
    window.showCicaToast = function (type, msg, duration) {
        duration = duration || 3500;
        let container = document.getElementById('cica-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'cica-toast-container';
            container.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:8px';
            document.body.appendChild(container);
        }
        const colors = { success: '#16a34a', error: '#dc2626', info: '#2563eb' };
        const icons  = { success: 'fa-check-circle', error: 'fa-triangle-exclamation', info: 'fa-circle-info' };
        const toast  = document.createElement('div');
        toast.style.cssText = `background:${colors[type]||'#374151'};color:white;padding:12px 20px;border-radius:12px;font-weight:600;font-size:14px;display:flex;align-items:center;gap:10px;box-shadow:0 8px 30px rgba(0,0,0,0.15);animation:cicaSlideIn 0.3s ease`;
        toast.innerHTML = `<i class="fas ${icons[type]||'fa-bell'}"></i><span>${msg}</span>`;
        container.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(50px)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    };

    // ============================================================
    // UPDATE CART BADGE
    // ============================================================
    window.updateCartBadge = function (count) {
        const badge = document.getElementById('cica-cart-badge');
        if (!badge) return;
        badge.textContent = count;
        badge.style.display = count > 0 ? 'flex' : 'none';
    };

})(jQuery);
