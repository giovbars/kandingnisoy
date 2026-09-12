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

$title = 'Delete User';
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
    .btn-danger {
        background: #ef4444;
        color: white;
        box-shadow: 0 4px 12px rgba(239,68,68,0.25);
    }
    .btn-danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(239,68,68,0.3);
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
        margin-top: 20px;
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

    /* Info/Warning box */
    .warning-box {
        background: #fef3c7;
        border-left: 5px solid #f59e0b;
        padding: 14px 18px;
        border-radius: 10px;
        margin-bottom: 16px;
        color: #92400e;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .warning-box i { font-size: 20px; color: #f59e0b; }

    .user-details-box {
        background: #f0fdf4;
        border: 1px solid #dcfce7;
        border-radius: 8px;
        padding: 14px 18px;
        margin-bottom: 16px;
    }
    .user-details-box p {
        margin: 4px 0;
        font-size: 15px;
    }
    .user-details-box strong {
        color: #1f2937;
    }

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
                        Delete User
                        <span style="background:rgba(255,255,255,0.2);padding:2px 14px;border-radius:20px;font-size:14px;font-weight:500;color:white;text-transform:capitalize;"><?php echo htmlspecialchars($currentRole); ?></span>
                    </h1>
                    <p style="font-size:16px;color:rgba(255,255,255,0.8);margin:4px 0 0 0;">
                        <i class="fas fa-exclamation-triangle" style="font-size:14px;"></i> Confirm deletion of user account
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
        <div class="message error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- MAIN CARD -->
    <?php if ($user): ?>
    <div class="card">
        <div class="card-title">
            <i class="fas fa-user-minus" style="color:#ef4444;"></i> Confirm Deletion
        </div>

        <div class="warning-box">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong>Warning:</strong> This action <strong>cannot be undone</strong>. All data associated with this user will be permanently removed.
            </div>
        </div>

        <div class="user-details-box">
            <p><strong>Full Name:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
            <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>Role:</strong> <span class="badge badge-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span></p>
        </div>

        <form method="POST">
            <div class="btn-group">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash-alt"></i> Delete User
                </button>
                <a href="<?php echo BASE_URL; ?>/app/users/dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="warning-box" style="background:#f0fdf4;border-left-color:#94a3b8;">
            <i class="fas fa-user-slash" style="color:#94a3b8;"></i>
            <div>User not found.</div>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php renderFooter(); ?>