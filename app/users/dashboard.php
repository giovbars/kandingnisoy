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

renderHeader('User Management');
?>

<div class="nav">
    <a href="<?php echo BASE_URL; ?>/app/<?php echo $_SESSION['role']; ?>/dashboard.php">Dashboard</a>
    <?php if ($currentRole === 'admin' || $currentRole === 'manager'): ?>
        <a href="<?php echo BASE_URL; ?>/app/users/user-create.php">Create User</a>
    <?php endif; ?>
    <a href="<?php echo BASE_URL; ?>/app/auth/signout.php">Logout</a>
</div>

<h1>User Management</h1>

<div class="info-box">
    <strong>Your Role: <span class="badge badge-<?php echo $currentRole; ?>"><?php echo ucfirst($currentRole); ?></span></strong><br>
    <?php if ($currentRole === 'admin'): ?>
        You have full access to manage all users, assign roles, and activate or deactivate accounts.
    <?php elseif ($currentRole === 'manager'): ?>
        You can manage regular user (Farmer) accounts only. Admin and Manager accounts are hidden from your view.
    <?php else: ?>
        You can only view and update your own profile.
    <?php endif; ?>
</div>

<?php if ($currentRole !== 'user'): ?>
<form method="GET" class="card" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
    <div class="form-group" style="margin-bottom: 0; flex: 2; min-width: 200px;">
        <label for="q">Search (name, email, username)</label>
        <input type="text" id="q" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search users...">
    </div>
    <?php if ($currentRole === 'admin'): ?>
    <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 150px;">
        <label for="role">Role</label>
        <select id="role" name="role">
            <option value="">All Roles</option>
            <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
            <option value="manager" <?php echo $roleFilter === 'manager' ? 'selected' : ''; ?>>Manager</option>
            <option value="user" <?php echo $roleFilter === 'user' ? 'selected' : ''; ?>>User (Farmer)</option>
        </select>
    </div>
    <?php endif; ?>
    <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 150px;">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">All Statuses</option>
            <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
            <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
        </select>
    </div>
    <div class="form-group" style="margin-bottom: 0;">
        <button type="submit">Search</button>
        <?php if ($search !== '' || $roleFilter !== '' || $statusFilter !== ''): ?>
            <a href="<?php echo BASE_URL; ?>/app/users/dashboard.php"><button type="button" style="background:#757575;">Clear</button></a>
        <?php endif; ?>
    </div>
</form>
<?php endif; ?>

<p>
    Showing <?php echo count($users); ?> of <?php echo $totalVisible; ?> user<?php echo $totalVisible == 1 ? '' : 's'; ?> you can access.
</p>

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
            <td>
                <span class="badge badge-<?php echo $user['role']; ?>">
                    <?php echo ucfirst($user['role']); ?>
                </span>
            </td>
            <td>
                <span class="badge badge-<?php echo $user['status'] === 'active' ? 'active' : 'inactive'; ?>">
                    <?php echo ucfirst($user['status']); ?>
                </span>
            </td>
            <td>
                <span class="badge badge-<?php echo $user['is_verified'] ? 'verified' : 'unverified'; ?>">
                    <?php echo $user['is_verified'] ? 'Yes' : 'No'; ?>
                </span>
            </td>
            <td><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></td>
            <td>
                <a href="user-view.php?user_id=<?php echo $user['id']; ?>">View</a>

                <?php if (canManageTarget($currentRole, $currentUserId, $user['role'], $user['id'])): ?>
                    | <a href="user-update.php?user_id=<?php echo $user['id']; ?>">Edit</a>
                <?php endif; ?>

                <?php if (canDeleteTarget($currentRole, $currentUserId, $user['role'], $user['id'])): ?>
                    | <a href="user-delete.php?user_id=<?php echo $user['id']; ?>"
                         onclick="return confirm('Are you sure you want to delete this user?')"
                         style="color: #dc3545;">Delete</a>
                <?php elseif ($user['id'] == $currentUserId): ?>
                    | <span style="color: #6c757d;" title="You cannot delete your own account">Delete</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if (empty($users)): ?>
    <p style="text-align: center; padding: 40px; color: #6c757d;">
        No users to display based on your role permissions or search filters.
    </p>
<?php endif; ?>

<?php renderFooter(); ?>
