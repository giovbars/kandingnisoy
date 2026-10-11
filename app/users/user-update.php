<?php
require_once '../../config/config.php';
require_once '../../config/functions.php';
require_once '../../includes/activity-logger.php';
requireLogin();

$currentRole = $_SESSION['role'];
$currentUserId = $_SESSION['user_id'];

$userId = $_GET['user_id'] ?? 0;
$message = '';
$success = false;

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    renderHeader('Update User');
    echo '<div class="error">User not found.</div>';
    echo '<p><a href="dashboard.php">Back to Users</a></p>';
    renderFooter();
    exit;
}

// Enforce role-based access: Admin manages anyone, Manager manages only
// regular Users, and everyone can manage (edit) their own profile.
if (!canManageTarget($currentRole, $currentUserId, $user['role'], $user['id'])) {
    die("Access denied. You do not have permission to edit this user.");
}

$isSelf = ($user['id'] == $currentUserId);
$canChangeRole = canAssignRole($currentRole) && !$isSelf;
$canChangeStatus = ($currentRole === 'admin' || ($currentRole === 'manager' && $user['role'] === 'user')) && !$isSelf;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? $user['role'];
    $status = $_POST['status'] ?? $user['status'];

    $updates = [];
    $params = [];
    $passwordChanged = false;

    if ($full_name !== '' && $full_name !== $user['full_name']) {
        $updates[] = "full_name = ?";
        $params[] = $full_name;
    }

    if ($username !== '' && $username !== $user['username']) {
        $updates[] = "username = ?";
        $params[] = $username;
    }

    if ($email !== '' && $email !== $user['email']) {
        $updates[] = "email = ?";
        $params[] = $email;
    }

    if ($password !== '') {
        if (strlen($password) < 6) {
            $message = "Password must be at least 6 characters. No changes were saved.";
        } else {
            $updates[] = "password = ?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
            $passwordChanged = true;
        }
    }

    // Only Admin can reassign roles, and never for their own account
    if ($canChangeRole && in_array($role, ['admin', 'manager', 'user']) && $role !== $user['role']) {
        $updates[] = "role = ?";
        $params[] = $role;
    }

    // Admin can (de)activate anyone; Manager only regular Users; nobody can
    // change their own status.
    if ($canChangeStatus && in_array($status, ['active', 'inactive']) && $status !== $user['status']) {
        $updates[] = "status = ?";
        $params[] = $status;
    }

    if (!empty($updates) && $message === '') {
        $params[] = $userId;
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $message = "User updated successfully!";
            $success = true;

            // Log the update action performed by the admin/manager (or self)
            logActivity($pdo, $_SESSION['user_id'], $_SESSION['email'], 'user_updated', 'success');

            // Password changes are logged separately per the audit requirements
            if ($passwordChanged) {
                logActivity($pdo, $userId, $user['email'], 'password_changed', 'success');
            } else {
                logActivity($pdo, $userId, $user['email'], 'profile_updated', 'success');
            }

            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch(PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = "Error updating user: that email address or username is already taken.";
            } else {
                $message = "Error updating user: " . $e->getMessage();
            }

            // Log failed update
            logActivity($pdo, $_SESSION['user_id'], $_SESSION['email'], 'user_updated', 'failed');
        }
    } elseif (empty($updates) && $message === '') {
        $message = "No changes were submitted.";
    }
}

$title = 'Update User';
renderHeader($title);
?>

<!-- ===== FULLSCREEN STYLES (matching dashboards) ===== -->
<style>
    .main-content {
        width: 100% !important;
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
        min-height: 100vh !important;
        background: #f0fdf4 !important;
        display: block !important;
    }
    .main-content .container {
        width: 100% !important;
        max-width: 1400px !important;
        padding: 30px 20px 40px 20px !important;
        margin: 0 auto !important;
        min-height: 100vh !important;
        display: flex !important;
        flex-direction: column !important;
        background: #f0fdf4 !important;
        box-sizing: border-box !important;
    }
    @media (min-width: 640px) { .main-content .container { padding: 40px 28px 40px 28px !important; } }
    @media (min-width: 1024px) { .main-content .container { padding: 50px 36px 40px 36px !important; } }
    html, body {
        height: 100% !important;
        min-height: 100vh !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #f0fdf4 !important;
        font-size: 16px !important;
        font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
    }
    body, .main-content, .container, * { font-size: inherit; }

    .header-grass h1 { font-size: 28px !important; }
    .header-grass p { font-size: 16px !important; }

    .card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        border: 1px solid #dcfce7;
        padding: 20px 24px;
        margin-bottom: 16px;
    }
    .card-title {
        font-size: 20px;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .card-title i { color: #22C55E; }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 24px;
        border-radius: 999px;
        font-weight: 600;
        font-size: 15px;
        text-decoration: none;
        transition: all 0.2s;
        border: none;
        cursor: pointer;
    }
    .btn-primary {
        background: linear-gradient(145deg, #22C55E, #15803D);
        color: white;
        box-shadow: 0 4px 12px rgba(34,197,94,0.25);
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(34,197,94,0.3);
    }
    .btn-secondary {
        background: #e2e8f0;
        color: #1f2937;
    }
    .btn-secondary:hover {
        background: #cbd5e1;
    }
    .btn-outline {
        background: transparent;
        color: #15803D;
        border: 2px solid #22C55E;
    }
    .btn-outline:hover {
        background: #f0fdf4;
    }
    .btn-group {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 12px;
    }

    /* Form */
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    @media (max-width: 640px) {
        .form-grid { grid-template-columns: 1fr; }
    }
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .form-group label {
        font-size: 13px;
        font-weight: 600;
        color: #334155;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .form-group label .required {
        color: #ef4444;
        font-weight: 700;
    }
    .form-group input,
    .form-group select {
        padding: 10px 14px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 15px;
        background: white;
        color: #1e293b;
        transition: border-color 0.2s, box-shadow 0.2s;
        width: 100%;
        box-sizing: border-box;
    }
    .form-group input:focus,
    .form-group select:focus {
        border-color: #22C55E;
        outline: none;
        box-shadow: 0 0 0 3px rgba(34,197,94,0.15);
    }
    .form-group input:disabled,
    .form-group select:disabled {
        background: #f1f5f9;
        color: #94a3b8;
        cursor: not-allowed;
    }
    .form-group small {
        font-size: 12px;
        color: #94a3b8;
        margin-top: 4px;
    }
    .form-group small i {
        margin-right: 4px;
    }

    /* Badges */
    .badge {
        display: inline-block;
        padding: 4px 14px;
        border-radius: 999px;
        font-weight: 600;
        font-size: 13px;
    }
    .badge-admin { background: #fce7f3; color: #9d174d; }
    .badge-manager { background: #dbeafe; color: #1e40af; }
    .badge-user { background: #dcfce7; color: #166534; }

    /* Navigation */
    .nav-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        padding: 10px 16px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        margin-bottom: 16px;
        border: 1px solid #dcfce7;
    }
    .nav-bar a {
        padding: 8px 16px;
        font-size: 15px;
        color: #4a5568;
        border-radius: 8px;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
    }
    .nav-bar a:hover {
        background: #f0fdf4;
        color: #22C55E;
    }
    .nav-bar a i {
        font-size: 16px;
        color: #22C55E;
    }
    .nav-bar .logout {
        color: #ef4444;
        margin-left: auto;
    }
    .nav-bar .logout i { color: #ef4444; }

    /* Message */
    .message {
        padding: 14px 18px;
        border-radius: 10px;
        margin-bottom: 16px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .message.success {
        background: #dcfce7;
        color: #166534;
        border-left: 5px solid #22C55E;
    }
    .message.error {
        background: #fef3c7;
        color: #92400e;
        border-left: 5px solid #f59e0b;
    }
    .message i { font-size: 20px; }

    /* Responsive */
    @media (max-width: 640px) {
        .card { padding: 14px 16px; }
        .nav-bar a { font-size: 13px; padding: 6px 12px; }
        .btn { font-size: 13px; padding: 8px 16px; }
        .card-title { font-size: 17px; }
        .header-grass h1 { font-size: 22px !important; }
        .header-grass p { font-size: 14px !important; }
        .form-group input, .form-group select { font-size: 14px; padding: 8px 12px; }
    }

    @keyframes sway {
        0%, 100% { transform: rotate(-2deg); }
        50% { transform: rotate(2deg); }
    }
</style>

<!-- ===== MAIN CONTENT ===== -->
<div style="width:100%;display:flex;flex-direction:column;background:#f0fdf4;">

    <!-- HEADER -->
    <div class="header-grass" style="background:linear-gradient(135deg,#22C55E,#15803D);border-radius:12px;padding:16px 20px;box-shadow:0 2px 10px rgba(34,197,94,0.25);margin-bottom:16px;position:relative;overflow:hidden;">
        <div style="position:absolute;top:-40px;right:-40px;width:120px;height:120px;background:rgba(255,255,255,0.05);border-radius:50%;pointer-events:none;"></div>
        <div style="position:absolute;bottom:-30px;left:-30px;width:80px;height:80px;background:rgba(255,255,255,0.05);border-radius:50%;pointer-events:none;"></div>
        <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:8px;position:relative;z-index:2;">
            <div style="display:flex;align-items:center;gap:12px;">
                <span style="font-size:38px;display:inline-block;animation:sway 2s ease-in-out infinite;">🐐</span>
                <div>
                    <h1 style="font-size:28px;font-weight:700;color:white;margin:0;display:flex;align-items:center;gap:10px;">
                        Update User
                        <span style="background:rgba(255,255,255,0.2);padding:2px 14px;border-radius:20px;font-size:14px;font-weight:500;color:white;text-transform:capitalize;"><?php echo htmlspecialchars($currentRole); ?></span>
                    </h1>
                    <p style="font-size:16px;color:rgba(255,255,255,0.8);margin:4px 0 0 0;">
                        <i class="fas fa-user-edit" style="font-size:14px;"></i> Edit user account details
                    </p>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <span style="background:rgba(255,255,255,0.15);color:white;padding:4px 16px;border-radius:20px;font-size:14px;font-weight:500;">
                    <i class="fas fa-calendar-alt"></i> <?php echo date('M d, Y'); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- NAVIGATION -->
    <nav class="nav-bar">
        <a href="<?php echo BASE_URL; ?>/app/users/dashboard.php">
            <i class="fas fa-arrow-left"></i> Back to Users
        </a>
        <a href="<?php echo BASE_URL; ?>/app/<?php echo $currentRole; ?>/dashboard.php">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="<?php echo BASE_URL; ?>/app/auth/signout.php" class="logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>

    <!-- MESSAGE -->
    <?php if ($message): ?>
        <div class="message <?php echo $success ? 'success' : 'error'; ?>">
            <i class="fas <?php echo $success ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- FORM CARD -->
    <div class="card">
        <div class="card-title">
            <i class="fas fa-user-cog"></i> Edit Profile
            <span class="badge badge-<?php echo $user['role']; ?>" style="font-size:12px;padding:2px 12px;">Current: <?php echo ucfirst($user['role']); ?></span>
        </div>

        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label for="full_name">Full Name <span class="required">*</span></label>
                    <input type="text" id="full_name" name="full_name" required
                           value="<?php echo htmlspecialchars($user['full_name']); ?>">
                </div>

                <div class="form-group">
                    <label for="username">Username <span class="required">*</span></label>
                    <input type="text" id="username" name="username" required
                           value="<?php echo htmlspecialchars($user['username']); ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email <span class="required">*</span></label>
                    <input type="email" id="email" name="email" required
                           value="<?php echo htmlspecialchars($user['email']); ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Leave empty to keep current">
                    <small><i class="fas fa-info-circle"></i> At least 6 characters if changing</small>
                </div>

                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" <?php echo $canChangeRole ? '' : 'disabled'; ?>>
                        <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User (Farmer)</option>
                        <option value="manager" <?php echo $user['role'] === 'manager' ? 'selected' : ''; ?>>Manager</option>
                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                    <?php if (!$canChangeRole): ?>
                        <input type="hidden" name="role" value="<?php echo htmlspecialchars($user['role']); ?>">
                        <small><i class="fas fa-lock"></i> <?php echo $isSelf ? 'You cannot change your own role.' : 'Only Admins can assign roles.'; ?></small>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="status">Account Status</label>
                    <select id="status" name="status" <?php echo $canChangeStatus ? '' : 'disabled'; ?>>
                        <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                    <?php if (!$canChangeStatus): ?>
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($user['status']); ?>">
                        <small><i class="fas fa-lock"></i> <?php echo $isSelf ? 'You cannot deactivate your own account.' : 'You do not have permission to change this account\'s status.'; ?></small>
                    <?php endif; ?>
                </div>
            </div> <!-- end form-grid -->

            <div class="btn-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update User
                </button>
                <a href="<?php echo BASE_URL; ?>/app/users/dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>

</div>

<?php renderFooter(); ?>