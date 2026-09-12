<?php
require_once '../../config/config.php';
require_once '../../config/functions.php';
require_once '../../includes/goat-functions.php';
requireAnyRole(['manager', 'admin']);

$currentRole = $_SESSION['role'];

$search = trim($_GET['q'] ?? '');

$where = "WHERE u.role = 'user'";
$params = [];
if ($search !== '') {
    $where .= " AND (u.full_name LIKE :search OR u.username LIKE :search OR u.email LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$sql = "
    SELECT
        u.id, u.full_name, u.username, u.email,
        COUNT(CASE WHEN g.status = 'active' THEN 1 END) AS active_count,
        COUNT(CASE WHEN g.status = 'active' AND g.sex = 'male' THEN 1 END) AS active_male,
        COUNT(CASE WHEN g.status = 'active' AND g.sex = 'female' THEN 1 END) AS active_female,
        COUNT(CASE WHEN g.status = 'active' AND g.health_status = 'healthy' THEN 1 END) AS healthy_count,
        COUNT(CASE WHEN g.status = 'active' AND g.health_status = 'sick' THEN 1 END) AS sick_count,
        COUNT(CASE WHEN g.status = 'sold' THEN 1 END) AS sold_count,
        COUNT(CASE WHEN g.status = 'deceased' THEN 1 END) AS deceased_count
    FROM users u
    LEFT JOIN goats g ON g.user_id = u.id
    $where
    GROUP BY u.id, u.full_name, u.username, u.email
    ORDER BY active_count DESC, u.full_name ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$farmers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalFarmers = count($pdo->query("SELECT id FROM users WHERE role = 'user'")->fetchAll());
$totalActiveGoats = (int)$pdo->query("SELECT COUNT(*) FROM goats WHERE status = 'active'")->fetchColumn();

$title = 'Farmer Inventory Overview';
renderHeader($title);

// Determine if we are on the farmer overview page
$isFarmerOverview = (basename($_SERVER['PHP_SELF']) == 'farmer-overview.php');
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
    .btn-secondary {
        background: #e2e8f0;
        color: #1f2937;
    }
    .btn-secondary:hover { background: #cbd5e1; }

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

    /* ---------- Stat cards ---------- */
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

    /* ---------- Search form ---------- */
    .search-form {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: flex-end;
    }
    .search-form .form-group {
        margin-bottom: 0;
        flex: 2 1 220px;
    }
    .search-form label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 4px;
    }
    .search-form input[type="text"] {
        width: 100%;
        padding: 8px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        transition: 0.2s;
        background: white;
    }
    .search-form input[type="text"]:focus {
        outline: none;
        border-color: #22C55E;
        box-shadow: 0 0 0 3px rgba(34,197,94,0.2);
    }
    .search-form .btn-group {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
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

    /* ---------- Animation ---------- */
    @keyframes sway { 0%,100% { transform: rotate(-2deg); } 50% { transform: rotate(2deg); } }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    .stat-card, .card {
        animation: fadeInUp 0.4s ease both;
    }
    .stat-card:nth-child(2) { animation-delay: 0.05s; }
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
                        Farmer Inventory Overview
                        <span class="header-role-badge"><?php echo ucfirst($currentRole); ?></span>
                    </h1>
                    <p>
                        <i class="fas fa-leaf" style="font-size:12px;"></i> Overview of all farmers and their goat inventory
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
        <?php
        $dashboardLink = BASE_URL . '/app/' . $currentRole . '/dashboard.php';
        ?>
        <a href="<?php echo $dashboardLink; ?>" class="dash-nav-link">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>

        <a href="<?php echo BASE_URL; ?>/app/goats/dashboard.php" class="dash-nav-link">
            <i class="fas fa-goat"></i> Goat Inventory
        </a>

        <!-- Farmer Overview link: non-clickable when on this page -->
        <?php if ($isFarmerOverview): ?>
            <span class="dash-nav-link active">
                <i class="fas fa-users"></i> Farmer Overview
            </span>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>/app/goats/farmer-overview.php" class="dash-nav-link active">
                <i class="fas fa-users"></i> Farmer Overview
            </a>
        <?php endif; ?>

        <a href="<?php echo BASE_URL; ?>/app/auth/signout.php" class="dash-nav-link logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>

    <!-- ===== SUMMARY STATS ===== -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
        <div class="stat-card" style="border-left-color:#3b82f6;">
            <i class="fas fa-users" style="color:#3b82f6;"></i>
            <div class="stat-text">
                <div class="number"><?php echo $totalFarmers; ?></div>
                <div class="label">Total Registered Farmers</div>
            </div>
        </div>
        <div class="stat-card" style="border-left-color:#22C55E;">
            <i class="fas fa-check-circle" style="color:#22C55E;"></i>
            <div class="stat-text">
                <div class="number"><?php echo $totalActiveGoats; ?></div>
                <div class="label">Total Active Goats</div>
            </div>
        </div>
    </div>

    <!-- ===== SEARCH FORM ===== -->
    <div class="card" style="margin-bottom:16px;">
        <div class="card-title"><i class="fas fa-search" style="color:#22C55E;"></i> Search Farmers</div>
        <form method="GET" class="search-form">
            <div class="form-group">
                <label for="q">Search by name, username, or email</label>
                <input type="text" id="q" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="e.g. Juan">
            </div>
            <div class="btn-group">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                <?php if ($search !== ''): ?>
                    <a href="<?php echo BASE_URL; ?>/app/goats/farmer-overview.php" class="btn btn-secondary"><i class="fas fa-times"></i> Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- ===== FARMERS TABLE ===== -->
    <div class="card" style="margin-bottom:16px;">
        <div class="card-title" style="justify-content:space-between;">
            <span><i class="fas fa-list" style="color:#22C55E;"></i> Farmer Inventory List</span>
            <span class="badge-sm" style="background:#dcfce7;color:#166534;">
                <?php echo count($farmers); ?> farmer<?php echo count($farmers) !== 1 ? 's' : ''; ?> found
            </span>
        </div>

        <?php if (empty($farmers)): ?>
            <div style="text-align:center;padding:40px 20px;color:#a0aec0;">
                <i class="fas fa-user-slash" style="font-size:40px;color:#bbf7d0;display:block;margin-bottom:10px;"></i>
                <p style="font-size:14px;margin:0;color:#4a5568;">No farmers found matching your search.</p>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Farmer</th>
                            <th>Active Goats</th>
                            <th>Male / Female</th>
                            <th>Healthy / Sick</th>
                            <th>Sold</th>
                            <th>Deceased</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($farmers as $f): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($f['full_name']); ?></strong>
                                <small style="color:#94a3b8;display:block;font-size:12px;">@<?php echo htmlspecialchars($f['username']); ?></small>
                            </td>
                            <td><span class="badge" style="background:#dcfce7;color:#166534;"><?php echo (int)$f['active_count']; ?></span></td>
                            <td><?php echo (int)$f['active_male']; ?> / <?php echo (int)$f['active_female']; ?></td>
                            <td>
                                <span class="badge badge-success"><?php echo (int)$f['healthy_count']; ?></span>
                                <span class="badge badge-danger"><?php echo (int)$f['sick_count']; ?></span>
                            </td>
                            <td><?php echo (int)$f['sold_count']; ?></td>
                            <td><?php echo (int)$f['deceased_count']; ?></td>
                            <td>
                                <a href="<?php echo BASE_URL; ?>/app/goats/dashboard.php?farmer_id=<?php echo $f['id']; ?>" class="btn btn-primary" style="font-size:12px;padding:4px 12px;min-height:30px;">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php renderFooter(); ?>