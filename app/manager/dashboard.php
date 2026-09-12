<?php
require_once '../../config/config.php';
require_once '../../config/functions.php';
require_once '../../includes/activity-logger.php';
require_once '../../includes/goat-functions.php';
requireRole('manager');

// ---- Goat Inventory summary ----
$goatsTableExists = $pdo->query("SHOW TABLES LIKE 'goats'")->fetch();
$totalActiveGoats = 0;
$goatSexCounts = ['male' => 0, 'female' => 0];
$goatHealthCounts = ['healthy' => 0, 'sick' => 0, 'unknown' => 0];
$soldGoatsCount = 0;
$deceasedGoatsCount = 0;
$recentGoats = [];
if ($goatsTableExists) {
    $totalActiveGoats = (int)$pdo->query("SELECT COUNT(*) FROM goats WHERE status = 'active'")->fetchColumn();

    $stmt = $pdo->query("SELECT sex, COUNT(*) c FROM goats WHERE status = 'active' GROUP BY sex");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) { $goatSexCounts[$row['sex']] = (int)$row['c']; }

    $stmt = $pdo->query("SELECT health_status, COUNT(*) c FROM goats WHERE status = 'active' GROUP BY health_status");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) { $goatHealthCounts[$row['health_status']] = (int)$row['c']; }

    $soldGoatsCount = (int)$pdo->query("SELECT COUNT(*) FROM goats WHERE status = 'sold'")->fetchColumn();
    $deceasedGoatsCount = (int)$pdo->query("SELECT COUNT(*) FROM goats WHERE status = 'deceased'")->fetchColumn();

    $stmt = $pdo->query("
        SELECT g.goat_id, g.goat_name, g.status, g.health_status, g.updated_at, u.full_name AS owner_name
        FROM goats g JOIN users u ON u.id = g.user_id
        ORDER BY g.updated_at DESC LIMIT 5
    ");
    $recentGoats = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get statistics for manager
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'user' AND is_verified = 0");
$unverifiedUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get activity statistics for manager and users only
$stmt = $pdo->query("
    SELECT DATE(al.created_at) as date, COUNT(*) as count 
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    WHERE al.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    AND (u.role IN ('manager', 'user') OR u.role IS NULL)
    GROUP BY DATE(al.created_at)
    ORDER BY date ASC
");
$dailyActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get action distribution for manager and users
$stmt = $pdo->query("
    SELECT al.action, COUNT(*) as count 
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    WHERE al.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    AND (u.role IN ('manager', 'user') OR u.role IS NULL)
    GROUP BY al.action 
    ORDER BY count DESC 
    LIMIT 10
");
$actionStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$title = 'Manager Dashboard';
renderHeader($title);

// Determine if we are on the dashboard page itself
$isDashboard = (basename($_SERVER['PHP_SELF']) == 'dashboard.php');
?>

<!-- ============================================================
     STYLES – EXACTLY MATCHING GOATS/DASHBOARD.PHP
============================================================ -->
<style>
    /* ---------- Reset / safety net ---------- */
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
    .btn-secondary {
        background: #e2e8f0;
        color: #1f2937;
    }
    .btn-secondary:hover { background: #cbd5e1; }

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

    /* ---------- STAT GRID – EXACTLY LIKE GOAT INVENTORY ---------- */
    .stat-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        margin-bottom: 16px;
    }
    @media (min-width: 641px) {
        .stat-grid { grid-template-columns: repeat(4, 1fr); gap: 12px; }
    }
    @media (min-width: 1024px) {
        .stat-grid { grid-template-columns: repeat(7, 1fr); }
    }

    /* ---------- Stat cards (matching goats dashboard) ---------- */
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

    /* ---------- Chart containers ---------- */
    .chart-container {
        background: white;
        border-radius: 12px;
        padding: 14px;
        border: 1px solid #dcfce7;
        box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        margin-bottom: 16px;
    }
    .chart-container h3 {
        font-size: 15px;
        font-weight: 600;
        color: #1f2937;
        margin: 0 0 12px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    @media (min-width: 641px) {
        .chart-container { padding: 16px 18px; }
        .chart-container h3 { font-size: 16px; }
    }

    /* ---------- Table styling ---------- */
    .table-wrapper {
        overflow-x: auto;
        margin-top: 8px;
    }
    .table-wrapper table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }
    .table-wrapper th {
        text-align: left;
        padding: 10px 12px;
        background: #f0fdf4;
        color: #1f2937;
        font-weight: 600;
        border-bottom: 2px solid #dcfce7;
        white-space: nowrap;
    }
    .table-wrapper td {
        padding: 10px 12px;
        border-bottom: 1px solid #e5e7eb;
        vertical-align: middle;
    }
    .table-wrapper tr:last-child td { border-bottom: none; }
    .table-wrapper tr:hover td { background: #fafff7; }

    /* ---------- Badges ---------- */
    .badge {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 999px;
        font-weight: 600;
        font-size: 12px;
    }
    .badge-success { background: #dcfce7; color: #166534; }
    .badge-danger  { background: #fef3c7; color: #92400e; }
    .badge-warning { background: #fef9c3; color: #854d0e; }
    .badge-secondary { background: #f3f4f6; color: #6b7280; }
    .badge-manager { background: #dbeafe; color: #1e40af; }
    .badge-admin { background: #fce7f3; color: #9d174d; }
    .badge-user { background: #e0e7ff; color: #3730a3; }

    /* ---------- Animation ---------- */
    @keyframes sway { 0%,100% { transform: rotate(-2deg); } 50% { transform: rotate(2deg); } }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    .stat-card, .quick-action, .card, .chart-container {
        animation: fadeInUp 0.4s ease both;
    }
    .stat-card:nth-child(2) { animation-delay: 0.05s; }
    .stat-card:nth-child(3) { animation-delay: 0.10s; }
    .stat-card:nth-child(4) { animation-delay: 0.15s; }
    .stat-card:nth-child(5) { animation-delay: 0.20s; }
    .stat-card:nth-child(6) { animation-delay: 0.25s; }
    .stat-card:nth-child(7) { animation-delay: 0.30s; }
</style>

<!-- ============================================================
     MAIN CONTENT
============================================================ -->
<div style="width:100%;display:flex;flex-direction:column;background:#f0fdf4;">

    <!-- ===== HEADER ===== -->
    <div class="header-grass">
        <div class="blob-1"></div>
        <div class="blob-2"></div>
        <div class="header-inner">
            <div class="header-title-row">
                <span class="header-goat">🐐</span>
                <div style="min-width:0;">
                    <h1>
                        Manager Dashboard
                        <span class="header-role-badge">Manager</span>
                    </h1>
                    <p>
                        <i class="fas fa-leaf" style="font-size:12px;"></i> Welcome, <?php echo htmlspecialchars(substr($_SESSION['email'], 0, 30)); ?>
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
    <nav class="dash-nav">
        <?php if ($isDashboard): ?>
            <span class="dash-nav-link active">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </span>
        <?php else: ?>
            <a href="dashboard.php" class="dash-nav-link active">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        <?php endif; ?>

        <a href="<?php echo BASE_URL; ?>/app/users/user-create.php" class="dash-nav-link">
            <i class="fas fa-user-plus"></i> Create User
        </a>

        <a href="<?php echo BASE_URL; ?>/app/goats/dashboard.php" class="dash-nav-link">
            <i class="fas fa-goat"></i> Inventory
        </a>

        <a href="<?php echo BASE_URL; ?>/app/goats/farmer-overview.php" class="dash-nav-link">
            <i class="fas fa-users"></i> Farmer Overview
        </a>

        <a href="<?php echo BASE_URL; ?>/app/auth/signout.php" class="dash-nav-link logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>

    <!-- ===== USER STATS + QUICK ACTIONS ===== -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
        <!-- User Stats -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <div class="stat-card" style="border-left-color:#3b82f6;">
                <i class="fas fa-users" style="color:#3b82f6;"></i>
                <div class="stat-text">
                    <div class="number"><?php echo $totalUsers; ?></div>
                    <div class="label">Total Users</div>
                </div>
            </div>
            <div class="stat-card" style="border-left-color:#f59e0b;">
                <i class="fas fa-user-clock" style="color:#f59e0b;"></i>
                <div class="stat-text">
                    <div class="number"><?php echo $unverifiedUsers; ?></div>
                    <div class="label">Unverified</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <a href="<?php echo BASE_URL; ?>/app/users/user-create.php" class="quick-action">
                <i class="fas fa-user-plus" style="color:#22C55E;"></i>
                <span>Add User</span>
            </a>
            <a href="<?php echo BASE_URL; ?>/app/goats/dashboard.php" class="quick-action">
                <i class="fas fa-goat" style="color:#15803D;"></i>
                <span>Inventory</span>
            </a>
        </div>
    </div>

    <!-- ============================================================
         GOAT INVENTORY STATS – USING SAME GRID AS GOATS/DASHBOARD
    ============================================================ -->
    <h2 style="font-size:18px;font-weight:700;color:#1f2937;margin:0 0 12px 0;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-goat" style="color:#22C55E;"></i> Goat Inventory (All Farmers)
    </h2>

    <div class="stat-grid">
        <div class="stat-card" style="border-left-color:#22C55E;">
            <i class="fas fa-check-circle" style="color:#22C55E;"></i>
            <div class="stat-text">
                <div class="number"><?php echo $totalActiveGoats; ?></div>
                <div class="label">Active</div>
            </div>
        </div>
        <div class="stat-card" style="border-left-color:#3b82f6;">
            <i class="fas fa-venus-mars" style="color:#3b82f6;"></i>
            <div class="stat-text">
                <div class="number"><?php echo $goatSexCounts['male']; ?>/<?php echo $goatSexCounts['female']; ?></div>
                <div class="label">Male/Female</div>
            </div>
        </div>
        <div class="stat-card" style="border-left-color:#4ADE80;">
            <i class="fas fa-heart" style="color:#4ADE80;"></i>
            <div class="stat-text">
                <div class="number"><?php echo $goatHealthCounts['healthy']; ?></div>
                <div class="label">Healthy</div>
            </div>
        </div>
        <div class="stat-card" style="border-left-color:#ef4444;">
            <i class="fas fa-medkit" style="color:#ef4444;"></i>
            <div class="stat-text">
                <div class="number"><?php echo $goatHealthCounts['sick']; ?></div>
                <div class="label">Sick</div>
            </div>
        </div>
        <div class="stat-card" style="border-left-color:#94a3b8;">
            <i class="fas fa-question-circle" style="color:#94a3b8;"></i>
            <div class="stat-text">
                <div class="number"><?php echo $goatHealthCounts['unknown']; ?></div>
                <div class="label">Unknown</div>
            </div>
        </div>
        <div class="stat-card" style="border-left-color:#f59e0b;">
            <i class="fas fa-hand-holding-usd" style="color:#f59e0b;"></i>
            <div class="stat-text">
                <div class="number"><?php echo $soldGoatsCount; ?></div>
                <div class="label">Sold</div>
            </div>
        </div>
        <div class="stat-card" style="border-left-color:#9ca3af;">
            <i class="fas fa-skull" style="color:#9ca3af;"></i>
            <div class="stat-text">
                <div class="number"><?php echo $deceasedGoatsCount; ?></div>
                <div class="label">Deceased</div>
            </div>
        </div>
    </div>

    <!-- ===== CHARTS ===== -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
        <div class="chart-container">
            <h3><i class="fas fa-chart-line" style="color:#22C55E;"></i> Daily Activity Trend (Last 7 Days)</h3>
            <canvas id="dailyActivityChart" style="width:100%;max-height:220px;"></canvas>
        </div>
        <div class="chart-container">
            <h3><i class="fas fa-chart-bar" style="color:#22C55E;"></i> Top Actions (Last 30 Days)</h3>
            <canvas id="actionChart" style="width:100%;max-height:220px;"></canvas>
        </div>
    </div>

    <!-- ===== RECENT GOATS ===== -->
    <div class="card" style="margin-bottom:16px;">
        <div class="card-title" style="justify-content:space-between;">
            <span><i class="fas fa-clock" style="color:#22C55E;"></i> Recently Updated Goat Records</span>
            <a href="<?php echo BASE_URL; ?>/app/goats/dashboard.php" class="btn btn-primary" style="font-size:13px;padding:6px 16px;min-height:38px;">
                <i class="fas fa-arrow-right"></i> View Full Inventory
            </a>
        </div>
        <?php if (empty($recentGoats)): ?>
            <div style="text-align:center;padding:30px 20px;color:#a0aec0;">
                <i class="fas fa-goat" style="font-size:40px;color:#bbf7d0;display:block;margin-bottom:10px;"></i>
                <p style="font-size:14px;margin:0;color:#4a5568;">No goat records yet.</p>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr><th>Goat ID</th><th>Name</th><th>Farmer</th><th>Status</th><th>Health</th><th>Updated</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentGoats as $g): ?>
                        <tr>
                            <td><a href="<?php echo BASE_URL; ?>/app/goats/goat-view.php?goat_id=<?php echo urlencode($g['goat_id']); ?>" style="color:#16a34a;font-weight:600;text-decoration:none;"><?php echo htmlspecialchars($g['goat_id']); ?></a></td>
                            <td><?php echo htmlspecialchars($g['goat_name']); ?></td>
                            <td><?php echo htmlspecialchars($g['owner_name']); ?></td>
                            <td><span class="badge <?php echo goatStatusBadgeClass($g['status']); ?>"><?php echo goatStatusLabel($g['status']); ?></span></td>
                            <td><span class="badge <?php echo healthStatusBadgeClass($g['health_status']); ?>"><?php echo healthStatusLabel($g['health_status']); ?></span></td>
                            <td><?php echo date('Y-m-d', strtotime($g['updated_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top:12px;display:flex;gap:10px;">
                <a href="<?php echo BASE_URL; ?>/app/goats/dashboard.php" class="btn btn-primary">View Full Inventory</a>
                <a href="<?php echo BASE_URL; ?>/app/goats/farmer-overview.php" class="btn btn-secondary">Farmer Overview</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- ===== MANAGER CAPABILITIES ===== -->
    <div class="card">
        <div class="card-title"><i class="fas fa-shield-alt" style="color:#22C55E;"></i> Manager Capabilities</div>
        <ul style="margin: 0 0 0 20px; line-height: 1.8; color: #1f2937;">
            <li><strong>Manage Users:</strong> Create, edit, and delete regular users only</li>
            <li><strong>View Reports:</strong> Access user data and analytics</li>
            <li><strong>Team Management:</strong> Oversee user activities</li>
            <li><strong>Restrictions:</strong>
                <ul style="margin-top: 5px;">
                    <li>Cannot view Admin activities</li>
                    <li>Cannot manage Admins or other Managers</li>
                    <li>Cannot delete own account</li>
                </ul>
            </li>
        </ul>
    </div>

</div>

<!-- ===== SCRIPTS ===== -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Daily Activity Chart
const dailyCtx = document.getElementById('dailyActivityChart').getContext('2d');
const dailyData = <?php echo json_encode($dailyActivity); ?>;
new Chart(dailyCtx, {
    type: 'line',
    data: {
        labels: dailyData.map(d => d.date),
        datasets: [{
            label: 'Activities',
            data: dailyData.map(d => d.count),
            borderColor: '#22C55E',
            backgroundColor: 'rgba(34,197,94,0.1)',
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#15803D'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
            x: { grid: { display: false } }
        }
    }
});

// Action Distribution Chart
const actionCtx = document.getElementById('actionChart').getContext('2d');
const actionData = <?php echo json_encode($actionStats); ?>;
new Chart(actionCtx, {
    type: 'bar',
    data: {
        labels: actionData.map(d => d.action),
        datasets: [{
            label: 'Count',
            data: actionData.map(d => d.count),
            backgroundColor: [
                'rgba(34,197,94,0.7)',
                'rgba(21,128,61,0.7)',
                'rgba(251,191,36,0.7)',
                'rgba(59,130,246,0.7)',
                'rgba(239,68,68,0.7)',
                'rgba(168,85,247,0.7)',
                'rgba(236,72,153,0.7)',
                'rgba(14,165,233,0.7)',
                'rgba(245,158,11,0.7)',
                'rgba(107,114,128,0.7)'
            ],
            borderColor: 'rgba(0,0,0,0)'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
            x: { grid: { display: false } }
        }
    }
});
</script>

<?php renderFooter(); ?>