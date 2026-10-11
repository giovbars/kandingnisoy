<?php
/**
 * Universal Footer
 * ------------------------------------------------------------
 * Usage:  require_once __DIR__ . '/../../includes/footer.php';
 *         renderFooter();
 *
 * Works from any depth in the project — no path juggling needed.
 * ------------------------------------------------------------
 */

if (!function_exists('renderFooter')) {

    function renderFooter(): void
    {
        // Year is computed at render time
        $year = date('Y');

        // Site name — override with define('SITE_NAME', '...') in config.php
        $siteName = defined('SITE_NAME') ? SITE_NAME : 'Goat Management System';

        // Optional BASE_URL for internal links
        $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

        ?>
        <style>
            /* ==========================================================
               UNIVERSAL FOOTER — self-contained, safe to include anywhere
            ========================================================== */
            .site-footer,
            .site-footer *,
            .site-footer *::before,
            .site-footer *::after { box-sizing: border-box; }

            .site-footer {
                position: relative;
                margin-top: 60px;
                padding: 40px clamp(16px, 3vw, 36px) 20px;
                border-top: 1px solid #dcfce7;
                background:
                    radial-gradient(circle at 0% 0%, rgba(34,197,94,.06), transparent 45%),
                    radial-gradient(circle at 100% 100%, rgba(34,197,94,.05), transparent 45%),
                    linear-gradient(180deg, #ffffff, #f8fafc);
                color: #475569;
                font-family: system-ui, -apple-system, "Segoe UI", Roboto,
                             "Helvetica Neue", Arial, sans-serif;
                font-size: 14px;
                line-height: 1.55;
            }

            .site-footer__inner {
                max-width: 1200px;
                margin: 0 auto;
                display: grid;
                grid-template-columns: minmax(0, 1.4fr) repeat(2, minmax(0, 1fr)) minmax(0, 1.2fr);
                gap: 32px;
            }

            /* ---------- Brand column ---------- */
            .site-footer__brand {
                display: flex;
                align-items: flex-start;
                gap: 12px;
                min-width: 0;
            }

            .site-footer__logo {
                flex-shrink: 0;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 46px;
                height: 46px;
                border-radius: 12px;
                background: linear-gradient(145deg, #22c55e, #15803d);
                box-shadow: 0 6px 14px rgba(21,128,61,.22);
                color: #fff;
            }

            .site-footer__brand-text { min-width: 0; }

            .site-footer__brand-name {
                margin: 0;
                color: #0f172a;
                font-size: 16px;
                font-weight: 800;
                letter-spacing: -.015em;
            }

            .site-footer__brand-tag {
                margin: 4px 0 0;
                color: #64748b;
                font-size: 13px;
                line-height: 1.5;
            }

            /* ---------- Link columns ---------- */
            .site-footer__col h4 {
                margin: 0 0 12px;
                color: #0f172a;
                font-size: 13px;
                font-weight: 800;
                letter-spacing: .06em;
                text-transform: uppercase;
            }

            .site-footer__col ul {
                margin: 0;
                padding: 0;
                list-style: none;
                display: flex;
                flex-direction: column;
                gap: 8px;
            }

            .site-footer__col a {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                min-height: 32px;
                color: #475569;
                font-size: 14px;
                font-weight: 500;
                text-decoration: none;
                transition: color .18s ease, transform .18s ease;
            }

            .site-footer__col a:hover {
                color: #15803d;
                transform: translateX(2px);
            }

            .site-footer__col a svg { color: #22c55e; flex-shrink: 0; }

            /* ---------- Contact column ---------- */
            .site-footer__contact {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .site-footer__contact-row {
                display: inline-flex;
                align-items: flex-start;
                gap: 10px;
                color: #475569;
                font-size: 13.5px;
                line-height: 1.5;
            }

            .site-footer__contact-row svg {
                margin-top: 3px;
                color: #22c55e;
                flex-shrink: 0;
            }

            .site-footer__contact-row a {
                color: inherit;
                text-decoration: none;
                transition: color .18s ease;
            }
            .site-footer__contact-row a:hover { color: #15803d; }

            /* ---------- Bottom bar ---------- */
            .site-footer__bottom {
                max-width: 1200px;
                margin: 32px auto 0;
                padding-top: 20px;
                border-top: 1px solid #e2e8f0;
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                color: #64748b;
                font-size: 12.5px;
            }

            .site-footer__bottom p { margin: 0; }

            .site-footer__bottom-links {
                display: inline-flex;
                flex-wrap: wrap;
                gap: 16px;
            }

            .site-footer__bottom-links a {
                color: #64748b;
                text-decoration: none;
                transition: color .18s ease;
            }
            .site-footer__bottom-links a:hover { color: #15803d; }

            .site-footer__heart {
                display: inline-block;
                color: #ef4444;
                animation: footerHeart 1.6s ease-in-out infinite;
                transform-origin: center;
            }

            @keyframes footerHeart {
                0%, 100% { transform: scale(1); }
                50%      { transform: scale(1.18); }
            }

            .site-footer__up {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 38px;
                height: 38px;
                border: 1px solid #bbf7d0;
                border-radius: 10px;
                background: #fff;
                color: #15803d;
                cursor: pointer;
                transition: background .18s ease, transform .18s ease, box-shadow .18s ease;
            }

            .site-footer__up:hover {
                background: #f0fdf4;
                box-shadow: 0 6px 14px rgba(21,128,61,.14);
                transform: translateY(-2px);
            }

            /* ---------- Responsive ---------- */
            @media (max-width: 900px) {
                .site-footer__inner {
                    grid-template-columns: 1fr 1fr;
                }
                .site-footer__brand {
                    grid-column: 1 / -1;
                }
            }

            @media (max-width: 560px) {
                .site-footer { padding: 32px 16px 16px; }
                .site-footer__inner { grid-template-columns: 1fr; gap: 26px; }
                .site-footer__bottom {
                    flex-direction: column;
                    align-items: flex-start;
                }
            }

            @media (prefers-reduced-motion: reduce) {
                .site-footer * {
                    transition-duration: .01ms !important;
                    animation-duration: .01ms !important;
                }
            }
        </style>

        <footer class="site-footer" role="contentinfo">
            <div class="site-footer__inner">

                <!-- Brand -->
                <div class="site-footer__brand">
                    <span class="site-footer__logo" aria-hidden="true">
                        <svg viewBox="0 0 512 512" width="26" height="26" fill="currentColor" focusable="false">
                            <path d="M464 112c0-26.5-21.5-48-48-48c-5 0-9.9 .8-14.5 2.2L380 32c-6.4-6.4-16.5-7.4-24-2.4L320 53.6 283.9 29.6c-7.4-5-17.6-4-24 2.4L238.4 53.6 202.3 29.6c-7.4-5-17.6-4-24 2.4L157 66.2C152.4 64.8 147.5 64 142.5 64C116 64 94.5 85.5 94.5 112c0 3.6 .4 7.1 1.2 10.4C63.8 143.9 32 191.8 32 248c0 79.5 64.5 144 144 144h16v56c0 13.3 10.7 24 24 24s24-10.7 24-24V392h8v56c0 13.3 10.7 24 24 24s24-10.7 24-24v-56h16c79.5 0 144-64.5 144-144c0-56.2-31.8-104.1-77.7-125.6c.8-3.3 1.2-6.8 1.2-10.4zM176 288a32 32 0 1 1 0-64 32 32 0 1 1 0 64zm160 0a32 32 0 1 1 0-64 32 32 0 1 1 0 64z"/>
                        </svg>
                    </span>
                    <div class="site-footer__brand-text">
                        <p class="site-footer__brand-name"><?php echo htmlspecialchars($siteName); ?></p>
                        <p class="site-footer__brand-tag">
                            Smart livestock management for healthier herds and happier farmers.
                        </p>
                    </div>
                </div>

                <!-- Quick links -->
                <div class="site-footer__col">
                    <h4>Quick Links</h4>
                    <ul>
                        <li>
                            <a href="<?php echo htmlspecialchars($base); ?>/app/goats/dashboard.php">
                                <svg viewBox="0 0 512 512" width="12" height="12" fill="currentColor" aria-hidden="true" focusable="false"><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg>
                                Inventory
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo htmlspecialchars($base); ?>/app/goats/goat-create.php">
                                <svg viewBox="0 0 512 512" width="12" height="12" fill="currentColor" aria-hidden="true" focusable="false"><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM232 344V280H168c-13.3 0-24-10.7-24-24s10.7-24 24-24h64V168c0-13.3 10.7-24 24-24s24 10.7 24 24v64h64c13.3 0 24 10.7 24 24s-10.7 24-24 24H280v64c0 13.3-10.7 24-24 24s-24-10.7-24-24z"/></svg>
                                Add Goat
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo htmlspecialchars($base); ?>/app/goats/farmer-overview.php">
                                <svg viewBox="0 0 512 512" width="12" height="12" fill="currentColor" aria-hidden="true" focusable="false"><path d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512H418.3c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304H178.3z"/></svg>
                                Farmers
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Resources -->
                <div class="site-footer__col">
                    <h4>Resources</h4>
                    <ul>
                        <li>
                            <a href="<?php echo htmlspecialchars($base); ?>/app/help/help.php">
                                <svg viewBox="0 0 512 512" width="12" height="12" fill="currentColor" aria-hidden="true" focusable="false"><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM169.8 165.3c7.9-22.3 29.1-37.3 52.8-37.3h58.3c34.9 0 63.1 28.3 63.1 63.1c0 22.6-12.1 43.5-31.7 54.8L280 264.4c-.2 13-10.9 23.6-24 23.6c-13.3 0-24-10.7-24-24V250.5c0-8.6 4.6-16.5 12.1-20.8l44.3-25.4c4.7-2.7 7.6-7.7 7.6-13.1c0-8.4-6.8-15.1-15.1-15.1H222.6c-3.4 0-6.4 2.1-7.5 5.3l-.4 1.2c-4.4 12.5-18.2 19-30.6 14.6s-19-18.2-14.6-30.6l.4-1.2zM224 352a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>
                                Help Center
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo htmlspecialchars($base); ?>/app/help/guidelines.php">
                                <svg viewBox="0 0 512 512" width="12" height="12" fill="currentColor" aria-hidden="true" focusable="false"><path d="M64 144c0-13.3 10.7-24 24-24H424c13.3 0 24 10.7 24 24s-10.7 24-24 24H88c-13.3 0-24-10.7-24-24zm0 112c0-13.3 10.7-24 24-24H424c13.3 0 24 10.7 24 24s-10.7 24-24 24H88c-13.3 0-24-10.7-24-24zm24 88H424c13.3 0 24 10.7 24 24s-10.7 24-24 24H88c-13.3 0-24-10.7-24-24s10.7-24 24-24z"/></svg>
                                Guidelines
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo htmlspecialchars($base); ?>/app/help/faq.php">
                                <svg viewBox="0 0 512 512" width="12" height="12" fill="currentColor" aria-hidden="true" focusable="false"><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM232 128V256c0 10.6 5.2 20.5 14 26.5l72 48c14.7 9.8 34.5 5.8 44.3-8.9s5.8-34.5-8.9-44.3L280 236V128c0-17.7-14.3-32-32-32s-32 14.3-32 32z"/></svg>
                                FAQ
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Contact -->
                <div class="site-footer__col">
                    <h4>Get in Touch</h4>
                    <div class="site-footer__contact">
                        <div class="site-footer__contact-row">
                            <svg viewBox="0 0 512 512" width="14" height="14" fill="currentColor" aria-hidden="true" focusable="false"><path d="M48 64C21.5 64 0 85.5 0 112c0 15.1 7.1 29.3 19.2 38.4L236.8 313.6c11.4 8.5 27 8.5 38.4 0L492.8 150.4c12.1-9.1 19.2-23.3 19.2-38.4c0-26.5-21.5-48-48-48H48zM0 176V384c0 35.3 28.7 64 64 64H448c35.3 0 64-28.7 64-64V176L294.4 339.2c-22.8 17.1-54 17.1-76.8 0L0 176z"/></svg>
                            <a href="mailto:support@example.com">support@example.com</a>
                        </div>
                        <div class="site-footer__contact-row">
                            <svg viewBox="0 0 512 512" width="14" height="14" fill="currentColor" aria-hidden="true" focusable="false"><path d="M164.9 24.6c-7.7-18.6-28-28.5-47.4-23.2l-88 24C12.1 30.2 0 46 0 64C0 311.4 200.6 512 448 512c18 0 33.8-12.1 38.6-29.5l24-88c5.3-19.4-4.6-39.7-23.2-47.4l-96-40c-16.3-6.8-35.2-2.1-46.3 11.6L304.7 368C234.3 334.7 177.3 277.7 144 207.3L193.3 167c13.7-11.2 18.4-30 11.6-46.3l-40-96z"/></svg>
                            <a href="tel:+1234567890">+1 (234) 567-890</a>
                        </div>
                        <div class="site-footer__contact-row">
                            <svg viewBox="0 0 384 512" width="14" height="14" fill="currentColor" aria-hidden="true" focusable="false"><path d="M215.7 499.2C267 435 384 279.4 384 192C384 86 298 0 192 0S0 86 0 192c0 87.4 117 243 168.3 307.2c12.3 15.3 35.1 15.3 47.4 0zM192 128a64 64 0 1 1 0 128 64 64 0 1 1 0-128z"/></svg>
                            <span>Manolo Fortich, Bukidnon</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom bar -->
            <div class="site-footer__bottom">
                <p>
                    &copy; <?php echo $year; ?> <?php echo htmlspecialchars($siteName); ?>.
                    Made with <span class="site-footer__heart" aria-hidden="true">&hearts;</span> for farmers.
                </p>

                <div class="site-footer__bottom-links">
                    <a href="<?php echo htmlspecialchars($base); ?>/app/help/privacy.php">Privacy</a>
                    <a href="<?php echo htmlspecialchars($base); ?>/app/help/terms.php">Terms</a>
                    <button type="button"
                            class="site-footer__up"
                            aria-label="Back to top"
                            onclick="window.scrollTo({top:0, behavior:'smooth'});">
                        <svg viewBox="0 0 512 512" width="14" height="14" fill="currentColor" aria-hidden="true" focusable="false">
                            <path d="M233.4 105.4c12.5-12.5 32.8-12.5 45.3 0l192 192c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L256 173.3 86.6 342.6c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3l192-192z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </footer>
        <?php
    }
}