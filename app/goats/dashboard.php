<?php
require_once '../../config/config.php';
require_once '../../config/functions.php';
require_once '../../includes/goat-logger.php';
require_once '../../includes/goat-functions.php';
requireLogin();

$currentRole = $_SESSION['role'];
$currentUserId = $_SESSION['user_id'];

// Search / filter support
$search = trim($_GET['q'] ?? '');
$sexFilter = normalizeGoatSex($_GET['sex'] ?? '') ?? '';
$healthFilter = normalizeHealthStatus($_GET['health_status'] ?? '') ?? '';
$statusFilter = normalizeGoatStatus($_GET['status'] ?? '') ?? '';
$farmerFilter = ($currentRole !== 'user') ? (int)($_GET['farmer_id'] ?? 0) : 0;

$where = [];
$params = [];

// Ownership scoping
if ($currentRole === 'user') {
    $where[] = "g.user_id = :self_id";
    $params[':self_id'] = $currentUserId;
} elseif ($farmerFilter > 0) {
    $where[] = "g.user_id = :farmer_id";
    $params[':farmer_id'] = $farmerFilter;
}

if ($search !== '') {
    $where[] = "(g.goat_id LIKE :search OR g.goat_name LIKE :search OR g.tag_number LIKE :search OR g.breed LIKE :search"
             . ($currentRole !== 'user' ? " OR u.full_name LIKE :search" : "") . ")";
    $params[':search'] = '%' . $search . '%';
}

if ($sexFilter !== '') {
    $where[] = "g.sex = :sex";
    $params[':sex'] = $sexFilter;
}

if ($healthFilter !== '') {
    $where[] = "g.health_status = :health_status";
    $params[':health_status'] = $healthFilter;
}

if ($statusFilter !== '') {
    $where[] = "g.status = :status";
    $params[':status'] = $statusFilter;
}

$sql = "SELECT g.*, u.full_name AS owner_name, u.username AS owner_username
        FROM goats g
        JOIN users u ON u.id = g.user_id";
if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY g.updated_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$goats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Summary statistics
$scopeWhere = "";
$scopeParams = [];
if ($currentRole === 'user') {
    $scopeWhere = " WHERE user_id = :self_id";
    $scopeParams[':self_id'] = $currentUserId;
} elseif ($farmerFilter > 0) {
    $scopeWhere = " WHERE user_id = :farmer_id";
    $scopeParams[':farmer_id'] = $farmerFilter;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM goats" . $scopeWhere . ($scopeWhere ? " AND status = 'active'" : " WHERE status = 'active'"));
$stmt->execute($scopeParams);
$activeCount = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT sex, COUNT(*) as c FROM goats" . $scopeWhere . ($scopeWhere ? " AND status = 'active'" : " WHERE status = 'active'") . " GROUP BY sex");
$stmt->execute($scopeParams);
$sexCounts = ['male' => 0, 'female' => 0];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $sexCounts[$row['sex']] = (int)$row['c'];
}

$stmt = $pdo->prepare("SELECT health_status, COUNT(*) as c FROM goats" . $scopeWhere . ($scopeWhere ? " AND status = 'active'" : " WHERE status = 'active'") . " GROUP BY health_status");
$stmt->execute($scopeParams);
$healthCounts = ['healthy' => 0, 'sick' => 0, 'unknown' => 0];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $healthCounts[$row['health_status']] = (int)$row['c'];
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM goats" . $scopeWhere . ($scopeWhere ? " AND status = 'sold'" : " WHERE status = 'sold'"));
$stmt->execute($scopeParams);
$soldCount = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM goats" . $scopeWhere . ($scopeWhere ? " AND status = 'deceased'" : " WHERE status = 'deceased'"));
$stmt->execute($scopeParams);
$deceasedCount = (int)$stmt->fetchColumn();

// Farmer list
$farmers = [];
if ($currentRole !== 'user') {
    $farmers = $pdo->query("SELECT id, full_name, username FROM users WHERE role = 'user' ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
}

$title = 'Goat Inventory';
renderHeader($title);
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
        .stat-grid { grid-template-columns: repeat(4, 1fr); gap: 12px; }
    }
    @media (min-width: 1024px) {
        .stat-grid { grid-template-columns: repeat(7, 1fr); }
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

    /* ---------- Filter form ---------- */
    .filter-form {
        background: white;
        border-radius: 12px;
        padding: 14px;
        border: 1px solid #dcfce7;
        margin-bottom: 12px;
    }
    .filter-form .filter-group {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: flex-end;
    }
    .filter-form .filter-group > div {
        flex: 1 1 140px;
        min-width: 100px;
    }
    .filter-form label {
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: block;
        margin-bottom: 3px;
    }
    .filter-form input, .filter-form select {
        width: 100%;
        padding: 8px 12px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        background: white;
        color: #1e293b;
        transition: border-color 0.2s;
    }
    .filter-form input:focus, .filter-form select:focus {
        border-color: #22C55E;
        outline: none;
        box-shadow: 0 0 0 3px rgba(34,197,94,0.15);
    }
    .filter-form .btn {
        height: 44px;
        padding: 0 20px;
    }

    /* ---------- Goat cards ---------- */
    .goat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 12px;
    }
    @media (min-width: 641px) {
        .goat-grid { grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
    }

    .goat-card {
        background: white;
        border-radius: 12px;
        padding: 14px 16px;
        border: 1px solid #dcfce7;
        box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        min-width: 0;
    }
    .goat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(34,197,94,0.12);
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
    .goat-card-name {
        font-size: 17px;
        font-weight: 600;
        color: #1e293b;
        margin-top: 2px;
        overflow-wrap: anywhere;
    }
    .goat-card-date {
        font-size: 12px;
        color: #94a3b8;
        white-space: nowrap;
    }
    .goat-card-badges {
        display: flex;
        gap: 8px;
        margin-top: 8px;
        flex-wrap: wrap;
    }
    .goat-badge {
        font-size: 11px;
        font-weight: 600;
        padding: 3px 12px;
        border-radius: 999px;
        display: inline-block;
    }
    .health-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 999px;
        margin-right: 4px;
        vertical-align: middle;
    }
    .health-dot.healthy { background: #22C55E; }
    .health-dot.sick { background: #ef4444; }
    .health-dot.unknown { background: #94a3b8; }

    .goat-actions {
        display: flex;
        gap: 8px;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #f0fdf4;
    }
    .goat-actions .btn {
        flex: 1;
        justify-content: center;
        font-size: 13px;
        padding: 6px 12px;
        min-height: 38px;
    }

    /* ---------- Empty state ---------- */
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #a0aec0;
    }
    .empty-state i {
        font-size: 56px;
        color: #bbf7d0;
        display: block;
        margin-bottom: 14px;
    }
    .empty-state p {
        font-size: 18px;
        margin: 0;
        color: #4a5568;
    }
    .empty-state .sub {
        font-size: 15px;
        color: #a0aec0;
        margin-top: 6px;
    }

    /* ---------- Animation ---------- */
    @keyframes sway { 0%,100% { transform: rotate(-2deg); } 50% { transform: rotate(2deg); } }
</style>

<!-- ============================================================
     MAIN CONTENT
============================================================ -->
<div class="dashboard-wrap" style="width:100%;display:flex;flex-direction:column;background:#f0fdf4;">

    <!-- ===== HEADER ===== -->
    <div class="header-grass">
        <div class="blob-1"></div>
        <div class="blob-2"></div>
        <div class="header-inner">
            <div class="header-title-row">
                <span class="header-goat">🐐</span>
                <div style="min-width:0;">
                    <h1>
                        Goat Inventory
                        <span class="header-role-badge"><?php echo ucfirst($currentRole); ?></span>
                    </h1>
                    <p>
                        <i class="fas fa-leaf" style="font-size:12px;"></i> <?php echo count($goats); ?> goat<?php echo count($goats) != 1 ? 's' : ''; ?> found
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
    <?php
    // Determine dashboard link based on role
    $dashboardLink = BASE_URL . '/app/' . $currentRole . '/dashboard.php';
    ?>
    <nav class="dash-nav">
        <a href="<?php echo $dashboardLink; ?>" class="dash-nav-link">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="<?php echo BASE_URL; ?>/app/goats/dashboard.php" class="dash-nav-link active">
            <i class="fas fa-goat"></i> Inventory
        </a>
        <?php if ($currentRole === 'user' || $currentRole === 'admin'): ?>
            <a href="<?php echo BASE_URL; ?>/app/goats/goat-create.php" class="dash-nav-link">
                <i class="fas fa-plus-circle"></i> Add Goat
            </a>
        <?php endif; ?>
        <?php if ($currentRole === 'manager' || $currentRole === 'admin'): ?>
            <a href="<?php echo BASE_URL; ?>/app/goats/farmer-overview.php" class="dash-nav-link">
                <i class="fas fa-tractor"></i> Farmers
            </a>
        <?php endif; ?>
        <a href="<?php echo BASE_URL; ?>/app/auth/signout.php" class="dash-nav-link logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>

    <!-- ===== SICK ALERT ===== -->
    <?php if ($healthCounts['sick'] > 0): ?>
    <div class="alert-warning">
        <i class="fas fa-exclamation-triangle" style="font-size:18px;color:#d97706;flex-shrink:0;"></i>
        <div>
            <strong>⚠️ Health Alert:</strong> You have <strong><?php echo $healthCounts['sick']; ?></strong> sick goat(s). Please check their health status and provide care.
            <a href="?health_status=sick">View sick goats →</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== STATS CARDS ===== -->
    <div class="stat-grid">
        <div class="stat-card" style="border-left-color:#22C55E;">
            <i class="fas fa-check-circle" style="color:#22C55E;"></i>
            <div class="stat-text">
                <div class="number"><?php echo $activeCount; ?></div>
                <div class="label">Active</div>
            </div>
        </div>
        <div class="stat-card" style="border-left-color:#3b82f6;">
            <i class="fas fa-venus-mars" style="color:#3b82f6;"></i>
            <div class="stat-text">
                <div class="number"><?php echo $sexCounts['male']; ?>/<?php echo $sexCounts['female']; ?></div>
                <div class="label">Male/Female</div>
            </div>
        </div>
        <div class="stat-card" style="border-left-color:#4ADE80;">
            <i class="fas fa-heart" style="color:#4ADE80;"></i>
            <div class="stat-text">
                <div class="number"><?php echo $healthCounts['healthy']; ?></div>
                <div class="label">Healthy</div>
            </div>
        </div>
        <div class="stat-card" style="border-left-color:#ef4444;">
            <i class="fas fa-medkit" style="color:#ef4444;"></i>
            <div class="stat-text">
                <div class="number"><?php echo $healthCounts['sick']; ?></div>
                <div class="label">Sick</div>
            </div>
        </div>
        <div class="stat-card" style="border-left-color:#94a3b8;">
            <i class="fas fa-question-circle" style="color:#94a3b8;"></i>
            <div class="stat-text">
                <div class="number"><?php echo $healthCounts['unknown']; ?></div>
                <div class="label">Unknown</div>
            </div>
        </div>
        <div class="stat-card" style="border-left-color:#f59e0b;">
            <i class="fas fa-hand-holding-usd" style="color:#f59e0b;"></i>
            <div class="stat-text">
                <div class="number"><?php echo $soldCount; ?></div>
                <div class="label">Sold</div>
            </div>
        </div>
        <div class="stat-card" style="border-left-color:#9ca3af;">
            <i class="fas fa-skull" style="color:#9ca3af;"></i>
            <div class="stat-text">
                <div class="number"><?php echo $deceasedCount; ?></div>
                <div class="label">Deceased</div>
            </div>
        </div>
    </div>

    <!-- ===== FILTER FORM ===== -->
    <form method="GET" class="filter-form">
        <div class="filter-group">
            <div>
                <label for="q"><i class="fas fa-search"></i> Search</label>
                <input type="text" id="q" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="ID, name, tag...">
            </div>
            <?php if ($currentRole !== 'user'): ?>
            <div>
                <label for="farmer_id"><i class="fas fa-user"></i> Farmer</label>
                <select id="farmer_id" name="farmer_id">
                    <option value="">All Farmers</option>
                    <?php foreach ($farmers as $f): ?>
                        <option value="<?php echo $f['id']; ?>" <?php echo $farmerFilter === (int)$f['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($f['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div>
                <label for="sex"><i class="fas fa-venus-mars"></i> Sex</label>
                <select id="sex" name="sex">
                    <option value="">All</option>
                    <option value="male" <?php echo $sexFilter === 'male' ? 'selected' : ''; ?>>Male</option>
                    <option value="female" <?php echo $sexFilter === 'female' ? 'selected' : ''; ?>>Female</option>
                </select>
            </div>
            <div>
                <label for="health_status"><i class="fas fa-heartbeat"></i> Health</label>
                <select id="health_status" name="health_status">
                    <option value="">All</option>
                    <option value="healthy" <?php echo $healthFilter === 'healthy' ? 'selected' : ''; ?>>Healthy</option>
                    <option value="sick" <?php echo $healthFilter === 'sick' ? 'selected' : ''; ?>>Sick</option>
                    <option value="unknown" <?php echo $healthFilter === 'unknown' ? 'selected' : ''; ?>>Unknown</option>
                </select>
            </div>
            <div>
                <label for="status"><i class="fas fa-tag"></i> Status</label>
                <select id="status" name="status">
                    <option value="">All</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="sold" <?php echo $statusFilter === 'sold' ? 'selected' : ''; ?>>Sold</option>
                    <option value="deceased" <?php echo $statusFilter === 'deceased' ? 'selected' : ''; ?>>Deceased</option>
                </select>
            </div>
            <div style="flex:0 0 auto;display:flex;gap:6px;align-items:center;">
                <button type="submit" class="btn btn-primary" style="height:44px;padding:0 20px;">
                    <i class="fas fa-search"></i> Search
                </button>
                <?php if ($search !== '' || $sexFilter !== '' || $healthFilter !== '' || $statusFilter !== '' || $farmerFilter > 0): ?>
                    <a href="<?php echo BASE_URL; ?>/app/goats/dashboard.php" class="btn btn-outline" style="height:44px;padding:0 18px;display:inline-flex;align-items:center;">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <!-- ===== ADD GOAT BUTTON (if allowed) ===== -->
    <?php if ($currentRole === 'user' || $currentRole === 'admin'): ?>
    <div style="margin-bottom:16px;">
        <a href="<?php echo BASE_URL; ?>/app/goats/goat-create.php" class="btn btn-primary" style="padding:12px 28px;font-size:16px;width:100%;justify-content:center;">
            <i class="fas fa-plus-circle" style="font-size:18px;"></i> Add New Goat
        </a>
    </div>
    <?php endif; ?>

    <!-- ===== GOAT LIST ===== -->
    <?php if (empty($goats)): ?>
        <div class="card empty-state">
            <i class="fas fa-goat"></i>
            <p>No goat records found.</p>
            <div class="sub">Try adjusting your search filters or add a new goat.</div>
            <?php if ($currentRole === 'user' || $currentRole === 'admin'): ?>
                <a href="<?php echo BASE_URL; ?>/app/goats/goat-create.php" class="btn btn-primary" style="margin-top:12px;">
                    <i class="fas fa-plus-circle"></i> Add Your First Goat
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="goat-grid">
            <?php foreach ($goats as $goat):
                $healthColor = $goat['health_status'] === 'healthy' ? '#22C55E' : ($goat['health_status'] === 'sick' ? '#ef4444' : '#94a3b8');
                $healthBg = $goat['health_status'] === 'healthy' ? '#dcfce7' : ($goat['health_status'] === 'sick' ? '#fef3c7' : '#f3f4f6');
                $healthText = $goat['health_status'] === 'healthy' ? '#166534' : ($goat['health_status'] === 'sick' ? '#d97706' : '#6b7280');
                $sexColor = $goat['sex'] === 'male' ? '#2563eb' : '#db2777';
                $sexBg = $goat['sex'] === 'male' ? '#dbeafe' : '#fce7f3';
                $canEdit = canEditGoat($currentRole, $currentUserId, $goat['user_id']);
            ?>
                <div class="goat-card" style="border-left:5px solid <?php echo $healthColor; ?>;">
                    <div class="goat-card-top">
                        <div style="min-width:0;">
                            <a href="goat-view.php?id=<?php echo urlencode($goat['id']); ?>" class="goat-card-id">
                                <i class="fas fa-tag" style="color:#86efac;font-size:12px;"></i> <?php echo htmlspecialchars($goat['goat_id']); ?>
                            </a>
                            <div class="goat-card-name">
                                <?php echo htmlspecialchars($goat['goat_name']); ?>
                                <?php if ($goat['tag_number']): ?>
                                    <span style="font-size:13px;color:#94a3b8;font-weight:400;">(<?php echo htmlspecialchars($goat['tag_number']); ?>)</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="goat-card-date">
                            <i class="fas fa-calendar-alt"></i> <?php echo date('M d', strtotime($goat['updated_at'])); ?>
                        </span>
                    </div>

                    <div class="goat-card-badges">
                        <span class="goat-badge" style="background:<?php echo $sexBg; ?>;color:<?php echo $sexColor; ?>;">
                            <?php echo ucfirst($goat['sex']); ?>
                        </span>
                        <span class="goat-badge" style="background:<?php echo $healthBg; ?>;color:<?php echo $healthText; ?>;border:1px solid <?php echo $healthColor; ?>40;">
                            <span class="health-dot <?php echo $goat['health_status']; ?>"></span>
                            <?php echo healthStatusLabel($goat['health_status']); ?>
                        </span>
                        <span class="goat-badge" style="background:#DCFCE7;color:#166534;border:1px solid #BBF7D0;">
                            <?php echo goatStatusLabel($goat['status']); ?>
                        </span>
                        <?php if ($currentRole !== 'user' && !empty($goat['owner_name'])): ?>
                            <span class="goat-badge" style="background:#f1f5f9;color:#475569;">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($goat['owner_name']); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="goat-actions">
                        <a href="goat-view.php?id=<?php echo urlencode($goat['id']); ?>" class="btn btn-outline">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <?php if ($canEdit): ?>
                            <a href="goat-update.php?id=<?php echo urlencode($goat['id']); ?>" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php renderFooter(); ?>