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

// Get user details up front so we can check visibility permissions
$stmt = $pdo->prepare("SELECT id, full_name, username, email, role, status, is_verified, verification_token, email_verification_expires, created_at, updated_at FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && !canViewTarget($currentRole, $currentUserId, $user['role'], $user['id'])) {
    die("Access denied. You do not have permission to view this user.");
}

// Handle resend verification email (Admin/Manager only, for accounts they can manage)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_verification'])) {
    if (!$user || !canManageTarget($currentRole, $currentUserId, $user['role'], $user['id'])) {
        die("Access denied.");
    }

    $verification_token = bin2hex(random_bytes(32));
    $verification_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

    try {
        // Update user with new token and expiry
        $stmt = $pdo->prepare("
            UPDATE users 
            SET verification_token = :token, 
                email_verification_expires = :expires 
            WHERE id = :id
        ");
        $stmt->execute([
            ':token' => $verification_token,
            ':expires' => $verification_expires,
            ':id' => $userId
        ]);

        $userEmail = $user['email'];

        if ($userEmail) {
            $verificationLink = BASE_URL . "/app/auth/verify-email.php?token=" . $verification_token;

            $email_subject = "Verify Your Email Address";

            $email_body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #1976d2; color: white; padding: 20px; text-align: center; }
                    .content { background: #f9f9f9; padding: 30px; }
                    .button { display: inline-block; padding: 12px 30px; background: #4caf50; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                    .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Email Verification</h2>
                    </div>
                    <div class='content'>
                        <p>Hello,</p>
                        <p>You requested a new verification link. Please verify your email address.</p>
                        <p style='text-align: center;'>
                            <a href='{$verificationLink}' class='button'>Verify Email</a>
                        </p>
                        <p>If the button doesn't work, copy and paste this link:</p>
                        <p style='word-break: break-all; color: #1976d2;'>{$verificationLink}</p>
                        <p>This link expires in 24 hours.</p>
                    </div>
                    <div class='footer'>
                        <p>&copy; " . date('Y') . " Precision Livestock Farming - User Management</p>
                    </div>
                </div>
            </body>
            </html>
            ";

            // Send email using PHP mail()
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: " . MAIL_FROM . "\r\n";

            if (mail($userEmail, $email_subject, $email_body, $headers)) {
                $message = "Verification email has been resent successfully! Link: <a href='$verificationLink' target='_blank'>$verificationLink</a>";
                $success = true;

                // Log successful resend
                logActivity($pdo, $_SESSION['user_id'], $_SESSION['email'], 'verification_email_resent', 'success');
                logActivity($pdo, $userId, $userEmail, 'verification_email_received', 'success');
            } else {
                $message = "Verification email failed to send. Please try again later.";
                $success = false;

                // Log failed resend
                logActivity($pdo, $_SESSION['user_id'], $_SESSION['email'], 'verification_email_resent', 'failed');
            }
        }

        // Refresh user data
        $stmt = $pdo->prepare("SELECT id, full_name, username, email, role, status, is_verified, verification_token, email_verification_expires, created_at, updated_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch(PDOException $e) {
        $message = "Error resending verification: " . $e->getMessage();
        $success = false;

        // Log error
        logActivity($pdo, $_SESSION['user_id'], $_SESSION['email'], 'verification_email_resent', 'failed');
    }
}

// Log that this user profile was viewed
if ($user) {
    logActivity($pdo, $_SESSION['user_id'], $_SESSION['email'], 'user_viewed', 'success');
}

// Check if verification has expired
$verificationExpired = false;
if ($user && !$user['is_verified'] && $user['email_verification_expires']) {
    $verificationExpired = strtotime($user['email_verification_expires']) < time();
}

$canManage = $user ? canManageTarget($currentRole, $currentUserId, $user['role'], $user['id']) : false;

$title = 'View User';
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
    .btn-warning {
        background: #f59e0b;
        color: white;
        box-shadow: 0 4px 12px rgba(245,158,11,0.25);
    }
    .btn-warning:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(245,158,11,0.3);
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
    .badge-active { background: #dcfce7; color: #166534; }
    .badge-inactive { background: #f3f4f6; color: #6b7280; }
    .badge-verified { background: #dcfce7; color: #166534; }
    .badge-unverified { background: #fef3c7; color: #92400e; }

    /* Details table */
    .details-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 15px;
    }
    .details-table tr {
        border-bottom: 1px solid #f0fdf4;
    }
    .details-table tr:last-child {
        border-bottom: none;
    }
    .details-table th {
        text-align: left;
        padding: 12px 16px 12px 0;
        font-weight: 600;
        color: #475569;
        width: 200px;
        background: transparent;
    }
    .details-table td {
        padding: 12px 0;
        color: #1e293b;
    }
    .details-table th i {
        margin-right: 6px;
        color: #22C55E;
        width: 16px;
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

    /* Info box for verification */
    .info-box {
        background: #f0fdf4;
        border-left: 5px solid #22C55E;
        padding: 14px 18px;
        border-radius: 10px;
        margin-top: 20px;
        color: #166534;
    }
    .info-box.warning {
        background: #fef3c7;
        border-left-color: #f59e0b;
        color: #92400e;
    }
    .info-box.danger {
        background: #fef3c7;
        border-left-color: #ef4444;
        color: #92400e;
    }
    .info-box i {
        font-size: 18px;
        margin-right: 8px;
    }
    .info-box form {
        margin-top: 10px;
    }

    /* Responsive */
    @media (max-width: 640px) {
        .card { padding: 14px 16px; }
        .details-table th { width: 120px; font-size: 13px; padding: 10px 10px 10px 0; }
        .details-table td { font-size: 13px; padding: 10px 0; }
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
                        User Details
                        <?php if ($user): ?>
                        <span style="background:rgba(255,255,255,0.2);padding:2px 14px;border-radius:20px;font-size:14px;font-weight:500;color:white;"><?php echo htmlspecialchars($user['username']); ?></span>
                        <?php endif; ?>
                    </h1>
                    <p style="font-size:16px;color:rgba(255,255,255,0.8);margin:4px 0 0 0;">
                        <i class="fas fa-user" style="font-size:14px;"></i> View user profile and manage verification
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
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <!-- CARD -->
    <div class="card">
        <div class="card-title">
            <i class="fas fa-id-card"></i> Profile Information
        </div>

        <?php if ($user): ?>
            <table class="details-table">
                <tr>
                    <th><i class="fas fa-fingerprint"></i> ID</th>
                    <td><?php echo $user['id']; ?></td>
                </tr>
                <tr>
                    <th><i class="fas fa-user"></i> Full Name</th>
                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                </tr>
                <tr>
                    <th><i class="fas fa-user-tag"></i> Username</th>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                </tr>
                <tr>
                    <th><i class="fas fa-envelope"></i> Email</th>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                </tr>
                <tr>
                    <th><i class="fas fa-shield-alt"></i> Role</th>
                    <td><span class="badge badge-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span></td>
                </tr>
                <tr>
                    <th><i class="fas fa-circle"></i> Account Status</th>
                    <td><span class="badge badge-<?php echo $user['status'] === 'active' ? 'active' : 'inactive'; ?>"><?php echo ucfirst($user['status']); ?></span></td>
                </tr>
                <tr>
                    <th><i class="fas fa-check-circle"></i> Verified</th>
                    <td>
                        <span class="badge badge-<?php echo $user['is_verified'] ? 'verified' : 'unverified'; ?>">
                            <?php echo $user['is_verified'] ? 'Yes' : 'No'; ?>
                        </span>
                        <?php if (!$user['is_verified'] && $verificationExpired): ?>
                            <span class="badge" style="background:#fecaca;color:#dc2626;margin-left:8px;">Expired</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if (!$user['is_verified'] && $user['email_verification_expires']): ?>
                <tr>
                    <th><i class="fas fa-clock"></i> Verification Expires</th>
                    <td>
                        <?php 
                        $expiresTime = strtotime($user['email_verification_expires']);
                        echo date('F j, Y, g:i a', $expiresTime);

                        if ($verificationExpired) {
                            echo ' <span style="color: #dc2626;font-weight:600;">(Expired)</span>';
                        } else {
                            $timeLeft = $expiresTime - time();
                            $hoursLeft = floor($timeLeft / 3600);
                            echo ' <span style="color: #f59e0b;">(' . $hoursLeft . ' hours remaining)</span>';
                        }
                        ?>
                    </td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th><i class="fas fa-calendar-plus"></i> Created</th>
                    <td><?php echo date('F j, Y, g:i a', strtotime($user['created_at'])); ?></td>
                </tr>
                <tr>
                    <th><i class="fas fa-calendar-alt"></i> Last Updated</th>
                    <td><?php echo date('F j, Y, g:i a', strtotime($user['updated_at'])); ?></td>
                </tr>
            </table>

            <!-- Resend verification (if user can manage and not verified) -->
            <?php if ($canManage && !$user['is_verified']): ?>
                <div class="info-box <?php echo $verificationExpired ? 'danger' : 'warning'; ?>">
                    <i class="fas <?php echo $verificationExpired ? 'fa-exclamation-circle' : 'fa-envelope'; ?>"></i>
                    <strong><?php echo $verificationExpired ? '⚠️ Verification Link Expired' : '📧 Email Not Verified'; ?></strong><br>
                    <?php if ($verificationExpired): ?>
                        This user's verification link has expired. Click the button below to send a new verification email.
                    <?php else: ?>
                        This user has not verified their email address yet. You can resend the verification email if needed.
                    <?php endif; ?>

                    <form method="POST">
                        <button type="submit" name="resend_verification" class="btn <?php echo $verificationExpired ? 'btn-danger' : 'btn-warning'; ?>">
                            <i class="fas fa-paper-plane"></i> Resend Verification Email
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="btn-group">
                <?php if ($canManage): ?>
                    <a href="user-update.php?user_id=<?php echo $user['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit User
                    </a>
                <?php endif; ?>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>

        <?php else: ?>
            <div class="message error">
                <i class="fas fa-exclamation-triangle"></i> User not found.
            </div>
        <?php endif; ?>
    </div>

</div>

<?php renderFooter(); ?>