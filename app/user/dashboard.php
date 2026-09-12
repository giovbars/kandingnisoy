<?php
require_once '../../config/config.php';
require_once '../../config/functions.php';
require_once '../../includes/goat-functions.php';
require_once '../../includes/weather-forecast.php';

requireRole('user');

$userId = $_SESSION['user_id'];

// Weather forecast and farming tips
$weatherCacheKey = 'dashboard_weather_cache';
$weatherCacheTtl = 600; // 10 minutes

if (
    isset($_SESSION[$weatherCacheKey]['expires_at'], $_SESSION[$weatherCacheKey]['forecast'])
    && $_SESSION[$weatherCacheKey]['expires_at'] > time()
) {
    $weatherForecast = $_SESSION[$weatherCacheKey]['forecast'];
} else {
    $weatherForecast = getFarmWeatherForecast();

    $_SESSION[$weatherCacheKey] = [
        'forecast' => $weatherForecast,
        'expires_at' => time() + $weatherCacheTtl,
    ];
}

$tipsCacheKey = 'dashboard_farming_tips_cache';

if (
    isset($_SESSION[$tipsCacheKey]['expires_at'], $_SESSION[$tipsCacheKey]['tips'])
    && $_SESSION[$tipsCacheKey]['expires_at'] > time()
) {
    $farmingTips = $_SESSION[$tipsCacheKey]['tips'];
} else {
    $farmingTips = getGoatFarmingTips($weatherForecast);

    $_SESSION[$tipsCacheKey] = [
        'tips' => $farmingTips,
        'expires_at' => time() + $weatherCacheTtl,
    ];
}

// Goat inventory summary
$goatsTableCacheKey = 'dashboard_goats_table_exists';
$goatsTableCacheTtl = 3600; // 1 hour

if (
    isset($_SESSION[$goatsTableCacheKey]['expires_at'])
    && $_SESSION[$goatsTableCacheKey]['expires_at'] > time()
) {
    $goatsTableExists = $_SESSION[$goatsTableCacheKey]['exists'];
} else {
    $goatsTableExists = (bool) $pdo->query("SHOW TABLES LIKE 'goats'")->fetch();

    $_SESSION[$goatsTableCacheKey] = [
        'exists' => $goatsTableExists,
        'expires_at' => time() + $goatsTableCacheTtl,
    ];
}

$goatSummary = [
    'active' => 0,
    'male' => 0,
    'female' => 0,
    'healthy' => 0,
    'sick' => 0,
    'unknown' => 0,
];
$recentGoats = [];

if ($goatsTableExists) {
    $stmt = $pdo->prepare(
        "SELECT sex, health_status, COUNT(*) AS c
         FROM goats
         WHERE user_id = ? AND status = 'active'
         GROUP BY sex, health_status"
    );
    $stmt->execute([$userId]);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $count = (int) $row['c'];
        $goatSummary['active'] += $count;

        if (isset($goatSummary[$row['sex']])) {
            $goatSummary[$row['sex']] += $count;
        }

        if (isset($goatSummary[$row['health_status']])) {
            $goatSummary[$row['health_status']] += $count;
        }
    }

 $stmt = $pdo->prepare(
    "SELECT id, goat_id, goat_name, status, health_status, photo_path, updated_at
     FROM goats
     WHERE user_id = ?
     ORDER BY updated_at DESC
     LIMIT 5"
);
    $stmt->execute([$userId]);
    $recentGoats = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$title = 'User Dashboard';
renderHeader($title);
?>

<style>
    /* ==========================================================
       DASHBOARD DESIGN SYSTEM
       Clear hierarchy: page header > alert > summary > actions
       > weather guidance > recent records.
    ========================================================== */

    *,
    *::before,
    *::after {
        box-sizing: border-box;
    }

    html,
    body {
        min-height: 100%;
        margin: 0;
        padding: 0;
        background: #f8fafc;
        color: #172033;
        font-family: system-ui, -apple-system, BlinkMacSystemFont,
            "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        overflow-x: hidden;
    }

    img,
    svg {
        max-width: 100%;
        height: auto;
    }

    .main-content {
        width: 100%;
        max-width: 100%;
        min-height: 100vh;
        margin: 0 auto;
        background: #f8fafc;
        overflow-x: hidden;
    }

    .main-content .container {
        width: 100%;
        max-width: none;
        min-height: 100vh;
        margin: 0 auto;
        padding: 0;
    }

    .dashboard-wrap {
        width: min(100%, 1200px);
        max-width: 1200px;
        margin: 0 auto;
        padding: 24px clamp(16px, 3vw, 36px) 48px;
    }

    /* ---------- Typography and section hierarchy ---------- */

    .dashboard-section {
        margin-bottom: 32px;
    }

    .section-heading {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 16px;
    }

    .eyebrow {
        margin: 0 0 4px;
        color: #16a34a;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.08em;
        line-height: 1.2;
        text-transform: uppercase;
    }

    .section-heading h2,
    .section-title {
        margin: 0;
        color: #172033;
        font-size: 22px;
        font-weight: 750;
        line-height: 1.25;
    }

    .section-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 16px;
    }

    .section-title-between {
        justify-content: space-between;
    }

    .section-title i,
    .section-heading h2 i {
        color: #16a34a;
    }

    .section-description {
        margin: 6px 0 0;
        color: #64748b;
        font-size: 14px;
        line-height: 1.5;
    }

    /* ---------- Header ---------- */

    .header-grass {
        position: relative;
        overflow: hidden;
        margin-bottom: 18px;
        padding: 24px;
        border-radius: 16px;
        background: linear-gradient(135deg, #166534, #22c55e);
        box-shadow: 0 8px 20px rgba(22, 101, 52, 0.18);
    }

    .header-grass::after {
        position: absolute;
        right: -45px;
        bottom: -70px;
        width: 190px;
        height: 190px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
        content: "";
        pointer-events: none;
    }

    .header-inner {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
    }

    .header-title-row {
        display: flex;
        align-items: center;
        min-width: 0;
        gap: 14px;
    }

    .header-goat {
        flex-shrink: 0;
        font-size: 40px;
        line-height: 1;
    }

    .header-grass h1 {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
        margin: 0;
        color: #ffffff;
        font-size: 30px;
        font-weight: 800;
        line-height: 1.15;
    }

    .header-grass p {
        margin: 8px 0 0;
        overflow-wrap: anywhere;
        color: rgba(255, 255, 255, 0.86);
        font-size: 15px;
    }

    .header-role-badge,
    .header-date-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        color: #ffffff;
        font-weight: 650;
    }

    .header-role-badge {
        padding: 4px 10px;
        background: rgba(255, 255, 255, 0.18);
        font-size: 12px;
    }

    .header-date-badge {
        padding: 9px 13px;
        background: rgba(0, 0, 0, 0.13);
        font-size: 13px;
        white-space: nowrap;
    }

    /* ---------- Navigation ---------- */

    .dash-nav {
        display: flex;
        gap: 4px;
        overflow-x: auto;
        margin-bottom: 28px;
        padding: 0 0 10px;
        border-bottom: 1px solid #dbe4ea;
        scrollbar-width: thin;
    }

    .dash-nav-link {
        display: inline-flex;
        flex: 0 0 auto;
        align-items: center;
        min-height: 40px;
        gap: 7px;
        padding: 8px 14px;
        border-radius: 8px;
        color: #64748b;
        font-size: 14px;
        font-weight: 650;
        text-decoration: none;
        white-space: nowrap;
        transition: color 0.2s ease, background 0.2s ease, transform 0.2s ease;
    }

    .dash-nav-link:hover,
    .dash-nav-link.active {
        background: #ecfdf5;
        color: #15803d;
    }

    .dash-nav-link:hover {
        transform: translateY(-1px);
    }

    .dash-nav-link:active {
        transform: translateY(0);
    }

    .dash-nav-link i {
        color: #22c55e;
    }

    .dash-nav-link.logout {
        margin-left: auto;
        color: #dc2626;
    }

    .dash-nav-link.logout i {
        color: #dc2626;
    }

    /* ---------- Alert ---------- */

    .alert-warning {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 28px;
        padding: 16px 18px;
        border: 1px solid #fcd34d;
        border-left: 5px solid #f59e0b;
        border-radius: 10px;
        background: #fffbeb;
        color: #92400e;
        font-size: 14px;
        line-height: 1.5;
    }

    .alert-warning > i {
        flex-shrink: 0;
        margin-top: 2px;
        color: #d97706;
        font-size: 18px;
    }

    .alert-warning a {
        display: inline-flex;
        align-items: center;
        min-height: 36px;
        margin-left: 8px;
        color: #92400e;
        font-weight: 750;
        text-decoration: underline;
    }

    /* ---------- Buttons ---------- */

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 40px;
        gap: 7px;
        padding: 9px 15px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        transition: background 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        cursor: pointer;
    }

    .btn-primary {
        border: 1px solid #15803d;
        background: #15803d;
        color: #ffffff;
        box-shadow: 0 4px 10px rgba(21, 128, 61, 0.18);
    }

    .btn-primary:hover {
        background: #166534;
        box-shadow: 0 6px 14px rgba(21, 128, 61, 0.24);
        transform: translateY(-1px);
    }

    .btn-secondary {
        border: 1px solid #bbf7d0;
        background: #ffffff;
        color: #15803d;
    }

    .btn-secondary:hover {
        background: #f0fdf4;
        border-color: #86efac;
        box-shadow: 0 4px 10px rgba(21, 128, 61, 0.12);
        transform: translateY(-1px);
    }

    .btn:active {
        box-shadow: none;
        transform: translateY(1px);
    }

    .btn:focus-visible,
    .dash-nav-link:focus-visible,
    .goat-card-id:focus-visible,
    .alert-warning a:focus-visible {
        outline: 3px solid rgba(59, 130, 246, 0.35);
        outline-offset: 3px;
    }

    /* ---------- Summary and statistics ---------- */

    .overview-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 16px;
        align-items: stretch;
    }

    .stat-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .stat-card {
        display: flex;
        align-items: center;
        min-height: 112px;
        gap: 12px;
        padding: 18px;
        border: 1px solid #e2e8f0;
        border-left: 5px solid #22c55e;
        border-radius: 12px;
        background: #ffffff;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
    }

    .stat-card > i {
        flex-shrink: 0;
        color: #22c55e;
        font-size: 22px;
    }

    .stat-text {
        min-width: 0;
    }

    .stat-card .number {
        margin-bottom: 5px;
        color: #111827;
        font-size: 32px;
        font-weight: 800;
        line-height: 1;
        overflow-wrap: anywhere;
    }

    .stat-card .label {
        color: #64748b;
        font-size: 11px;
        font-weight: 750;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    .stat-card.stat-danger {
        border-left-color: #ef4444;
        background: #fff7ed;
    }

    .stat-card.stat-danger > i,
    .stat-card.stat-danger .number {
        color: #dc2626;
    }

    .quick-actions {
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-width: 180px;
        gap: 10px;
        padding: 16px;
        border: 1px solid #dcfce7;
        border-radius: 12px;
        background: #f0fdf4;
    }

    /* ---------- General cards ---------- */

    .card {
        padding: 18px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #ffffff;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
    }

    .badge-sm {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 10px;
        border-radius: 999px;
        background: #dcfce7;
        color: #166534;
        font-size: 12px;
        font-weight: 700;
    }

    /* ---------- Weather ---------- */

    .weather-scroll {
        display: flex;
        gap: 12px;
        overflow-x: auto;
        margin-bottom: 18px;
        padding: 4px 2px 12px;
        scroll-snap-type: x proximity;
        scrollbar-color: #bbf7d0 transparent;
        scrollbar-width: thin;
    }

    .weather-card {
        flex: 0 0 190px;
        overflow: hidden;
        border: 1px solid #dbe4ea;
        border-radius: 12px;
        background: #ffffff;
        box-shadow: 0 3px 8px rgba(15, 23, 42, 0.06);
        scroll-snap-align: start;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }

    .weather-card:hover {
        border-color: #86efac;
        box-shadow: 0 8px 18px rgba(21, 128, 61, 0.14);
        transform: translateY(-3px);
    }

    .weather-card.today {
        border: 2px solid #22c55e;
    }

    .weather-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 12px;
        background: #166534;
    }

    .weather-card-head span:first-child {
        color: #ffffff;
        font-size: 13px;
        font-weight: 800;
    }

    .weather-card-head span:last-child {
        color: rgba(255, 255, 255, 0.8);
        font-size: 12px;
        font-weight: 650;
    }

    .weather-card-body {
        padding: 12px;
    }

    .weather-period-label {
        margin-bottom: 6px;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .weather-desc {
        margin: 4px 0 7px;
        color: #334155;
        font-size: 13px;
        font-weight: 650;
    }

    .weather-meta,
    .weather-rain,
    .weather-total {
        color: #475569;
        font-size: 12px;
    }

    .weather-meta {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }

    .weather-rain {
        margin-top: 3px;
    }

    .weather-divider {
        height: 1px;
        margin: 10px 0;
        background: #e2e8f0;
    }

    .weather-total {
        text-align: center;
    }

    /* ---------- Farming tips ---------- */

    .tips-box {
        margin-top: 18px;
        padding: 20px 22px;
        border: 1px solid #bbf7d0;
        border-radius: 12px;
        background: #f0fdf4;
    }

    .tips-box h3 {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0 0 8px;
        color: #166534;
        font-size: 16px;
        font-weight: 750;
        line-height: 1.3;
    }

    .tips-grid {
        display: flex;
        flex-direction: column;
        gap: 0;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .tip-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 11px 4px;
        border-bottom: 1px solid #bbf7d0;
    }

    .tip-item:last-child {
        padding-bottom: 0;
        border-bottom: none;
    }

    .tip-item i {
        flex-shrink: 0;
        width: 16px;
        margin-top: 3px;
        font-size: 12px;
        text-align: center;
    }

    .tip-item span {
        flex: 1;
        display: block;
        color: #334155;
        font-size: 14px;
        line-height: 1.5;
    }

    /* ---------- Recent goats ---------- */

    .recent-goats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
        gap: 12px;
    }

    .goat-card {
        min-width: 0;
        padding: 14px;
        border: 1px solid #e2e8f0;
        border-left: 5px solid #22c55e;
        border-radius: 10px;
        background: #ffffff;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }

    .goat-card:hover {
        border-color: #86efac;
        box-shadow: 0 7px 16px rgba(15, 23, 42, 0.1);
        transform: translateY(-3px);
    }

    .goat-card-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 8px;
    }

    .goat-card-id {
        display: flex;
        align-items: center;
        min-height: 36px;
        gap: 6px;
        color: #15803d;
        font-size: 15px;
        font-weight: 800;
        text-decoration: none;
        transition: color 0.2s ease;
    }

    .goat-card-id:hover {
        color: #166534;
        text-decoration: underline;
    }

    .goat-card-name {
        margin-top: 2px;
        overflow-wrap: anywhere;
        color: #1e293b;
        font-size: 16px;
        font-weight: 650;
    }

    .goat-card-date {
        color: #94a3b8;
        font-size: 12px;
        white-space: nowrap;
    }

    .goat-card-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
        margin-top: 10px;
    }

    .goat-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
    }

    .empty-state {
        padding: 32px 16px;
        color: #64748b;
        text-align: center;
    }

    .empty-state i {
        display: block;
        margin-bottom: 10px;
        color: #86efac;
        font-size: 40px;
    }

    .empty-state p {
        margin: 0;
        font-size: 14px;
    }

    /* ---------- Responsive layout ---------- */

    @media (max-width: 900px) {
        .dashboard-wrap {
            width: 100%;
            padding-right: 24px;
            padding-left: 24px;
        }

        .overview-layout {
            grid-template-columns: 1fr;
        }

        .quick-actions {
            flex-direction: row;
            align-items: center;
            justify-content: flex-start;
            min-width: 0;
        }

    }

    @media (max-width: 700px) {
        .dashboard-wrap {
            width: 100%;
            padding: 16px 12px 36px;
        }

        .header-grass {
            padding: 20px 16px;
        }

        .header-inner {
            align-items: flex-start;
            flex-direction: column;
        }

        .header-grass h1 {
            font-size: 24px;
        }

        .header-goat {
            font-size: 34px;
        }

        .stat-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .weather-scroll {
            margin-right: -2px;
            margin-left: -2px;
        }

        .stat-card {
            min-height: 100px;
            padding: 14px;
        }

        .stat-card .number {
            font-size: 27px;
        }

        .section-heading {
            align-items: flex-start;
            flex-direction: column;
        }

        .section-heading h2,
        .section-title {
            font-size: 19px;
        }

        .section-title-between {
            align-items: flex-start;
            flex-direction: column;
        }

        .alert-warning {
            flex-wrap: wrap;
        }

        .alert-warning a {
            margin-left: 0;
        }
    }

    @media (max-width: 430px) {
        .dashboard-wrap {
            padding-right: 10px;
            padding-left: 10px;
        }

        .tips-box {
            padding: 16px;
        }

        .quick-actions {
            align-items: stretch;
            flex-direction: column;
        }

        .stat-card {
            align-items: flex-start;
            flex-direction: column;
            gap: 8px;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        *,
        *::before,
        *::after {
            scroll-behavior: auto !important;
            transition-duration: 0.01ms !important;
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
        }
    }

    /* ==========================================================
       UI POLISH ONLY
       Keeps the existing HTML structure and layout.
       No sections/cards/navigation are removed or rearranged.
    ========================================================== */

    .dashboard-wrap {
        padding-top: 22px;
        padding-bottom: 42px;
    }

    .dashboard-section {
        margin-bottom: 30px;
    }

    .header-grass {
        margin-bottom: 16px;
        border-radius: 18px;
        background:
            radial-gradient(circle at 88% 18%, rgba(255,255,255,.14) 0 70px, transparent 71px),
            linear-gradient(135deg, #14532d 0%, #15803d 55%, #22c55e 100%);
        box-shadow: 0 10px 26px rgba(22,101,52,.16);
    }

    .header-grass h1 {
        letter-spacing: -0.025em;
    }

    .header-role-badge {
        background: rgba(255,255,255,.16);
        border: 1px solid rgba(255,255,255,.18);
        backdrop-filter: blur(4px);
    }

    .header-date-badge {
        background: rgba(0,0,0,.12);
        border: 1px solid rgba(255,255,255,.12);
    }

    .dash-nav {
        gap: 2px;
        margin-bottom: 26px;
        border-bottom-color: #d7e2dc;
    }

    .dash-nav-link {
        border-radius: 9px;
        color: #64748b;
    }

    .dash-nav-link:hover,
    .dash-nav-link.active {
        background: #f0fdf4;
        color: #166534;
    }

    .alert-warning {
        margin-bottom: 26px;
        border-radius: 12px;
        box-shadow: 0 3px 10px rgba(146,64,14,.06);
    }

    .section-heading {
        margin-bottom: 14px;
    }

    .eyebrow {
        color: #15803d;
    }

    .section-heading h2,
    .section-title {
        letter-spacing: -0.015em;
    }

    /* Keep the existing statistic layout, but make it lighter and cleaner. */
    .stat-card {
        min-height: 104px;
        padding: 16px 18px;
        border-color: #e5ebe7;
        border-left-width: 4px;
        border-radius: 11px;
        box-shadow: 0 2px 8px rgba(15,23,42,.045);
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }

    .stat-card:hover {
        border-color: #cde8d5;
        box-shadow: 0 7px 16px rgba(15,23,42,.07);
        transform: translateY(-2px);
    }

    .stat-card .number {
        letter-spacing: -0.035em;
    }

    .stat-card .label {
        color: #64748b;
    }

    .stat-card.stat-danger {
        background: #fffafa;
        border-left-color: #ef4444;
    }

    .quick-actions {
        min-width: 176px;
        padding: 14px;
        border: 1px solid #d8eadc;
        border-radius: 11px;
        background: linear-gradient(180deg, #f7fdf8, #f0fdf4);
        box-shadow: 0 2px 8px rgba(21,128,61,.04);
    }

    .btn {
        border-radius: 8px;
    }

    .btn-primary {
        box-shadow: 0 3px 8px rgba(21,128,61,.14);
    }

    .btn-secondary {
        border-color: #d5e6d9;
        background: #ffffff;
    }

    /* Weather keeps the exact existing horizontal layout. */
    .weather-scroll {
        gap: 10px;
        padding-top: 2px;
    }

    .weather-card {
        border-color: #dfe7e2;
        border-radius: 11px;
        box-shadow: 0 2px 8px rgba(15,23,42,.045);
    }

    .weather-card:hover {
        box-shadow: 0 7px 16px rgba(21,128,61,.10);
    }

    .weather-card.today {
        border-width: 2px;
        box-shadow: 0 4px 12px rgba(21,128,61,.09);
    }

    .weather-card-head {
        background: linear-gradient(135deg, #166534, #15803d);
    }

    .tips-box {
        margin-top: 16px;
        border-color: #cfe8d5;
        border-radius: 11px;
        background: #f6fcf7;
        box-shadow: 0 2px 8px rgba(21,128,61,.035);
    }

    .tips-box h3 {
        color: #166534;
    }

    .tip-item {
        padding-top: 12px;
        padding-bottom: 12px;
        border-bottom-color: #dceee0;
    }

    /* Recent goat records keep the same grid/card structure. */
    .card {
        border-color: #e2e8f0;
        border-radius: 11px;
        box-shadow: 0 2px 8px rgba(15,23,42,.045);
    }

    .goat-card {
        border-color: #e3e9e5;
        border-left-width: 4px;
        border-radius: 10px;
        box-shadow: 0 2px 7px rgba(15,23,42,.04);
    }

    .goat-card:hover {
        box-shadow: 0 7px 15px rgba(15,23,42,.075);
    }

    .goat-card-name {
        color: #1e293b;
    }

    .goat-card-date {
        color: #94a3b8;
    }

    @media (max-width: 700px) {
        .header-grass {
            border-radius: 14px;
        }

        .stat-card {
            min-height: 96px;
        }

        .quick-actions {
            box-shadow: none;
        }
    }

</style>

<main class="dashboard-wrap">
    <!-- Page identity -->
    <header class="header-grass">
        <div class="header-inner">
            <div class="header-title-row">
                <span class="header-goat" aria-hidden="true">🐐</span>
                <div style="min-width: 0;">
                    <h1>
                        Farmer Dashboard
                        <span class="header-role-badge">User</span>
                    </h1>
                    <p>
                        <i class="fas fa-leaf" aria-hidden="true"></i>
                        Welcome back, <?php echo htmlspecialchars(substr($_SESSION['email'], 0, 20)); ?>
                    </p>
                </div>
            </div>

            <span class="header-date-badge">
                <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                <?php echo date('M d, Y'); ?>
            </span>
        </div>
    </header>

    <!-- Primary navigation -->
    <?php $isDashboard = basename($_SERVER['PHP_SELF']) === 'dashboard.php'; ?>
    <nav class="dash-nav" aria-label="Dashboard navigation">
        <?php if ($isDashboard): ?>
            <span class="dash-nav-link active" aria-current="page">
                <i class="fas fa-tachometer-alt" aria-hidden="true"></i>
                Dashboard
            </span>
        <?php else: ?>
            <a href="dashboard.php" class="dash-nav-link">
                <i class="fas fa-tachometer-alt" aria-hidden="true"></i>
                Dashboard
            </a>
        <?php endif; ?>

        <a href="<?php echo BASE_URL; ?>/app/goats/dashboard.php" class="dash-nav-link">
            <i class="fas fa-goat" aria-hidden="true"></i>
            Inventory
        </a>

        <a href="<?php echo BASE_URL; ?>/app/goats/goat-create.php" class="dash-nav-link">
            <i class="fas fa-plus-circle" aria-hidden="true"></i>
            Add Goat
        </a>

        <a href="<?php echo BASE_URL; ?>/app/auth/signout.php" class="dash-nav-link logout">
            <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
            Logout
        </a>
    </nav>

    <!-- Urgent information appears before ordinary content -->
    <?php if ($goatSummary['sick'] > 0): ?>
        <aside class="alert-warning" role="alert">
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            <div>
                <strong>Attention:</strong>
                You have <strong><?php echo $goatSummary['sick']; ?></strong>
                sick goat(s). Please check their health status and provide care.
                <a href="<?php echo BASE_URL; ?>/app/goats/dashboard.php?filter=sick">
                    View sick goats
                </a>
            </div>
        </aside>
    <?php endif; ?>

    <!-- Primary information: summary and actions -->
    <section class="dashboard-section" aria-labelledby="summary-heading">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Farm overview</p>
                <h2 id="summary-heading">Goat Summary</h2>
                <p class="section-description">
                    A quick view of the current status of your goat inventory.
                </p>
            </div>
        </div>

        <div class="overview-layout">
            <div class="stat-grid">
                <div class="stat-card">
                    <i class="fas fa-check-circle" aria-hidden="true"></i>
                    <div class="stat-text">
                        <div class="number"><?php echo $goatSummary['active']; ?></div>
                        <div class="label">Active Goats</div>
                    </div>
                </div>

                <div class="stat-card" style="border-left-color: #4ade80;">
                    <i class="fas fa-heart" style="color: #4ade80;" aria-hidden="true"></i>
                    <div class="stat-text">
                        <div class="number"><?php echo $goatSummary['healthy']; ?></div>
                        <div class="label">Healthy</div>
                    </div>
                </div>

                <div class="stat-card stat-danger">
                    <i class="fas fa-medkit" aria-hidden="true"></i>
                    <div class="stat-text">
                        <div class="number"><?php echo $goatSummary['sick']; ?></div>
                        <div class="label">Sick Goats</div>
                    </div>
                </div>

                <div class="stat-card" style="border-left-color: #60a5fa;">
                    <i class="fas fa-venus-mars" style="color: #60a5fa;" aria-hidden="true"></i>
                    <div class="stat-text">
                        <div class="number">
                            <?php echo $goatSummary['male']; ?>/<?php echo $goatSummary['female']; ?>
                        </div>
                        <div class="label">Male / Female</div>
                    </div>
                </div>
            </div>

            <div class="quick-actions" aria-label="Quick actions">
                <a href="<?php echo BASE_URL; ?>/app/goats/goat-create.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle" aria-hidden="true"></i>
                    Add New Goat
                </a>
                <a href="<?php echo BASE_URL; ?>/app/goats/dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-list" aria-hidden="true"></i>
                    View Inventory
                </a>
            </div>
        </div>
    </section>

    <!-- Secondary information: weather and farming guidance -->
    <section class="dashboard-section" aria-labelledby="weather-heading">
        <h2 class="section-title" id="weather-heading">
            <i class="fas fa-cloud-sun" aria-hidden="true"></i>
            Today's Weather and Tips
            <span class="badge-sm">
                <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                <?php echo htmlspecialchars($weatherForecast['location'] ?? 'Manolo Fortich, Bukidnon'); ?>
            </span>
        </h2>

        <?php if ($weatherForecast && !empty($weatherForecast['days'])): ?>
            <div class="weather-scroll" aria-label="Weather forecast">
                <?php foreach ($weatherForecast['days'] as $di => $day):
                    $dateTs = strtotime($day['date']);
                    $dayLabel = $di === 0 ? 'Today' : date('l', $dateTs);
                    $dateLabel = date('m/d', $dateTs);
                    $dayInfo = $day['day'];
                    $nightInfo = $day['night'];
                    $dayVisual = $dayInfo['available']
                        ? getPeriodWeatherDisplay($dayInfo['weathercode'], $dayInfo['pop'], false)
                        : null;
                    $nightVisual = $nightInfo['available']
                        ? getPeriodWeatherDisplay($nightInfo['weathercode'], $nightInfo['pop'], true)
                        : null;
                    $dayText = $dayInfo['available']
                        ? weatherDetailedLabel($dayInfo['weathercode'], $dayInfo['pop'], false)
                        : null;
                    $nightText = $nightInfo['available']
                        ? weatherDetailedLabel($nightInfo['weathercode'], $nightInfo['pop'], true)
                        : null;
                    $totalMm = $day['total_mm'] ?? 0;
                ?>
                    <article class="weather-card<?php echo $di === 0 ? ' today' : ''; ?>">
                        <div class="weather-card-head">
                            <span><?php echo htmlspecialchars($dayLabel); ?></span>
                            <span><?php echo htmlspecialchars($dateLabel); ?></span>
                        </div>

                        <div class="weather-card-body">
                            <div style="text-align: center;">
                                <div class="weather-period-label" style="color: #16a34a;">Adlaw</div>

                                <?php if ($dayInfo['available']): ?>
                                    <div style="display: flex; justify-content: center;">
                                        <?php echo renderWeatherIcon($dayVisual['type'], 40); ?>
                                    </div>
                                    <div class="weather-desc">
                                        <?php echo htmlspecialchars($dayText); ?>
                                    </div>
                                    <div class="weather-meta">
                                        <i class="fas fa-tint" style="color: #3b82f6;" aria-hidden="true"></i>
                                        <strong><?php echo $dayInfo['pop']; ?>%</strong> chance
                                    </div>
                                    <div class="weather-rain">
                                        Rainfall:
                                        <?php if ($dayInfo['rain_mm'] < 1): ?>
                                            <span style="color: #94a3b8;">&lt; 1 mm</span>
                                        <?php else: ?>
                                            <strong style="color: #2563eb;">
                                                <?php echo number_format($dayInfo['rain_mm'], 2); ?> mm
                                            </strong>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div style="padding: 14px 0; color: #94a3b8; font-size: 12px;">
                                        No data
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="weather-divider"></div>

                            <div style="text-align: center;">
                                <div class="weather-period-label" style="color: #2563eb;">Gabii</div>

                                <?php if ($nightInfo['available']): ?>
                                    <div style="display: flex; justify-content: center;">
                                        <?php echo renderWeatherIcon($nightVisual['type'], 40); ?>
                                    </div>
                                    <div class="weather-desc">
                                        <?php echo htmlspecialchars($nightText); ?>
                                    </div>
                                    <div class="weather-meta">
                                        <i class="fas fa-tint" style="color: #3b82f6;" aria-hidden="true"></i>
                                        <strong><?php echo $nightInfo['pop']; ?>%</strong> chance
                                    </div>
                                    <div class="weather-rain">
                                        Rainfall:
                                        <?php if ($nightInfo['rain_mm'] < 1): ?>
                                            <span style="color: #94a3b8;">&lt; 1 mm</span>
                                        <?php else: ?>
                                            <strong style="color: #2563eb;">
                                                <?php echo number_format($nightInfo['rain_mm'], 2); ?> mm
                                            </strong>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div style="padding: 14px 0; color: #94a3b8; font-size: 12px;">
                                        No data
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="weather-divider"></div>

                            <div class="weather-total">
                                <i class="fas fa-umbrella"
                                   style="color: <?php echo $totalMm >= 1 ? '#dc2626' : '#94a3b8'; ?>;"
                                   aria-hidden="true"></i>
                                Total:
                                <strong style="color: <?php echo $totalMm >= 1 ? '#dc2626' : '#94a3b8'; ?>;">
                                    <?php echo $totalMm < 1 ? '&lt; 1 mm' : number_format($totalMm, 2) . ' mm'; ?>
                                </strong>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="tips-box">
                <h3>
                    <i class="fas fa-lightbulb" style="color: #f59e0b;" aria-hidden="true"></i>
                    Farming Tips for Today
                </h3>

                <ul class="tips-grid">
                    <?php foreach ($farmingTips as $tip):
                        $tipColor = $tip['color'] ?? '#22c55e';
                    ?>
                        <li class="tip-item">
                            <i class="fas fa-check-circle"
                               style="color: <?php echo htmlspecialchars($tipColor); ?>;"
                               aria-hidden="true"></i>
                            <span><?php echo htmlspecialchars($tip['text'] ?? ''); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <div class="card empty-state">
                <i class="fas fa-cloud-slash" aria-hidden="true"></i>
                <p>Weather forecast temporarily unavailable.</p>
            </div>
        <?php endif; ?>
    </section>

    <!-- Detailed information: recent records -->
    <section class="dashboard-section" aria-labelledby="recent-heading">
        <div class="card">
            <div class="section-title section-title-between">
                <h2 id="recent-heading" class="section-title" style="margin-bottom: 0;">
                    <i class="fas fa-clock" aria-hidden="true"></i>
                    Recently Added or Updated Goats
                </h2>

                <a href="<?php echo BASE_URL; ?>/app/goats/dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    View All
                </a>
            </div>

            <?php if (empty($recentGoats)): ?>
                <div class="empty-state">
                    <i class="fas fa-goat" aria-hidden="true"></i>
                    <p>No goat records yet.</p>
                    <a href="<?php echo BASE_URL; ?>/app/goats/goat-create.php" class="btn btn-primary" style="margin-top: 12px;">
                        <i class="fas fa-plus-circle" aria-hidden="true"></i>
                        Add Your First Goat
                    </a>
                </div>
            <?php else: ?>
                <div class="recent-goats-grid">
                    <?php foreach ($recentGoats as $goat):
                        $healthColor = $goat['health_status'] === 'healthy'
                            ? '#22c55e'
                            : ($goat['health_status'] === 'sick' ? '#ef4444' : '#94a3b8');
                        $healthBg = $goat['health_status'] === 'healthy'
                            ? '#dcfce7'
                            : ($goat['health_status'] === 'sick' ? '#fef3c7' : '#f3f4f6');
                        $healthText = $goat['health_status'] === 'healthy'
                            ? '#166534'
                            : ($goat['health_status'] === 'sick' ? '#d97706' : '#6b7280');
                    ?>
                       <article class="goat-card" style="border-left-color: <?php echo $healthColor; ?>;">
                           <div class="goat-card-top">
    <div style="display:flex; align-items:center; gap:10px; min-width:0; flex:1;">
        <?php if (!empty($goat['photo_path'])): ?>
            <img src="<?php echo BASE_URL . '/app/goats/' . htmlspecialchars($goat['photo_path']); ?>"
                 alt="Photo of <?php echo htmlspecialchars($goat['goat_name']); ?>"
                 style="width:48px; height:48px; border-radius:8px; object-fit:cover; border:1px solid #e2e8f0; flex-shrink:0;">
        <?php else: ?>
            <div style="width:48px; height:48px; border-radius:8px; background:#f0fdf4; border:1px solid #dcfce7; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i class="fas fa-goat" style="color:#86efac; font-size:18px;" aria-hidden="true"></i>
            </div>
        <?php endif; ?>

        <div style="min-width: 0;">
<a href="<?php echo BASE_URL; ?>/app/goats/goat-view.php?id=<?php echo urlencode($goat['id']); ?>"
   class="goat-card-id">
    <i class="fas fa-tag" style="color: #86efac;" aria-hidden="true"></i>
    <?php echo htmlspecialchars($goat['goat_id']); ?>
</a>
            <div class="goat-card-name">
                <?php echo htmlspecialchars($goat['goat_name']); ?>
            </div>
        </div>
    </div>

    <span class="goat-card-date">
        <i class="fas fa-calendar-alt" aria-hidden="true"></i>
        <?php echo date('M d', strtotime($goat['updated_at'])); ?>
    </span>
</div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php renderFooter(); ?>
