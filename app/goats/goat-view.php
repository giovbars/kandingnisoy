<?php
require_once '../../config/config.php';
require_once '../../config/functions.php';
require_once '../../includes/goat-logger.php';
require_once '../../includes/goat-functions.php';
requireLogin();

$currentRole = $_SESSION['role'];
$currentUserId = $_SESSION['user_id'];

// Lookup by primary key `id`
$goatPk = (int) trim($_GET['id'] ?? '');

$stmt = $pdo->prepare("
    SELECT g.*, u.full_name AS owner_name, u.username AS owner_username, u.email AS owner_email
    FROM goats g
    JOIN users u ON u.id = g.user_id
    WHERE g.id = ?
");
$stmt->execute([$goatPk]);
$goat = $stmt->fetch(PDO::FETCH_ASSOC);

$title = 'Goat Details';
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
    .badge-success { background: #dcfce7; color: #166534; }
    .badge-danger  { background: #fef3c7; color: #92400e; }
    .badge-warning { background: #fef9c3; color: #854d0e; }
    .badge-secondary { background: #f3f4f6; color: #6b7280; }

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
        width: 220px;
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

    /* Responsive */
    @media (max-width: 640px) {
        .card { padding: 14px 16px; }
        .details-table th { width: 130px; font-size: 13px; padding: 10px 10px 10px 0; }
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
                        Goat Details
                        <?php if ($goat): ?>
                        <span style="background:rgba(255,255,255,0.2);padding:2px 14px;border-radius:20px;font-size:14px;font-weight:500;color:white;"><?php echo htmlspecialchars($goat['goat_id']); ?></span>
                        <?php endif; ?>
                    </h1>
                    <p style="font-size:16px;color:rgba(255,255,255,0.8);margin:4px 0 0 0;">
                        <i class="fas fa-info-circle" style="font-size:14px;"></i> Complete information about this goat
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
        <a href="<?php echo BASE_URL; ?>/app/goats/dashboard.php">
            <i class="fas fa-arrow-left"></i> Back to Inventory
        </a>
        <a href="<?php echo BASE_URL; ?>/app/<?php echo $currentRole; ?>/dashboard.php">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="<?php echo BASE_URL; ?>/app/auth/signout.php" class="logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>

    <!-- CARD -->
    <div class="card">
        <div class="card-title">
            <i class="fas fa-paw"></i> Goat Information
        </div>

        <?php if (!$goat): ?>
            <div class="error">The requested goat record could not be found.</div>
        <?php elseif (!canAccessGoat($currentRole, $currentUserId, $goat['user_id'])): ?>
            <div class="error">You are not authorized to access this goat record.</div>
        <?php else: ?>
            <?php
            // Log that this goat record was viewed (audit trail)
            logGoatActivity($pdo, $currentUserId, $_SESSION['email'], 'goat_viewed', 'success', $goat['goat_id']);
            ?>

            <table class="details-table">
                <tr>
                    <th><i class="fas fa-fingerprint"></i> Goat ID</th>
                    <td><?php echo htmlspecialchars($goat['goat_id']); ?></td>
                </tr>
                <?php if ($currentRole !== 'user'): ?>
                <tr>
                    <th><i class="fas fa-user"></i> Owner (Farmer)</th>
                    <td><?php echo htmlspecialchars($goat['owner_name']); ?> (<?php echo htmlspecialchars($goat['owner_username']); ?>)</td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th><i class="fas fa-tag"></i> Goat Name / Tag Number</th>
                    <td><?php echo htmlspecialchars($goat['goat_name']); ?></td>
                </tr>
                <tr>
                    <th><i class="fas fa-qrcode"></i> Ear Tag Number</th>
                    <td><?php echo $goat['tag_number'] ? htmlspecialchars($goat['tag_number']) : '—'; ?></td>
                </tr>
                <tr>
                    <th><i class="fas fa-venus-mars"></i> Sex</th>
                    <td><?php echo ucfirst($goat['sex']); ?></td>
                </tr>
                <tr>
                    <th><i class="fas fa-dna"></i> Breed</th>
                    <td><?php echo htmlspecialchars($goat['breed']); ?></td>
                </tr>
                <tr>
                    <th><i class="fas fa-calendar-alt"></i> Date of Birth</th>
                    <td><?php echo $goat['date_of_birth'] ? date('F j, Y', strtotime($goat['date_of_birth'])) : '—'; ?></td>
                </tr>
                <tr>
                    <th><i class="fas fa-palette"></i> Color</th>
                    <td><?php echo $goat['color'] ? htmlspecialchars($goat['color']) : '—'; ?></td>
                </tr>
                <tr>
                    <th><i class="fas fa-pen-fancy"></i> Identifying Characteristics</th>
                    <td><?php echo $goat['identifying_characteristics'] ? nl2br(htmlspecialchars($goat['identifying_characteristics'])) : '—'; ?></td>
                </tr>
                <tr>
                    <th><i class="fas fa-clock"></i> Date Acquired</th>
                    <td><?php echo $goat['date_acquired'] ? date('F j, Y', strtotime($goat['date_acquired'])) : '—'; ?></td>
                </tr>
                <tr>
                    <th><i class="fas fa-heartbeat"></i> Health Status</th>
                    <td><span class="badge <?php echo healthStatusBadgeClass($goat['health_status']); ?>"><?php echo healthStatusLabel($goat['health_status']); ?></span></td>
                </tr>
                <tr>
                    <th><i class="fas fa-circle"></i> Goat Status</th>
                    <td><span class="badge <?php echo goatStatusBadgeClass($goat['status']); ?>"><?php echo goatStatusLabel($goat['status']); ?></span></td>
                </tr>
                <tr>
                    <th><i class="fas fa-edit"></i> Date Registered</th>
                    <td><?php echo date('F j, Y, g:i a', strtotime($goat['date_registered'])); ?></td>
                </tr>
                <tr>
                    <th><i class="fas fa-plus-circle"></i> Created</th>
                    <td><?php echo date('F j, Y, g:i a', strtotime($goat['created_at'])); ?></td>
                </tr>
                <tr>
                    <th><i class="fas fa-sync-alt"></i> Last Updated</th>
                    <td><?php echo date('F j, Y, g:i a', strtotime($goat['updated_at'])); ?></td>
                </tr>
            </table>

            <!-- Action Buttons -->
            <div class="btn-group">
                <?php if (canEditGoat($currentRole, $currentUserId, $goat['user_id']) || canUpdateHealthStatus($currentRole, $currentUserId, $goat['user_id'])): ?>
                    <a href="goat-update.php?id=<?php echo urlencode($goat['id']); ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Goat
                    </a>
                <?php endif; ?>
                <a href="goat-history.php?id=<?php echo urlencode($goat['id']); ?>" class="btn btn-secondary">
                    <i class="fas fa-history"></i> View History
                </a>
                <a href="dashboard.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>

        <?php endif; ?>
    </div>

</div>

<?php renderFooter(); ?>