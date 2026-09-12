<?php
require_once '../../config/config.php';
require_once '../../config/functions.php';
require_once '../../includes/goat-functions.php';
require_once '../../includes/weather-forecast.php';

requireRole('user');

$userId = $_SESSION['user_id'];

// ---- Weather Forecast + Farming Tips ----
$weatherForecast = getFarmWeatherForecast();
$farmingTips = getGoatFarmingTips($weatherForecast);

// ---- Goat Inventory Summary ----
$goatsTableExists = $pdo->query("SHOW TABLES LIKE 'goats'")->fetch();
$goatSummary = [
    'active' => 0, 'male' => 0, 'female' => 0,
    'healthy' => 0, 'sick' => 0, 'unknown' => 0,
];
$recentGoats = [];
if ($goatsTableExists) {
    $stmt = $pdo->prepare("SELECT sex, health_status, COUNT(*) as c FROM goats WHERE user_id = ? AND status = 'active' GROUP BY sex, health_status");
    $stmt->execute([$userId]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $goatSummary['active'] += (int)$row['c'];
        $goatSummary[$row['sex']] = ($goatSummary[$row['sex']] ?? 0) + (int)$row['c'];
        $goatSummary[$row['health_status']] = ($goatSummary[$row['health_status']] ?? 0) + (int)$row['c'];
    }

    $stmt = $pdo->prepare("SELECT goat_id, goat_name, status, health_status, updated_at FROM goats WHERE user_id = ? ORDER BY updated_at DESC LIMIT 5");
    $stmt->execute([$userId]);
    $recentGoats = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$title = 'User Dashboard';
renderHeader($title);
?>

<!--
    NOTE: this page is styled mobile-first. If renderHeader() does not already
    emit <meta name="viewport" content="width=device-width, initial-scale=1">,
    add it there — otherwise phones will render this at desktop width and
    scale it down instead of actually reflowing.
-->

<!-- ============================================================
     MOBILE-FIRST DASHBOARD STYLES
     Base rules = phones. min-width queries progressively enhance
     for tablet (>=641px) and desktop (>=1024px).
============================================================ -->
<style>
    /* ---------- Reset / safety net against horizontal scroll ---------- */
    *, *::before, *::after { box-sizing: border-box; }
    html, body {
        height: 100%;
        min-height: 100vh;
        margin: 0;
        padding: 0;
        background: #f0fdf4;
        font-size: 16px;
        font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
        overflow-x: hidden;
        width: 100%;
    }
    img, svg { max-width: 100%; height: auto; }

    .main-content {
        width: 100%;
        max-width: 100%;
        padding: 0;
        margin: 0;
        min-height: 100vh;
        background: #f0fdf4;
        display: block;
        overflow-x: hidden;
    }
    .main-content .container {
        width: 100%;
        max-width: 1400px;
        padding: 16px 12px 28px 12px;
        margin: 0 auto;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        background: #f0fdf4;
        box-sizing: border-box;
    }
    @media (min-width: 641px) {
        .main-content .container { padding: 28px 24px 36px 24px; }
    }
    @media (min-width: 1024px) {
        .main-content .container { padding: 40px 36px 40px 36px; }
    }

    .dashboard-wrap {
        width: 100%;
        display: flex;
        flex-direction: column;
        background: #f0fdf4;
        min-width: 0; /* prevents flex children from forcing overflow */
    }

    /* ---------- Shared card / button primitives ---------- */
    .card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        border: 1px solid #dcfce7;
        padding: 14px;
        min-width: 0;
    }
    @media (min-width: 641px) { .card { padding: 16px 18px; } }

    .card-title {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        font-size: 15px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 12px;
    }
    @media (min-width: 641px) { .card-title { font-size: 16px; } }

    .badge-sm {
        font-size: 12px;
        padding: 4px 12px;
        border-radius: 999px;
        font-weight: 600;
        display: inline-block;
        white-space: nowrap;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        min-height: 44px;
        padding: 10px 18px;
        border-radius: 999px;
        font-weight: 500;
        font-size: 14px;
        text-decoration: none;
        transition: all 0.2s;
        border: none;
        cursor: pointer;
    }
    .btn-primary {
        background: linear-gradient(145deg, #22C55E, #15803D);
        color: white;
        box-shadow: 0 4px 10px rgba(34,197,94,0.25);
    }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(34,197,94,0.3); }

    .quick-action {
        background: white;
        border-radius: 12px;
        padding: 14px 10px;
        min-height: 84px;
        text-align: center;
        box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        border: 1px solid #dcfce7;
        transition: 0.2s;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        color: #1f2937;
    }
    .quick-action:hover { transform: translateY(-3px); box-shadow: 0 8px 16px rgba(34,197,94,0.12); }
    .quick-action span { font-size: 13px; font-weight: 600; margin-top: 4px; }
    .quick-action i { font-size: 24px; }
    @media (min-width: 641px) {
        .quick-action i { font-size: 28px; }
        .quick-action span { font-size: 14px; }
    }

    .alert-warning {
        background: #fef3c7;
        border-left: 6px solid #f59e0b;
        padding: 12px 14px;
        border-radius: 10px;
        color: #92400e;
        margin-bottom: 16px;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        font-size: 14px;
        flex-wrap: wrap;
    }
    .alert-warning a {
        color: #92400e;
        font-weight: 600;
        text-decoration: underline;
        min-height: 44px;
        display: inline-flex;
        align-items: center;
    }

    /* ---------- Header ---------- */
    .header-grass {
        background: linear-gradient(135deg,#22C55E,#15803D);
        border-radius: 12px;
        padding: 14px 16px;
        box-shadow: 0 2px 10px rgba(34,197,94,0.25);
        margin-bottom: 14px;
        position: relative;
        overflow: hidden;
    }
    .header-grass .blob-1 { position:absolute; top:-40px; right:-40px; width:120px; height:120px; background:rgba(255,255,255,0.05); border-radius:50%; pointer-events:none; }
    .header-grass .blob-2 { position:absolute; bottom:-30px; left:-30px; width:80px; height:80px; background:rgba(255,255,255,0.05); border-radius:50%; pointer-events:none; }
    .header-inner {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        position: relative;
        z-index: 2;
    }
    .header-title-row { display: flex; align-items: center; gap: 10px; min-width: 0; }
    .header-goat { font-size: 32px; display: inline-block; animation: sway 2s ease-in-out infinite; flex-shrink: 0; }
    .header-grass h1 {
        font-size: 20px;
        font-weight: 700;
        color: white;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .header-grass p {
        font-size: 13px;
        color: rgba(255,255,255,0.85);
        margin: 4px 0 0 0;
        overflow-wrap: anywhere;
    }
    .header-role-badge {
        background: rgba(255,255,255,0.2);
        padding: 2px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        color: white;
    }
    .header-date-badge {
        background: rgba(255,255,255,0.15);
        color: white;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 500;
        white-space: nowrap;
    }
    @media (min-width: 641px) {
        .header-grass { padding: 16px 20px; }
        .header-goat { font-size: 38px; }
        .header-grass h1 { font-size: 24px; }
        .header-grass p { font-size: 15px; }
        .header-role-badge { font-size: 14px; padding: 2px 14px; }
        .header-date-badge { font-size: 14px; }
    }
    @media (min-width: 1024px) {
        .header-grass h1 { font-size: 28px; }
        .header-grass p { font-size: 16px; }
    }

    /* ---------- Navigation (horizontal scroll on mobile) ---------- */
    .dash-nav {
        display: flex;
        gap: 6px;
        padding: 8px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        margin-bottom: 14px;
        border: 1px solid #dcfce7;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        scrollbar-color: #bbf7d0 transparent;
    }
    .dash-nav::-webkit-scrollbar { height: 6px; }
    .dash-nav::-webkit-scrollbar-thumb { background: #bbf7d0; border-radius: 999px; }
    .dash-nav-link {
        flex: 0 0 auto;
        min-height: 44px;
        padding: 8px 14px;
        font-size: 14px;
        color: #4a5568;
        border-radius: 8px;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 7px;
        font-weight: 500;
        white-space: nowrap;
    }
    .dash-nav-link i { font-size: 15px; color: #22C55E; }
    .dash-nav-link.active { background: #f0fdf4; color: #22C55E; }
    .dash-nav-link.logout { color: #ef4444; }
    .dash-nav-link.logout i { color: #ef4444; }
    .dash-nav-link.logout { margin-left: auto; }
    @media (min-width: 641px) {
        .dash-nav { overflow-x: visible; padding: 10px 16px; }
        .dash-nav-link { font-size: 15px; padding: 8px 16px; }
        .dash-nav-link i { font-size: 16px; }
    }

    /* ---------- Overview: stat cards + quick actions ---------- */
    .overview-row {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-bottom: 14px;
    }
    .stat-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    .quick-actions-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    @media (min-width: 641px) {
        .overview-row { flex-direction: row; gap: 16px; }
        .stat-grid { flex: 2 1 0; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .quick-actions-grid { flex: 1 1 0; align-self: stretch; }
    }

    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 12px 14px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        border-left: 5px solid #22C55E;
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
    }
    .stat-card i { font-size: 20px; flex-shrink: 0; }
    .stat-card .stat-text { min-width: 0; }
    .stat-card .number {
        font-size: 22px;
        font-weight: 700;
        color: #1a202c;
        line-height: 1.2;
        overflow-wrap: anywhere;
    }
    .stat-card .label {
        font-size: 11.5px;
        color: #718096;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }
    @media (min-width: 641px) {
        .stat-card { padding: 14px 16px; }
        .stat-card i { font-size: 22px; }
        .stat-card .number { font-size: 28px; }
        .stat-card .label { font-size: 13px; }
    }
    @media (min-width: 1024px) {
        .stat-card .number { font-size: 32px; }
    }

    /* ---------- Weather ---------- */
    .weather-scroll {
        display: flex;
        gap: 10px;
        overflow-x: auto;
        padding: 4px 2px 12px 2px;
        margin-bottom: 12px;
        scroll-snap-type: x proximity;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        scrollbar-color: #bbf7d0 transparent;
    }
    .weather-scroll::-webkit-scrollbar { height: 7px; }
    .weather-scroll::-webkit-scrollbar-track { background: transparent; }
    .weather-scroll::-webkit-scrollbar-thumb { background: #bbf7d0; border-radius: 999px; }
    .weather-scroll::-webkit-scrollbar-thumb:hover { background: #86efac; }

    .weather-card {
        flex: 0 0 152px;
        background: white;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        border: 1px solid #dcfce7;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        scroll-snap-align: start;
    }
    .weather-card:hover { transform: translateY(-4px); box-shadow: 0 10px 24px rgba(34,197,94,0.18); }
    .weather-card.today { outline: 2px solid #22C55E; outline-offset: -1px; }
    .weather-card-head {
        background: linear-gradient(135deg,#22C55E,#15803D);
        padding: 8px 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .weather-card-head span:first-child { color: white; font-weight: 700; font-size: 12px; }
    .weather-card-head span:last-child { color: rgba(255,255,255,0.85); font-weight: 600; font-size: 11px; }
    .weather-card-body { padding: 10px 10px 12px 10px; }
    .weather-period-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
    .weather-desc { font-size: 11px; color: #4a5568; font-weight: 600; margin: 2px 0 6px 0; }
    .weather-meta { font-size: 11px; color: #334155; display: flex; align-items: center; justify-content: center; gap: 4px; }
    .weather-rain { font-size: 11px; color: #334155; margin-top: 2px; }
    .weather-divider { height: 1px; background: #e2e8f0; margin: 8px 0; }
    .weather-total { text-align: center; font-size: 11px; color: #334155; }
    @media (min-width: 641px) {
        .weather-card { flex: 0 0 190px; }
        .weather-card-head span:first-child { font-size: 13px; }
        .weather-card-head span:last-child { font-size: 12px; }
        .weather-desc, .weather-meta, .weather-rain, .weather-total { font-size: 12px; }
    }
    @media (min-width: 1024px) {
        .weather-card { flex: 0 0 210px; }
    }

    .tips-box {
        background: linear-gradient(135deg,#f0fdf4,#dcfce7);
        border-radius: 14px;
        padding: 14px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        border: 1px solid #bbf7d0;
    }
    .tips-box h3 {
        font-size: 13.5px;
        font-weight: 700;
        color: #166534;
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0 0 12px 0;
    }
    .tips-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 10px;
    }
    .tip-card {
        animation: fadeInUp 0.45s ease both;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        display: flex;
        align-items: center;
        gap: 10px;
        background: white;
        border-radius: 10px;
        padding: 10px 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        border-left: 4px solid #22C55E;
    }
    .tip-card:hover { transform: translateY(-2px); box-shadow: 0 6px 14px rgba(0,0,0,0.08); }
    .tip-card span { font-size: 13px; color: #334155; line-height: 1.4; }
    @media (min-width: 641px) {
        .tips-box { padding: 16px; }
        .tips-box h3 { font-size: 14px; }
        .tips-grid { grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
    }

    /* ---------- Recent goats ---------- */
    .recent-goats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 12px;
    }
    .goat-card {
        background: white;
        border-radius: 12px;
        padding: 12px 14px;
        border: 1px solid #dcfce7;
        box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        transition: 0.2s;
        min-width: 0;
    }
    .goat-card-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 8px;
        flex-wrap: wrap;
    }
    .goat-card-id {
        font-size: 15px;
        font-weight: 700;
        color: #16a34a;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 6px;
        min-height: 44px;
    }
    .goat-card-name { font-size: 17px; font-weight: 600; color: #1e293b; margin-top: 2px; overflow-wrap: anywhere; }
    .goat-card-date { font-size: 12px; color: #94a3b8; white-space: nowrap; }
    .goat-card-badges { display: flex; gap: 8px; margin-top: 8px; flex-wrap: wrap; }
    .goat-badge {
        font-size: 11px;
        font-weight: 600;
        padding: 3px 12px;
        border-radius: 999px;
        display: inline-block;
    }

    /* ---------- Animations ---------- */
    @keyframes sway { 0%,100% { transform: rotate(-2deg); } 50% { transform: rotate(2deg); } }
    @keyframes spinSlow { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    @keyframes floatCloud { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-2px); } }
    @keyframes dropFall {
        0%   { transform: translateY(0); opacity: 0; }
        20%  { opacity: 1; }
        90%  { opacity: 1; }
        100% { transform: translateY(6px); opacity: 0; }
    }
    @keyframes boltFlash { 0%,45%,55%,100% { opacity: 1; } 50% { opacity: 0.35; } }
    @keyframes fogDrift { 0%,100% { transform: translateX(0); opacity: 0.9; } 50% { transform: translateX(3px); opacity: 0.5; } }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

    .weather-icon-anim { display: inline-flex; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.08)); }
    .weather-icon-anim .wi-sun-rays { transform-origin: 32px 32px; animation: spinSlow 12s linear infinite; }
    .weather-icon-anim .wi-cloud { animation: floatCloud 3.5s ease-in-out infinite; }
    .weather-icon-anim .wi-drop { animation: dropFall 1s ease-in infinite; }
    .weather-icon-anim .wi-drop-2 { animation-delay: 0.25s; }
    .weather-icon-anim .wi-drop-3 { animation-delay: 0.5s; }
    .weather-icon-anim .wi-bolt { animation: boltFlash 2s ease-in-out infinite; transform-origin: center; }
    .weather-icon-anim .wi-fog line { animation: fogDrift 3s ease-in-out infinite; }
    .weather-icon-anim .wi-fog-2 { animation-delay: 0.4s; }
    .weather-icon-anim .wi-fog-3 { animation-delay: 0.8s; }
    .wi-color-sunny { color: #F59E0B; }
    .wi-color-rain  { color: #3B82F6; }
    .wi-color-storm { color: #F59E0B; }
    .wi-color-fog   { color: #94A3B8; }
    .wi-color-cloud { color: #94A3B8; }
</style>

<!-- ============================================================
     MAIN DASHBOARD
============================================================ -->
<div class="dashboard-wrap">

    <!-- ===== HEADER ===== -->
    <div class="header-grass">
        <div class="blob-1"></div>
        <div class="blob-2"></div>
        <div class="header-inner">
            <div class="header-title-row">
                <span class="header-goat">🐐</span>
                <div style="min-width:0;">
                    <h1>
                        Farmer Dashboard
                        <span class="header-role-badge">User</span>
                    </h1>
                    <p>
                        <i class="fas fa-leaf" style="font-size:12px;"></i> Welcome back, <?php echo htmlspecialchars(substr($_SESSION['email'], 0, 20)); ?>
                    </p>
                </div>
            </div>
            <div>
                <span class="header-date-badge">
                    <i class="fas fa-calendar-alt"></i> <?php echo date('M d, Y'); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- ===== NAVIGATION ===== -->
    <?php
    // Determine if we are on the dashboard page itself
    $isDashboard = (basename($_SERVER['PHP_SELF']) == 'dashboard.php');
    ?>
    <nav class="dash-nav">
        <!-- Dashboard link: if already on dashboard, make it a non-clickable span, otherwise link to same file -->
        <?php if ($isDashboard): ?>
            <span class="dash-nav-link active">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </span>
        <?php else: ?>
            <a href="dashboard.php" class="dash-nav-link active">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        <?php endif; ?>

        <a href="<?php echo BASE_URL; ?>/app/goats/dashboard.php" class="dash-nav-link">
            <i class="fas fa-goat"></i> Inventory
        </a>
        <a href="<?php echo BASE_URL; ?>/app/goats/goat-create.php" class="dash-nav-link">
            <i class="fas fa-plus-circle"></i> Add Goat
        </a>
        <a href="<?php echo BASE_URL; ?>/app/auth/signout.php" class="dash-nav-link logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>

    <!-- ===== ALERT: Sick Goats ===== -->
    <?php if ($goatSummary['sick'] > 0): ?>
    <div class="alert-warning">
        <i class="fas fa-exclamation-triangle" style="font-size:18px;color:#d97706;flex-shrink:0;"></i>
        <div>
            <strong>⚠️ Attention:</strong> You have <strong><?php echo $goatSummary['sick']; ?></strong> sick goat(s). Please check their health status and provide care.
            <a href="<?php echo BASE_URL; ?>/app/goats/dashboard.php?filter=sick">View sick goats</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== FARM OVERVIEW – KEY STATS & QUICK ACTIONS ===== -->
    <div class="overview-row">
        <!-- Stat Cards -->
        <div class="stat-grid">
            <div class="stat-card" style="border-left-color:#22C55E;">
                <i class="fas fa-check-circle" style="color:#22C55E;"></i>
                <div class="stat-text">
                    <div class="number"><?php echo $goatSummary['active']; ?></div>
                    <div class="label">Active Goats</div>
                </div>
            </div>
            <div class="stat-card" style="border-left-color:#4ADE80;">
                <i class="fas fa-heart" style="color:#4ADE80;"></i>
                <div class="stat-text">
                    <div class="number"><?php echo $goatSummary['healthy']; ?></div>
                    <div class="label">Healthy</div>
                </div>
            </div>
            <div class="stat-card" style="border-left-color:#ef4444;">
                <i class="fas fa-medkit" style="color:#ef4444;"></i>
                <div class="stat-text">
                    <div class="number"><?php echo $goatSummary['sick']; ?></div>
                    <div class="label">Sick</div>
                </div>
            </div>
            <div class="stat-card" style="border-left-color:#60A5FA;">
                <i class="fas fa-venus-mars" style="color:#60A5FA;"></i>
                <div class="stat-text">
                    <div class="number"><?php echo $goatSummary['male']; ?>/<?php echo $goatSummary['female']; ?></div>
                    <div class="label">Male/ Female</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions-grid">
            <a href="<?php echo BASE_URL; ?>/app/goats/goat-create.php" class="quick-action">
                <i class="fas fa-plus-circle" style="color:#22C55E;"></i>
                <span>Add Goat</span>
            </a>
            <a href="<?php echo BASE_URL; ?>/app/goats/dashboard.php" class="quick-action">
                <i class="fas fa-list" style="color:#15803D;"></i>
                <span>View All</span>
            </a>
        </div>
    </div>

    <!-- ===== WEATHER & FARMING TIPS ===== -->
    <div style="margin-bottom:16px;">
        <div class="card-title">
            <i class="fas fa-cloud-sun" style="color:#22C55E;font-size:18px;"></i> Today's Weather &amp; Tips
            <span class="badge-sm" style="background:#dcfce7;color:#166534;">
                <i class="fas fa-map-marker-alt" style="font-size:10px;"></i> <?php echo htmlspecialchars($weatherForecast['location'] ?? 'Manolo Fortich, Bukidnon'); ?>
            </span>
        </div>

        <?php if ($weatherForecast && !empty($weatherForecast['days'])): ?>
        <div class="weather-scroll">
            <?php foreach ($weatherForecast['days'] as $di => $day):
                $dateTs   = strtotime($day['date']);
                $dayLabel = $di === 0 ? 'Today' : date('l', $dateTs);
                $dateLabel = date('m/d', $dateTs);
                $dayInfo   = $day['day'];
                $nightInfo = $day['night'];
                $dayVisual   = $dayInfo['available']   ? getPeriodWeatherDisplay($dayInfo['weathercode'], $dayInfo['pop'], false) : null;
                $nightVisual = $nightInfo['available'] ? getPeriodWeatherDisplay($nightInfo['weathercode'], $nightInfo['pop'], true) : null;
                $dayText   = $dayInfo['available']   ? weatherDetailedLabel($dayInfo['weathercode'], $dayInfo['pop'], false) : null;
                $nightText = $nightInfo['available'] ? weatherDetailedLabel($nightInfo['weathercode'], $nightInfo['pop'], true) : null;
                $totalMm = $day['total_mm'] ?? 0;
            ?>
            <div class="weather-card<?php echo $di === 0 ? ' today' : ''; ?>">
                <div class="weather-card-head">
                    <span><?php echo htmlspecialchars($dayLabel); ?></span>
                    <span><?php echo $dateLabel; ?></span>
                </div>
                <div class="weather-card-body">
                    <div style="text-align:center;">
                        <div class="weather-period-label" style="color:#16A34A;">Adlaw</div>
                        <?php if ($dayInfo['available']): ?>
                            <div style="display:flex;justify-content:center;"><?php echo renderWeatherIcon($dayVisual['type'], 40); ?></div>
                            <div class="weather-desc"><?php echo htmlspecialchars($dayText); ?></div>
                            <div class="weather-meta">
                                <i class="fas fa-tint" style="color:#3b82f6;font-size:9px;"></i> <strong><?php echo $dayInfo['pop']; ?>%</strong>&nbsp;chance
                            </div>
                            <div class="weather-rain">
                                Rainfall: <?php if ($dayInfo['rain_mm'] < 1): ?><span style="color:#94a3b8;">&lt; 1 mm</span><?php else: ?><strong style="color:#2563eb;"><?php echo number_format($dayInfo['rain_mm'], 2); ?> mm</strong><?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div style="padding:14px 0;color:#a0aec0;font-size:12px;font-style:italic;">No data</div>
                        <?php endif; ?>
                    </div>
                    <div class="weather-divider"></div>
                    <div style="text-align:center;">
                        <div class="weather-period-label" style="color:#2563eb;">Gabii</div>
                        <?php if ($nightInfo['available']): ?>
                            <div style="display:flex;justify-content:center;"><?php echo renderWeatherIcon($nightVisual['type'], 40); ?></div>
                            <div class="weather-desc"><?php echo htmlspecialchars($nightText); ?></div>
                            <div class="weather-meta">
                                <i class="fas fa-tint" style="color:#3b82f6;font-size:9px;"></i> <strong><?php echo $nightInfo['pop']; ?>%</strong>&nbsp;chance
                            </div>
                            <div class="weather-rain">
                                Rainfall: <?php if ($nightInfo['rain_mm'] < 1): ?><span style="color:#94a3b8;">&lt; 1 mm</span><?php else: ?><strong style="color:#2563eb;"><?php echo number_format($nightInfo['rain_mm'], 2); ?> mm</strong><?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div style="padding:14px 0;color:#a0aec0;font-size:12px;font-style:italic;">No data</div>
                        <?php endif; ?>
                    </div>
                    <div class="weather-divider"></div>
                    <div class="weather-total">
                        <i class="fas fa-umbrella" style="color:<?php echo $totalMm >= 1 ? '#DC2626' : '#94a3b8'; ?>;font-size:10px;"></i>
                        Total: <strong style="color:<?php echo $totalMm >= 1 ? '#DC2626' : '#94a3b8'; ?>;"><?php echo $totalMm < 1 ? '< 1 mm' : number_format($totalMm, 2) . ' mm'; ?></strong>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ===== FARMING TIPS ===== -->
        <div class="tips-box">
            <h3><i class="fas fa-lightbulb" style="color:#f59e0b;"></i> Farming Tips for Today</h3>
            <div class="tips-grid">
                <?php foreach ($farmingTips as $ti => $tip):
                    $tipColor = $tip['color'] ?? '#22C55E';
                ?>
                    <div class="tip-card" style="animation-delay:<?php echo $ti * 0.08; ?>s;border-left-color:<?php echo $tipColor; ?>;">
                        <span><?php echo htmlspecialchars($tip['text'] ?? ''); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
            <div class="card" style="text-align:center;color:#a0aec0;padding:24px;">
                <i class="fas fa-cloud-slash" style="font-size:32px;color:#bbf7d0;display:block;margin-bottom:8px;"></i>
                <p style="font-size:13px;margin:0;">Weather forecast temporarily unavailable.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- ===== RECENT GOATS (CARDS) ===== -->
    <div style="margin-bottom:16px;">
        <div class="card">
            <div class="card-title" style="justify-content:space-between;">
                <span><i class="fas fa-clock" style="color:#22C55E;"></i> Recently Added / Updated Goats</span>
                <a href="<?php echo BASE_URL; ?>/app/goats/dashboard.php" class="btn btn-primary" style="font-size:13px;padding:8px 16px;">
                    <i class="fas fa-arrow-right"></i> View All
                </a>
            </div>

            <?php if (empty($recentGoats)): ?>
                <div style="text-align:center;padding:30px 16px;color:#a0aec0;">
                    <i class="fas fa-goat" style="font-size:40px;color:#bbf7d0;display:block;margin-bottom:10px;"></i>
                    <p style="font-size:14px;margin:0;color:#4a5568;">No goat records yet.</p>
                    <a href="<?php echo BASE_URL; ?>/app/goats/goat-create.php" class="btn btn-primary" style="margin-top:10px;">
                        <i class="fas fa-plus-circle"></i> Add your first goat
                    </a>
                </div>
            <?php else: ?>
                <div class="recent-goats-grid">
                    <?php foreach ($recentGoats as $g):
                        $healthColor = $g['health_status'] === 'healthy' ? '#22C55E' : ($g['health_status'] === 'sick' ? '#ef4444' : '#94a3b8');
                        $healthBg = $g['health_status'] === 'healthy' ? '#dcfce7' : ($g['health_status'] === 'sick' ? '#fef3c7' : '#f3f4f6');
                        $healthText = $g['health_status'] === 'healthy' ? '#166534' : ($g['health_status'] === 'sick' ? '#d97706' : '#6b7280');
                    ?>
                        <div class="goat-card" style="border-left:5px solid <?php echo $healthColor; ?>;">
                            <div class="goat-card-top">
                                <div style="min-width:0;">
                                    <a href="<?php echo BASE_URL; ?>/app/goats/goat-view.php?goat_id=<?php echo urlencode($g['goat_id']); ?>" class="goat-card-id">
                                        <i class="fas fa-tag" style="color:#86efac;font-size:12px;"></i> <?php echo htmlspecialchars($g['goat_id']); ?>
                                    </a>
                                    <div class="goat-card-name"><?php echo htmlspecialchars($g['goat_name']); ?></div>
                                </div>
                                <span class="goat-card-date">
                                    <i class="fas fa-calendar-alt"></i> <?php echo date('M d', strtotime($g['updated_at'])); ?>
                                </span>
                            </div>
                            <div class="goat-card-badges">
                                <span class="goat-badge" style="background:#DCFCE7;color:#166534;border:1px solid #BBF7D0;">
                                    <?php echo goatStatusLabel($g['status']); ?>
                                </span>
                                <span class="goat-badge" style="background:<?php echo $healthBg; ?>;color:<?php echo $healthText; ?>;border:1px solid <?php echo $healthColor; ?>40;">
                                    <?php echo healthStatusLabel($g['health_status']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php renderFooter(); ?>