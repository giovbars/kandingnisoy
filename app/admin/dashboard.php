<?php
require_once '../../config/config.php';
require_once '../../config/functions.php';
require_once '../../includes/activity-logger.php';
require_once '../../includes/goat-functions.php';
requireRole('admin');

// ---- Goat Inventory summary ----
$goatsTableExists = $pdo->query("SHOW TABLES LIKE 'goats'")->fetch();
$totalActiveGoats = 0;
$totalSoldGoats = 0;
$totalDeceasedGoats = 0;
$totalFarmersWithGoats = 0;
$recentOwnershipCorrections = [];
if ($goatsTableExists) {
    $totalActiveGoats = (int)$pdo->query("SELECT COUNT(*) FROM goats WHERE status = 'active'")->fetchColumn();
    $totalSoldGoats = (int)$pdo->query("SELECT COUNT(*) FROM goats WHERE status = 'sold'")->fetchColumn();
    $totalDeceasedGoats = (int)$pdo->query("SELECT COUNT(*) FROM goats WHERE status = 'deceased'")->fetchColumn();
    $totalFarmersWithGoats = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM goats")->fetchColumn();

    $stmt = $pdo->query("
        SELECT al.*, u.full_name AS actor_name
        FROM activity_logs al
        LEFT JOIN users u ON u.id = al.user_id
        WHERE al.action = 'goat_ownership_corrected'
        ORDER BY al.created_at DESC LIMIT 5
    ");
    $recentOwnershipCorrections = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'admin'");
$totalAdmins = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'manager'");
$totalManagers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$totalRegularUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE is_verified = 0");
$unverifiedUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM activity_logs 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$dailyActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT action, COUNT(*) as count 
    FROM activity_logs 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY action 
    ORDER BY count DESC 
    LIMIT 10
");
$actionStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT 
        COALESCE(u.role, 'unknown') as role, 
        COUNT(*) as count 
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    WHERE al.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY role
");
$roleStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sessionEmail = $_SESSION['email'] ?? '';
$sessionEmailShort = htmlspecialchars(substr($sessionEmail, 0, 15)) . (strlen($sessionEmail) > 15 ? '...' : '');

$title = 'Admin Dashboard';
renderHeader($title);

// Determine if we are on the dashboard page itself
$isDashboard = (basename($_SERVER['PHP_SELF']) == 'dashboard.php');
?>

<!-- ============================================================
     STYLES – fully aligned with user dashboard
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
    .btn-outline {
        background: transparent;
        color: #15803D;
        border: 1.5px solid #22C55E;
    }
    .btn-outline:hover { background: #f0fdf4; }

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

    /* ---------- Stat cards (matching dashboard) ---------- */
    .stat-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        margin-bottom: 16px;
    }
    @media (min-width: 641px) {
        .stat-grid { grid-template-columns: repeat(5, 1fr); gap: 12px; }
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
    .stat-card, .card, .chart-container {
        animation: fadeInUp 0.4s ease both;
    }
    .stat-card:nth-child(2) { animation-delay: 0.05s; }
    .stat-card:nth-child(3) { animation-delay: 0.10s; }
    .stat-card:nth-child(4) { animation-delay: 0.15s; }
    .stat-card:nth-child(5) { animation-delay: 0.20s; }
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
                        Admin Dashboard
                        <span class="header-role-badge">Admin</span>
                    </h1>
                    <p>
                        <i class="fas fa-leaf" style="font-size:12px;"></i> Welcome, <?php echo $sessionEmailShort; ?>
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

    <!-- ===== NAVIGATION (ALL LABELS VISIBLE ON MOBILE) ===== -->
    <nav class="dash-nav">
        <!-- Dashboard link: if already on dashboard, make it a non-clickable span -->
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
            <i class="fas fa-goat"></i> Goat Inventory
        </a>
        <a href="<?php echo BASE_URL; ?>/app/goats/farmer-overview.php" class="dash-nav-link">
            <i class="fas fa-users"></i> Farmer Overview
        </a>
        <a href="<?php echo BASE_URL; ?>/app/auth/signout.php" class="dash-nav-link logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>

    <!-- ===== USER STATISTICS ===== -->
    <h2 style="font-size:18px;font-weight:700;color:#1f2937;margin:0 0 12px 0;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-users" style="color:#22C55E;"></i> User Statistics
    </h2>
    <div class="stat-grid">
        <div class="stat-card" style="border-left-color:#3b82f6;">
            <i class="fas fa-users" style="color:#3b82f6;"></i>
            <div class="stat-text">
                <div class="number"><?php echo $totalUsers; ?></div>
                <div class="label">Total</div>
            </div>
        </div>
        <div class="stat-card" style="border-left-color:#f59e0b;">
            <i class="fas fa-user-shield" style="color:#f59e0b;"></i>
            <div class="stat-text">
                <div class="number"><?php echo $totalAdmins; ?></div>
                <div class="label">Admins</div>
            </div>
        </div>
        <div class="stat-card" style="border-left-color:#8b5cf6;">
            <i class="fas fa-user-cog" style="color:#8b5cf6;"></i>
            <div class="stat-text">
                <div class="number"><?php echo $totalManagers; ?></div>
                <div class="label">Managers</div>
            </div>
        </div>
        <div class="stat-card" style="border-left-color:#4ADE80;">
            <i class="fas fa-user" style="color:#4ADE80;"></i>
            <div class="stat-text">
                <div class="number"><?php echo $totalRegularUsers; ?></div>
                <div class="label">Regular</div>
            </div>
        </div>
        <div class="stat-card" style="border-left-color:#ef4444;">
            <i class="fas fa-user-clock" style="color:#ef4444;"></i>
            <div class="stat-text">
                <div class="number"><?php echo $unverifiedUsers; ?></div>
                <div class="label">Unverified</div>
            </div>
        </div>
    </div>

    <!-- ===== ACTIVITY ANALYTICS ===== -->
    <h2 style="font-size:18px;font-weight:700;color:#1f2937;margin:0 0 12px 0;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-chart-line" style="color:#22C55E;"></i> Activity Analytics
    </h2>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
        <div class="chart-container">
            <h3><i class="fas fa-calendar-day" style="color:#22C55E;"></i> Daily Trend <span style="font-size:12px;color:#94a3b8;font-weight:400;">(7 days)</span></h3>
            <canvas id="dailyChart" style="width:100%;max-height:220px;"></canvas>
        </div>
        <div class="chart-container">
            <h3><i class="fas fa-tasks" style="color:#22C55E;"></i> Top Actions <span style="font-size:12px;color:#94a3b8;font-weight:400;">(30 days)</span></h3>
            <canvas id="actionChart" style="width:100%;max-height:220px;"></canvas>
        </div>
    </div>

    <!-- ===== ACTIVITY LOGS (DataTable) ===== -->
    <div class="card" style="margin-bottom:16px;">
        <div class="card-title">
            <i class="fas fa-history" style="color:#22C55E;"></i> Activity Logs
            <span class="badge-sm" style="background:#dcfce7;color:#166534;">Recent</span>
        </div>
        <div class="table-wrapper">
            <table id="activityTable" class="display responsive" style="width:100%;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Action</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- ===== GOAT INVENTORY ===== -->
    <h2 style="font-size:18px;font-weight:700;color:#1f2937;margin:0 0 12px 0;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-paw" style="color:#22C55E;"></i> Goat Inventory
    </h2>
    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:16px;">
        <div class="stat-card" style="border-left-color:#22C55E;">
            <i class="fas fa-check-circle" style="color:#22C55E;"></i>
            <div class="stat-text">
                <div class="number"><?php echo $totalActiveGoats; ?></div>
                <div class="label">Active</div>
            </div>
        </div>
        <div class="stat-card" style="border-left-color:#f59e0b;">
            <i class="fas fa-hand-holding-usd" style="color:#f59e0b;"></i>
            <div class="stat-text">
                <div class="number"><?php echo $totalSoldGoats; ?></div>
                <div class="label">Sold</div>
            </div>
        </div>
        <div class="stat-card" style="border-left-color:#ef4444;">
            <i class="fas fa-skull" style="color:#ef4444;"></i>
            <div class="stat-text">
                <div class="number"><?php echo $totalDeceasedGoats; ?></div>
                <div class="label">Deceased</div>
            </div>
        </div>
        <div class="stat-card" style="border-left-color:#34d399;">
            <i class="fas fa-tractor" style="color:#34d399;"></i>
            <div class="stat-text">
                <div class="number"><?php echo $totalFarmersWithGoats; ?></div>
                <div class="label">Farmers</div>
            </div>
        </div>
    </div>

</div>

<!-- ===== SCRIPTS ===== -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Daily Chart
const dailyCtx = document.getElementById('dailyChart').getContext('2d');
const dailyData = <?php echo json_encode($dailyActivity); ?>;
new Chart(dailyCtx, {
    type: 'line',
    data: {
        labels: dailyData.map(d => d.date),
        datasets: [{
            data: dailyData.map(d => d.count),
            borderColor: '#22C55E',
            backgroundColor: 'rgba(34,197,94,0.08)',
            borderWidth: 2,
            tension: 0.4,
            fill: true,
            pointRadius: 2,
            pointBackgroundColor: '#15803D'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(34,197,94,0.05)' }, ticks: { font: { size: 10 } } },
            x: { grid: { display: false }, ticks: { font: { size: 10 } } }
        }
    }
});

// Action Chart
const actionCtx = document.getElementById('actionChart').getContext('2d');
const actionData = <?php echo json_encode($actionStats); ?>;
const colors = ['#22C55E','#4ADE80','#16A34A','#34D399','#15803D','#86EFAC','#0A2E1A','#BBF7D0','#14532D','#DCFCE7'];
new Chart(actionCtx, {
    type: 'bar',
    data: {
        labels: actionData.map(d => d.action),
        datasets: [{
            data: actionData.map(d => d.count),
            backgroundColor: colors.slice(0, actionData.length),
            borderRadius: 3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(34,197,94,0.05)' }, ticks: { font: { size: 10 } } },
            x: { grid: { display: false }, ticks: { font: { size: 10 } } }
        }
    }
});

// DataTable
$(document).ready(function() {
    $('#activityTable').DataTable({
        processing: true,
        serverSide: false,
        responsive: true,
        ajax: {
            url: '<?php echo BASE_URL; ?>/api/get-activity-logs.php',
            type: 'GET'
        },
        columns: [
            { data: 'id' },
            { data: 'email' },
            { 
                data: 'user_role',
                render: function(data) {
                    if (!data) return '<span class="badge badge-secondary">Unknown</span>';
                    const map = { 'admin': 'badge-admin', 'manager': 'badge-manager', 'user': 'badge-user' };
                    return '<span class="badge ' + (map[data] || 'badge-secondary') + '">' + data.toUpperCase() + '</span>';
                }
            },
            { data: 'action' },
            { 
                data: 'status',
                render: function(data) {
                    return '<span class="badge ' + (data === 'success' ? 'badge-success' : 'badge-danger') + '">' + data + '</span>';
                }
            },
            { 
                data: 'created_at',
                render: function(data) {
                    return new Date(data).toLocaleString();
                }
            }
        ],
        order: [[0, 'desc']],
        pageLength: 5,
        lengthMenu: [[5, 10, 25], [5, 10, 25]],
        language: {
            search: "",
            lengthMenu: "Show _MENU_",
            info: "_START_-_END_ of _TOTAL_",
            infoEmpty: "No entries",
            infoFiltered: ""
        },
        dom: '<"flex flex-wrap items-center gap-2"lf>rt<"flex flex-wrap items-center gap-2"ip>'
    });
});
</script>

<?php renderFooter(); ?>