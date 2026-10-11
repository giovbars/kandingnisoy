<?php
require_once '../../config/config.php';
require_once '../../config/functions.php';
requireLogin();

// Role-based filtering
$currentRole = $_SESSION['role'];
$currentUserId = $_SESSION['user_id'];

// Search / filter support
$search = trim($_GET['q'] ?? '');
$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$where = [];
$params = [];

// Build query based on role hierarchy
if ($currentRole === 'admin') {
    // Admin can see all users
} elseif ($currentRole === 'manager') {
    // Manager can only see regular users (hide admins and other managers)
    $where[] = "role = 'user'";
} else {
    // Regular users can only see themselves
    $where[] = "id = :self_id";
    $params[':self_id'] = $currentUserId;
}

if ($search !== '') {
    $where[] = "(full_name LIKE :search OR email LIKE :search OR username LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

// Role/status filters only make sense for admin and manager (who can see more than one user)
if ($currentRole !== 'user') {
    if ($roleFilter !== '' && in_array($roleFilter, ['admin', 'manager', 'user'])) {
        // Manager cannot filter to admin/manager since they're already excluded above,
        // but guard anyway for admin
        $where[] = "role = :role_filter";
        $params[':role_filter'] = $roleFilter;
    }
    if ($statusFilter !== '' && in_array($statusFilter, ['active', 'inactive'])) {
        $where[] = "status = :status_filter";
        $params[':status_filter'] = $statusFilter;
    }
}

$sql = "SELECT id, full_name, username, email, role, status, is_verified, created_at, updated_at FROM users";
if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY
    CASE role
        WHEN 'admin' THEN 1
        WHEN 'manager' THEN 2
        WHEN 'user' THEN 3
    END, full_name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total count (unfiltered, but respecting role visibility) for the summary line
if ($currentRole === 'admin') {
    $totalVisible = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
} elseif ($currentRole === 'manager') {
    $totalVisible = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
} else {
    $totalVisible = 1;
}

$title = 'User Management';
renderHeader($title);

// Determine if we are on the user management page itself
$isUserManagement = (basename($_SERVER['PHP_SELF']) == 'dashboard.php' && dirname($_SERVER['PHP_SELF']) == '/app/users');
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
    .btn-danger {
        background: #ef4444;
        color: white;
    }
    .btn-danger:hover { background: #dc2626; }

    /* ---------- Header (matching dashboard) ---------- */
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

    /* ---------- Filter form ---------- */
    .filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: flex-end;
    }
    .filter-form .form-group {
        margin-bottom: 0;
        flex: 1 1 160px;
        min-width: 140px;
    }
    .filter-form label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 4px;
    }
    .filter-form input[type="text"],
    .filter-form select {
        width: 100%;
        padding: 7px 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        transition: 0.2s;
        background: white;
    }
    .filter-form input:focus,
    .filter-form select:focus {
        outline: none;
        border-color: #22C55E;
        box-shadow: 0 0 0 3px rgba(34,197,94,0.2);
    }
    .filter-form .btn-group {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
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
    .badge-admin { background: #fce7f3; color: #9d174d; }
    .badge-manager { background: #dbeafe; color: #1e40af; }
    .badge-user { background: #dcfce7; color: #166534; }
    .badge-active { background: #dcfce7; color: #166534; }
    .badge-inactive { background: #f3f4f6; color: #6b7280; }
    .badge-verified { background: #dcfce7; color: #166534; }
    .badge-unverified { background: #fef3c7; color: #92400e; }

    /* ---------- Info box ---------- */
    .info-box {
        background: #f0fdf4;
        border-left: 5px solid #22C55E;
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 16px;
        font-size: 14px;
        color: #1f2937;
    }
    .info-box strong {
        color: #15803D;
    }

    /* ---------- Animation ---------- */
    @keyframes sway { 0%,100% { transform: rotate(-2deg); } 50% { transform: rotate(2deg); } }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    .header-grass, .info-box, .card {
        animation: fadeInUp 0.4s ease both;
    }
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
                        User Management
                        <span class="header-role-badge"><?php echo ucfirst($currentRole); ?></span>
                    </h1>
                    <p>
                        <i class="fas fa-leaf" style="font-size:12px;"></i> Manage users based on your role permissions
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
        // Dashboard link - always clickable (since this is the user management page)
        $dashboardLink = BASE_URL . '/app/' . $currentRole . '/dashboard.php';
        ?>
        <a href="<?php echo $dashboardLink; ?>" class="dash-nav-link">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>

        <!-- User Management link: non-clickable when on this page -->
        <?php if ($isUserManagement): ?>
            <span class="dash-nav-link active">
                <i class="fas fa-users"></i> User Management
            </span>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>/app/users/dashboard.php" class="dash-nav-link active">
                <i class="fas fa-users"></i> User Management
            </a>
        <?php endif; ?>

        <?php if ($currentRole === 'admin' || $currentRole === 'manager'): ?>
            <a href="<?php echo BASE_URL; ?>/app/users/user-create.php" class="dash-nav-link">
                <i class="fas fa-user-plus"></i> Create User
            </a>
        <?php endif; ?>

        <a href="<?php echo BASE_URL; ?>/app/goats/dashboard.php" class="dash-nav-link">
            <i class="fas fa-goat"></i> Goat Inventory
        </a>

        <?php if ($currentRole === 'admin'): ?>
            <a href="<?php echo BASE_URL; ?>/app/goats/farmer-overview.php" class="dash-nav-link">
                <i class="fas fa-users"></i> Farmer Overview
            </a>
        <?php endif; ?>

        <a href="<?php echo BASE_URL; ?>/app/auth/signout.php" class="dash-nav-link logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>

    <!-- ===== INFO BOX ===== -->
    <div class="info-box">
        <strong><i class="fas fa-info-circle"></i> Your Role: <span class="badge badge-<?php echo $currentRole; ?>"><?php echo ucfirst($currentRole); ?></span></strong><br>
        <?php if ($currentRole === 'admin'): ?>
            You have full access to manage all users, assign roles, and activate or deactivate accounts.
        <?php elseif ($currentRole === 'manager'): ?>
            You can manage regular user (Farmer) accounts only. Admin and Manager accounts are hidden from your view.
        <?php else: ?>
            You can only view and update your own profile.
        <?php endif; ?>
    </div>

    <!-- ===== FILTERS (only for admin/manager) ===== -->
    <?php if ($currentRole !== 'user'): ?>
    <div class="card" style="margin-bottom:16px;">
        <div class="card-title"><i class="fas fa-filter" style="color:#22C55E;"></i> Filter Users</div>
        <form method="GET" class="filter-form">
            <div class="form-group">
                <label for="q">Search</label>
                <input type="text" id="q" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name, email, username">
            </div>
            <?php if ($currentRole === 'admin'): ?>
            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role">
                    <option value="">All Roles</option>
                    <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="manager" <?php echo $roleFilter === 'manager' ? 'selected' : ''; ?>>Manager</option>
                    <option value="user" <?php echo $roleFilter === 'user' ? 'selected' : ''; ?>>User (Farmer)</option>
                </select>
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="btn-group">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                <?php if ($search !== '' || $roleFilter !== '' || $statusFilter !== ''): ?>
                    <a href="<?php echo BASE_URL; ?>/app/users/dashboard.php" class="btn btn-secondary"><i class="fas fa-times"></i> Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- ===== USER TABLE ===== -->
    <div class="card" style="margin-bottom:16px;">
        <div class="card-title" style="justify-content:space-between;">
            <span><i class="fas fa-list" style="color:#22C55E;"></i> Users</span>
            <span class="badge-sm" style="background:#dcfce7;color:#166534;">
                <?php echo count($users); ?> of <?php echo $totalVisible; ?>
            </span>
        </div>

        <?php if (empty($users)): ?>
            <div style="text-align:center;padding:40px 20px;color:#a0aec0;">
                <i class="fas fa-user-slash" style="font-size:40px;color:#bbf7d0;display:block;margin-bottom:10px;"></i>
                <p style="font-size:14px;margin:0;color:#4a5568;">No users to display based on your role permissions or search filters.</p>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Verified</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><span class="badge badge-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span></td>
                            <td><span class="badge badge-<?php echo $user['status'] === 'active' ? 'active' : 'inactive'; ?>"><?php echo ucfirst($user['status']); ?></span></td>
                            <td><span class="badge badge-<?php echo $user['is_verified'] ? 'verified' : 'unverified'; ?>"><?php echo $user['is_verified'] ? 'Yes' : 'No'; ?></span></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></td>
                            <td>
                                <a href="user-view.php?user_id=<?php echo $user['id']; ?>" class="btn btn-primary" style="font-size:12px;padding:3px 10px;min-height:30px;">View</a>
                                <?php if (canManageTarget($currentRole, $currentUserId, $user['role'], $user['id'])): ?>
                                    <a href="user-update.php?user_id=<?php echo $user['id']; ?>" class="btn btn-secondary" style="font-size:12px;padding:3px 10px;min-height:30px;">Edit</a>
                                <?php endif; ?>
                                <?php if (canDeleteTarget($currentRole, $currentUserId, $user['role'], $user['id'])): ?>
                                    <a href="user-delete.php?user_id=<?php echo $user['id']; ?>" class="btn btn-danger" style="font-size:12px;padding:3px 10px;min-height:30px;" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                                <?php elseif ($user['id'] == $currentUserId): ?>
                                    <span class="badge" style="background:#f3f4f6;color:#6b7280;font-size:12px;" title="You cannot delete your own account">Delete</span>
                                <?php endif; ?>
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