<?php
require_once '../../config/config.php';
require_once '../../config/functions.php';
require_once '../../includes/activity-logger.php';
requireLogin();

$currentRole = $_SESSION['role'];

// Only admin and manager can create users
if ($currentRole !== 'admin' && $currentRole !== 'manager') {
    die("Access denied. Only administrators and managers can create users.");
}

$message = '';
$success = false;
$verificationLink = '';

// Preserve submitted values on validation failure so the form isn't wiped
$full_name = '';
$username = '';
$email = '';
$role = 'user';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
    $send_email = isset($_POST['send_verification_email']) ? true : false;

    // Role restrictions based on current user's role.
    // Managers can only ever create regular (Farmer) accounts and can
    // never create Admin or Manager accounts.
    if ($currentRole === 'manager') {
        $role = 'user';
    } elseif ($currentRole === 'admin') {
        if (!in_array($role, ['admin', 'manager', 'user'])) {
            $role = 'user';
        }
    }

    // Basic validation
    $errors = [];
    if ($full_name === '') $errors[] = "Full name is required.";
    if ($username === '' || !preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $username)) {
        $errors[] = "Username must be 3-50 characters (letters, numbers, dot, dash, underscore only).";
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email address is required.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    if (empty($errors)) {
        try {
            // Email verification defaults
            $email_verified = 1;
            $verification_token = null;
            $verification_expires = null;

            if ($send_email) {
                $email_verified = 0;
                $verification_token = bin2hex(random_bytes(32));
                $verification_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
            }

            // Insert user into database
            $stmt = $pdo->prepare("
                INSERT INTO users
                    (full_name, username, email, password, role, status, verification_token, is_verified, email_verification_expires, created_at)
                VALUES
                    (:full_name, :username, :email, :password, :role, :status, :token, :verified, :expires, NOW())
            ");

            $stmt->execute([
                ':full_name' => $full_name,
                ':username'  => $username,
                ':email'     => $email,
                ':password'  => password_hash($password, PASSWORD_DEFAULT),
                ':role'      => $role,
                ':status'    => $status,
                ':token'     => $verification_token,
                ':verified'  => $email_verified,
                ':expires'   => $verification_expires
            ]);

            $new_user_id = $pdo->lastInsertId();

            // Log user creation
            logActivity($pdo, $_SESSION['user_id'], $_SESSION['email'], 'user_created', 'success');

            // Log for the new user as well
            logActivity($pdo, $new_user_id, $email, 'account_created', 'success');

            // Send verification email if requested
            if ($send_email) {
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
                            <p>Hello {$full_name},</p>
                            <p>An account was created for this email. Please verify your email address.</p>
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

                // Set headers for HTML email
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= "From: " . MAIL_FROM . "\r\n";

                if (mail($email, $email_subject, $email_body, $headers)) {
                    $message = "User created successfully! Verification email sent.";
                    logActivity($pdo, $new_user_id, $email, 'verification_email_sent', 'success');
                } else {
                    $message = "User created successfully! Verification email failed to send.";
                    logActivity($pdo, $new_user_id, $email, 'verification_email_sent', 'failed');
                }
            } else {
                $message = "User created successfully! (Email verification skipped)";
            }

            $success = true;

            // Clear the form on success
            $full_name = '';
            $username = '';
            $email = '';
            $role = 'user';

        } catch(PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = "Error creating user: that email address or username is already taken.";
            } else {
                $message = "Error creating user: " . $e->getMessage();
            }
            logActivity($pdo, $_SESSION['user_id'], $_SESSION['email'], 'user_created', 'failed');
        }
    } else {
        $message = implode(' ', $errors);
    }
}

$title = 'Create User';
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
    .btn-secondary {
        background: #e2e8f0;
        color: #1f2937;
    }
    .btn-secondary:hover { background: #cbd5e1; }

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

    /* ---------- Form styles ---------- */
    .form-group {
        margin-bottom: 16px;
    }
    .form-group label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 4px;
    }
    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group input[type="password"],
    .form-group select {
        width: 100%;
        padding: 8px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        transition: 0.2s;
        background: white;
    }
    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #22C55E;
        box-shadow: 0 0 0 3px rgba(34,197,94,0.2);
    }
    .form-group small {
        display: block;
        font-size: 12px;
        color: #6b7280;
        margin-top: 4px;
    }
    .form-group .checkbox-label {
        display: flex;
        align-items: center;
        cursor: pointer;
        font-weight: 400;
    }
    .form-group .checkbox-label input[type="checkbox"] {
        width: auto;
        margin-right: 10px;
        accent-color: #22C55E;
    }
    .form-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 6px;
    }

    .message-box {
        padding: 12px 16px;
        border-radius: 10px;
        margin-bottom: 16px;
        font-size: 14px;
    }
    .message-box.success {
        background: #dcfce7;
        color: #166534;
        border-left: 5px solid #22C55E;
    }
    .message-box.error {
        background: #fef3c7;
        color: #92400e;
        border-left: 5px solid #f59e0b;
    }
    .message-box.info {
        background: #dbeafe;
        color: #1e40af;
        border-left: 5px solid #3b82f6;
    }

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
    .info-box a {
        color: #16a34a;
        text-decoration: none;
        font-weight: 600;
    }
    .info-box a:hover {
        text-decoration: underline;
    }

    /* ---------- Animation ---------- */
    @keyframes sway { 0%,100% { transform: rotate(-2deg); } 50% { transform: rotate(2deg); } }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    .card, .header-grass, .dash-nav {
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
                        Create New User
                        <span class="header-role-badge"><?php echo ucfirst($currentRole); ?></span>
                    </h1>
                    <p>
                        <i class="fas fa-leaf" style="font-size:12px;"></i> Add a new user to the system
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
        <a href="<?php echo BASE_URL; ?>/app/users/dashboard.php" class="dash-nav-link">
            <i class="fas fa-tachometer-alt"></i> User Management
        </a>
        <a href="<?php echo BASE_URL; ?>/app/users/user-create.php" class="dash-nav-link active">
            <i class="fas fa-user-plus"></i> Create User
        </a>
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

    <!-- ===== FORM CARD ===== -->
    <div class="card" style="margin-bottom:16px;">
        <div class="card-title">
            <i class="fas fa-user-plus" style="color:#22C55E;"></i> User Details
            <span class="badge-sm" style="background:#dcfce7;color:#166534;">
                <?php echo ucfirst($currentRole); ?> access
            </span>
        </div>

        <?php if ($currentRole === 'manager'): ?>
            <div class="info-box">
                <strong><i class="fas fa-info-circle"></i> Manager Access:</strong> You can only create regular <strong>User (Farmer)</strong> accounts. The role selection is disabled.
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="message-box <?php echo $success ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($verificationLink): ?>
            <div class="message-box info">
                <strong><i class="fas fa-envelope"></i> Email Verification Link (for testing):</strong><br>
                <a href="<?php echo $verificationLink; ?>" target="_blank"><?php echo $verificationLink; ?></a>
                <p style="margin-top: 8px; font-size: 13px; color: #4b5563;">
                    In production, this link is sent via email. The email has been queued.
                </p>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="full_name">Full Name <span style="color:#ef4444;">*</span></label>
                <input type="text" id="full_name" name="full_name" required
                       value="<?php echo htmlspecialchars($full_name); ?>" placeholder="Juan Dela Cruz">
            </div>

            <div class="form-group">
                <label for="username">Username <span style="color:#ef4444;">*</span></label>
                <input type="text" id="username" name="username" required
                       value="<?php echo htmlspecialchars($username); ?>" placeholder="jdelacruz">
                <small>3-50 characters, letters, numbers, dot, dash, underscore only.</small>
            </div>

            <div class="form-group">
                <label for="email">Email Address <span style="color:#ef4444;">*</span></label>
                <input type="email" id="email" name="email" required
                       value="<?php echo htmlspecialchars($email); ?>" placeholder="user@example.com">
            </div>

            <div class="form-group">
                <label for="password">Password <span style="color:#ef4444;">*</span></label>
                <input type="password" id="password" name="password" required placeholder="At least 6 characters">
            </div>

            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" <?php echo $currentRole === 'manager' ? 'disabled' : ''; ?>>
                    <option value="user">User (Farmer)</option>
                    <?php if ($currentRole === 'admin'): ?>
                        <option value="manager" <?php echo $role === 'manager' ? 'selected' : ''; ?>>Manager</option>
                        <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <?php endif; ?>
                </select>
                <?php if ($currentRole === 'manager'): ?>
                    <input type="hidden" name="role" value="user">
                    <small>Managers can only create regular User (Farmer) accounts.</small>
                <?php else: ?>
                    <small>Select the role for this user.</small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="status">Account Status</label>
                <select id="status" name="status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <small>If inactive, the user cannot log in.</small>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="send_verification_email" value="1" checked>
                    <span>Send email verification (recommended)</span>
                </label>
                <small>If unchecked, the user will be verified immediately without email confirmation.</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Create User
                </button>
                <a href="<?php echo BASE_URL; ?>/app/users/dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Cancel
                </a>
            </div>
        </form>
    </div>

</div>

<?php renderFooter(); ?>