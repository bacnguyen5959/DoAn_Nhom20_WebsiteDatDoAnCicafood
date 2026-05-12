</div><!-- .col-full -->
</div><!-- #content -->

<?php
$site_url = get_site_url();
?>

<!-- ============================================================
     CICAFOOD FOOTER
     ============================================================ -->
<footer style="background:#111827;color:#d1d5db;margin-top:4rem">

    <!-- Main footer content -->
    <div style="max-width:1280px;margin:0 auto;padding:3.5rem 1.5rem 2rem">
        <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:3rem" class="cica-footer-grid">

            <!-- Brand column -->
            <div>
                <div style="display:flex;align-items:center;gap:0.6rem;margin-bottom:1rem">
                    <div style="width:40px;height:40px;background:#ee2624;border-radius:12px;display:flex;align-items:center;justify-content:center">
                        <i class="fas fa-utensils" style="color:white;font-size:1.1rem"></i>
                    </div>
                    <span style="font-size:1.5rem;font-weight:900;color:white">Cicafood</span>
                </div>
                <p style="font-size:0.875rem;line-height:1.7;color:#9ca3af;margin:0 0 1.25rem;max-width:280px">
                    Nền tảng đặt đồ ăn trực tuyến hàng đầu Việt Nam. Kết nối hàng nghìn nhà hàng với triệu khách hàng mỗi ngày.
                </p>
                <!-- App download badges -->
                <div style="display:flex;gap:0.75rem;flex-wrap:wrap">
                    <a href="#" style="display:flex;align-items:center;gap:0.5rem;background:#1f2937;border:1px solid #374151;border-radius:10px;padding:0.5rem 0.875rem;text-decoration:none;transition:border-color 0.2s" onmouseover="this.style.borderColor='#ee2624'" onmouseout="this.style.borderColor='#374151'">
                        <i class="fab fa-apple" style="color:white;font-size:1.2rem"></i>
                        <div>
                            <p style="font-size:0.6rem;color:#9ca3af;margin:0">Tải về trên</p>
                            <p style="font-size:0.8rem;font-weight:700;color:white;margin:0">App Store</p>
                        </div>
                    </a>
                    <a href="#" style="display:flex;align-items:center;gap:0.5rem;background:#1f2937;border:1px solid #374151;border-radius:10px;padding:0.5rem 0.875rem;text-decoration:none;transition:border-color 0.2s" onmouseover="this.style.borderColor='#ee2624'" onmouseout="this.style.borderColor='#374151'">
                        <i class="fab fa-google-play" style="color:#22c55e;font-size:1.1rem"></i>
                        <div>
                            <p style="font-size:0.6rem;color:#9ca3af;margin:0">Tải về trên</p>
                            <p style="font-size:0.8rem;font-weight:700;color:white;margin:0">Google Play</p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Khám phá -->
            <div>
                <h3 style="font-size:0.875rem;font-weight:700;color:white;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 1.25rem">Khám phá</h3>
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.6rem">
                    <?php foreach ([
                        ['Nhà hàng',    $site_url . '/nha-hang/'],
                        ['Ưu đãi hôm nay', $site_url . '/nha-hang/?deal=1'],
                        ['Freeship',    $site_url . '/nha-hang/?freeship=1'],
                        ['Voucher',     $site_url . '/voucher/'],
                        ['Đơn hàng',   $site_url . '/don-hang/'],
                    ] as [$label, $url]): ?>
                    <li>
                        <a href="<?= esc_url($url) ?>"
                           style="font-size:0.875rem;color:#9ca3af;text-decoration:none;transition:color 0.2s;display:flex;align-items:center;gap:0.4rem"
                           onmouseover="this.style.color='#ee2624'" onmouseout="this.style.color='#9ca3af'">
                            <i class="fas fa-chevron-right" style="font-size:0.6rem;color:#ee2624"></i> <?= $label ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Hỗ trợ -->
            <div>
                <h3 style="font-size:0.875rem;font-weight:700;color:white;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 1.25rem">Hỗ trợ</h3>
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.6rem">
                    <?php foreach ([
                        ['Trung tâm trợ giúp', '#'],
                        ['Chính sách bảo mật', '#'],
                        ['Điều khoản sử dụng', '#'],
                        ['Liên hệ chúng tôi',  '#'],
                        ['Đối tác nhà hàng',   '#'],
                    ] as [$label, $url]): ?>
                    <li>
                        <a href="<?= esc_url($url) ?>"
                           style="font-size:0.875rem;color:#9ca3af;text-decoration:none;transition:color 0.2s;display:flex;align-items:center;gap:0.4rem"
                           onmouseover="this.style.color='#ee2624'" onmouseout="this.style.color='#9ca3af'">
                            <i class="fas fa-chevron-right" style="font-size:0.6rem;color:#ee2624"></i> <?= $label ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Liên hệ -->
            <div>
                <h3 style="font-size:0.875rem;font-weight:700;color:white;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 1.25rem">Liên hệ</h3>
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.75rem">
                    <li style="display:flex;align-items:flex-start;gap:0.6rem;font-size:0.875rem;color:#9ca3af">
                        <i class="fas fa-location-dot" style="color:#ee2624;margin-top:2px;flex-shrink:0"></i>
                        <span>123 Nguyễn Huệ, Q1, TP.HCM</span>
                    </li>
                    <li style="display:flex;align-items:center;gap:0.6rem;font-size:0.875rem;color:#9ca3af">
                        <i class="fas fa-phone" style="color:#ee2624;flex-shrink:0"></i>
                        <a href="tel:18001234" style="color:#9ca3af;text-decoration:none" onmouseover="this.style.color='#ee2624'" onmouseout="this.style.color='#9ca3af'">1800-CICA (miễn phí)</a>
                    </li>
                    <li style="display:flex;align-items:center;gap:0.6rem;font-size:0.875rem;color:#9ca3af">
                        <i class="fas fa-envelope" style="color:#ee2624;flex-shrink:0"></i>
                        <a href="mailto:ngtuananh@gmail.com" style="color:#9ca3af;text-decoration:none" onmouseover="this.style.color='#ee2624'" onmouseout="this.style.color='#9ca3af'">ngtuananh@gmail.com</a>
                    </li>
                </ul>

                <!-- Social links -->
                <div style="display:flex;gap:0.6rem;margin-top:1.25rem">
                    <?php foreach ([
                        ['fab fa-facebook-f', '#', '#1877f2'],
                        ['fab fa-instagram',  '#', '#e1306c'],
                        ['fab fa-tiktok',     '#', '#ffffff'],
                        ['fab fa-youtube',    '#', '#ff0000'],
                    ] as [$icon, $url, $color]): ?>
                    <a href="<?= esc_url($url) ?>"
                       style="width:36px;height:36px;background:#1f2937;border:1px solid #374151;border-radius:10px;display:flex;align-items:center;justify-content:center;text-decoration:none;transition:all 0.2s"
                       onmouseover="this.style.background='<?= $color ?>20';this.style.borderColor='<?= $color ?>'"
                       onmouseout="this.style.background='#1f2937';this.style.borderColor='#374151'">
                        <i class="<?= $icon ?>" style="color:<?= $color ?>;font-size:0.875rem"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats bar -->
    <div style="border-top:1px solid #1f2937;border-bottom:1px solid #1f2937;background:#0f172a">
        <div style="max-width:1280px;margin:0 auto;padding:1.25rem 1.5rem;display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;text-align:center" class="cica-stats-grid">
            <?php foreach ([
                ['500+',  'Nhà hàng đối tác', 'fa-store'],
                ['50K+',  'Đơn hàng/ngày',    'fa-bag-shopping'],
                ['30\'',  'Giao hàng nhanh',  'fa-motorcycle'],
                ['4.8★',  'Đánh giá TB',      'fa-star'],
            ] as [$num, $label, $icon]): ?>
            <div>
                <div style="display:flex;align-items:center;justify-content:center;gap:0.4rem;margin-bottom:0.2rem">
                    <i class="fas <?= $icon ?>" style="color:#ee2624;font-size:0.875rem"></i>
                    <span style="font-size:1.25rem;font-weight:900;color:white"><?= $num ?></span>
                </div>
                <p style="font-size:0.75rem;color:#6b7280;margin:0"><?= $label ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Bottom bar -->
    <div style="max-width:1280px;margin:0 auto;padding:1.25rem 1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem">
        <p style="font-size:0.8rem;color:#6b7280;margin:0">
            &copy; <?= date('Y') ?> <strong style="color:#9ca3af">Cicafood</strong>. Bảo lưu mọi quyền.
        </p>
        <div style="display:flex;align-items:center;gap:1rem">
            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/5e/Visa_Inc._logo.svg/200px-Visa_Inc._logo.svg.png"
                 alt="Visa" style="height:20px;opacity:0.5;filter:grayscale(1)">
            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Mastercard-logo.svg/200px-Mastercard-logo.svg.png"
                 alt="Mastercard" style="height:20px;opacity:0.5;filter:grayscale(1)">
            <span style="font-size:0.75rem;color:#6b7280;background:#1f2937;padding:3px 10px;border-radius:6px;font-weight:600">COD</span>
            <span style="font-size:0.75rem;color:#6b7280;background:#1f2937;padding:3px 10px;border-radius:6px;font-weight:600">MoMo</span>
        </div>
    </div>
</footer>

<style>
@media (max-width: 900px) {
    .cica-footer-grid { grid-template-columns: 1fr 1fr !important; gap: 2rem !important; }
    .cica-stats-grid  { grid-template-columns: repeat(2,1fr) !important; }
}
@media (max-width: 600px) {
    .cica-footer-grid { grid-template-columns: 1fr !important; }
    .cica-stats-grid  { grid-template-columns: repeat(2,1fr) !important; }
}
</style>

</div><!-- #page -->

<?php wp_footer(); ?>
</body>
</html>
