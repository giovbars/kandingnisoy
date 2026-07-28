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

// Get user details BEFORE deletion
$stmt = $pdo->prepare("SELECT id, full_name, username, email, role FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Enforce role-based access: only Admin/Manager can delete, Manager can
// never delete Admin or Manager accounts, and nobody can delete themselves.
if ($user && !canDeleteTarget($currentRole, $currentUserId, $user['role'], $user['id'])) {
    die("Access denied. You do not have permission to delete this user.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($user && canDeleteTarget($currentRole, $currentUserId, $user['role'], $user['id'])) {
        try {
            // Log deletion BEFORE actually deleting (so we have user info)
            logActivity($pdo, $_SESSION['user_id'], $_SESSION['email'], 'user_deleted', 'success');
            logActivity($pdo, $userId, $user['email'], 'account_deleted', 'success');

            // Now delete the user
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);

            if ($stmt->rowCount() > 0) {
                redirect('/app/users/dashboard.php');
            } else {
                $message = "User not found.";
            }
        } catch(PDOException $e) {
            $message = "Error deleting user: " . $e->getMessage();

            // Log failed deletion
            logActivity($pdo, $_SESSION['user_id'], $_SESSION['email'], 'user_deleted', 'failed');
        }
    } else {
        $message = "You do not have permission to delete this user.";
    }
}

renderHeader('Delete User');
?>

<div class="nav">
    <a href="<?php echo BASE_URL; ?>/app/users/dashboard.php">Back to Users</a>
    <a href="<?php echo BASE_URL; ?>/app/auth/signout.php">Logout</a>
</div>

<h1>Delete User</h1>

<?php if ($message): ?>
    <div class="error"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($user): ?>
    <div class="card">
        <p>Are you sure you want to delete this user? This action cannot be undone.</p>

        <div class="info-box" style="background: #ffebee; border-left-color: #dc3545;">
            <strong>User Details:</strong><br>
            Full Name: <?php echo htmlspecialchars($user['full_name']); ?><br>
            Username: <?php echo htmlspecialchars($user['username']); ?><br>
            Email: <?php echo htmlspecialchars($user['email']); ?><br>
            Role: <span class="badge badge-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span>
        </div>

        <form method="POST" style="margin-top: 15px;">
            <button type="submit" style="background: #dc3545;">Delete User</button>
            <a href="<?php echo BASE_URL; ?>/app/users/dashboard.php">
                <button type="button" style="background: #757575;">Cancel</button>
            </a>
        </form>
    </div>
<?php else: ?>
    <p>User not found.</p>
<?php endif; ?>

<?php renderFooter(); ?>
