<?php
require_once '../../config/config.php';
require_once '../../config/functions.php';
require_once '../../includes/goat-functions.php';
requireLogin();

$currentRole = $_SESSION['role'];
$currentUserId = $_SESSION['user_id'];

// Lookup by primary key `id`
$goatPk = (int) trim($_GET['id'] ?? '');

$stmt = $pdo->prepare("SELECT * FROM goats WHERE id = ?");
$stmt->execute([$goatPk]);
$goat = $stmt->fetch(PDO::FETCH_ASSOC);

$history = [];
if ($goat && canAccessGoat($currentRole, $currentUserId, $goat['user_id'])) {
    // ⚠️ TODO/VERIFY: this still joins activity_logs on the goat_id
    // STRING code (e.g. "G-0002"), not the unique PK. If activity_logs
    // does not also scope by owner, this could show history entries
    // belonging to a DIFFERENT farmer's goat that happens to share the
    // same goat_id code. Confirm the activity_logs schema before
    // trusting this in production - ideally log goat PK id instead of
    // (or in addition to) the goat_id string.
    $stmt = $pdo->prepare("
        SELECT al.*, u.full_name AS actor_name, u.role AS actor_role
        FROM activity_logs al
        LEFT JOIN users u ON u.id = al.user_id
        WHERE al.goat_id = ?
        ORDER BY al.created_at DESC
    ");
    $stmt->execute([$goat['goat_id']]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function friendlyGoatAction($action) {
    $map = [
        'goat_registered' => 'Goat Registered',
        'goat_registration_rejected_duplicate' => 'Registration Rejected (Duplicate Goat ID)',
        'goat_viewed' => 'Record Viewed',
        'goat_updated' => 'Information Updated',
        'goat_health_status_changed' => 'Health Status Changed',
        'goat_status_changed' => 'Goat Status Changed',
        'goat_marked_sold' => 'Marked as Sold',
        'goat_marked_deceased' => 'Marked as Deceased',
        'goat_ownership_corrected' => 'Ownership Corrected (Administrative Action)',
    ];
    return $map[$action] ?? ucwords(str_replace('_', ' ', $action));
}

$title = 'Goat History';
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

    /* Table */
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
        .nav-bar a { font-size: 13px; padding: 6px 12px; }
        .btn { font-size: 13px; padding: 8px 16px; }
        .card-title { font-size: 17px; }
        .header-grass h1 { font-size: 22px !important; }
        .header-grass p { font-size: 14px !important; }
        .table-wrapper th, .table-wrapper td { padding: 8px 10px; font-size: 13px; }
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
                        Goat History
                        <?php if ($goat): ?>
                        <span style="background:rgba(255,255,255,0.2);padding:2px 14px;border-radius:20px;font-size:14px;font-weight:500;color:white;"><?php echo htmlspecialchars($goat['goat_id']); ?></span>
                        <?php endif; ?>
                    </h1>
                    <p style="font-size:16px;color:rgba(255,255,255,0.8);margin:4px 0 0 0;">
                        <i class="fas fa-history" style="font-size:14px;"></i> Audit trail for this goat
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
        <a href="<?php echo BASE_URL; ?>/app/goats/goat-view.php?id=<?php echo urlencode($goatPk); ?>">
            <i class="fas fa-arrow-left"></i> Back to Details
        </a>
        <a href="<?php echo BASE_URL; ?>/app/goats/dashboard.php">
            <i class="fas fa-goat"></i> Inventory
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
            <i class="fas fa-clock"></i> Activity Log
            <?php if ($goat): ?>
            <span class="badge badge-secondary" style="background:#e2e8f0;color:#475569;font-size:12px;padding:2px 12px;">
                <?php echo count($history); ?> entries
            </span>
            <?php endif; ?>
        </div>

        <?php if (!$goat): ?>
            <div class="error">The requested goat record could not be found.</div>
        <?php elseif (!canAccessGoat($currentRole, $currentUserId, $goat['user_id'])): ?>
            <div class="error">You are not authorized to access this goat record.</div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Date/Time</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>Performed By</th>
                            <th>Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $entry): ?>
                            <?php $details = $entry['details'] ? json_decode($entry['details'], true) : null; ?>
                            <tr>
                                <td><?php echo date('Y-m-d g:i a', strtotime($entry['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars(friendlyGoatAction($entry['action'])); ?></td>
                                <td>
                                    <?php if ($details && is_array($details)): ?>
                                        <?php foreach ($details as $k => $v): ?>
                                            <div><small><strong><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $k))); ?>:</strong> <?php echo htmlspecialchars(is_scalar($v) ? (string)$v : json_encode($v)); ?></small></div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($entry['actor_name'] ?? $entry['email'] ?? 'Unknown'); ?></td>
                                <td><span class="badge <?php echo $entry['status'] === 'success' ? 'badge-success' : 'badge-danger'; ?>"><?php echo ucfirst($entry['status']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (empty($history)): ?>
                <div style="text-align:center;padding:40px 20px;color:#a0aec0;">
                    <i class="fas fa-inbox" style="font-size:40px;color:#bbf7d0;display:block;margin-bottom:10px;"></i>
                    <p style="font-size:14px;margin:0;color:#4a5568;">No history recorded for this goat yet.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

</div>

<?php renderFooter(); ?>