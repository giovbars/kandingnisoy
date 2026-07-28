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

renderHeader('Update User');
?>

<div class="nav">
    <a href="<?php echo BASE_URL; ?>/app/users/dashboard.php">Back to Users</a>
    <a href="<?php echo BASE_URL; ?>/app/auth/signout.php">Logout</a>
</div>

<h1>Update User</h1>

<?php if ($message): ?>
    <div class="<?php echo $success ? 'success' : 'error'; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="card">
    <form method="POST">
        <div class="form-group">
            <label for="full_name">Full Name:</label>
            <input type="text" id="full_name" name="full_name" required
                   value="<?php echo htmlspecialchars($user['full_name']); ?>">
        </div>

        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required
                   value="<?php echo htmlspecialchars($user['username']); ?>">
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required
                   value="<?php echo htmlspecialchars($user['email']); ?>">
        </div>

        <div class="form-group">
            <label for="password">Password (leave empty to keep current):</label>
            <input type="password" id="password" name="password" placeholder="At least 6 characters">
        </div>

        <div class="form-group">
            <label for="role">Role:</label>
            <select id="role" name="role" <?php echo $canChangeRole ? '' : 'disabled'; ?>>
                <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User (Farmer)</option>
                <option value="manager" <?php echo $user['role'] === 'manager' ? 'selected' : ''; ?>>Manager</option>
                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
            </select>
            <?php if (!$canChangeRole): ?>
                <input type="hidden" name="role" value="<?php echo htmlspecialchars($user['role']); ?>">
                <small style="color: #666;">
                    <?php echo $isSelf ? 'You cannot change your own role.' : 'Only Admins can assign roles.'; ?>
                </small>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="status">Account Status:</label>
            <select id="status" name="status" <?php echo $canChangeStatus ? '' : 'disabled'; ?>>
                <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select>
            <?php if (!$canChangeStatus): ?>
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($user['status']); ?>">
                <small style="color: #666;">
                    <?php echo $isSelf ? 'You cannot deactivate your own account.' : 'You do not have permission to change this account\'s status.'; ?>
                </small>
            <?php endif; ?>
        </div>

        <button type="submit">Update User</button>
    </form>
</div>

<?php renderFooter(); ?>
