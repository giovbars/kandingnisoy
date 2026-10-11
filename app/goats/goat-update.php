<?php
require_once '../../config/config.php';
require_once '../../config/functions.php';
require_once '../../includes/goat-logger.php';
require_once '../../includes/goat-functions.php';
requireLogin();

$currentRole = $_SESSION['role'];
$currentUserId = $_SESSION['user_id'];

// Look up by primary key `id`
$goatPk = (int) trim($_GET['id'] ?? '');

$stmt = $pdo->prepare("SELECT * FROM goats WHERE id = ?");
$stmt->execute([$goatPk]);
$goat = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$goat) {
    renderHeader('Edit Goat');
    echo '<div class="error">The requested goat record could not be found.</div>';
    echo '<p><a href="dashboard.php">Back to Goat Inventory</a></p>';
    renderFooter();
    exit;
}

// Permissions
$canFullEdit = canEditGoat($currentRole, $currentUserId, $goat['user_id']);
$canHealthOnly = canUpdateHealthStatus($currentRole, $currentUserId, $goat['user_id']);
$canCorrectOwner = canCorrectOwnership($currentRole);

if (!$canFullEdit && !$canHealthOnly) {
    die("Access denied. You are not authorized to access this goat record.");
}

$farmers = [];
if ($canCorrectOwner) {
    $farmers = $pdo->query("SELECT id, full_name, username FROM users WHERE role = 'user' ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
}

$message = '';
$success = false;
$errors = [];

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updates = [];
    $params = [];
    $logEntries = [];

    // ---- Farmer-editable fields ----
    if ($canFullEdit) {
        $goat_name = trim($_POST['goat_name'] ?? $goat['goat_name']);
        $tag_number = trim($_POST['tag_number'] ?? '');
        $breed = trim($_POST['breed'] ?? $goat['breed']);
        $date_of_birth = trim($_POST['date_of_birth'] ?? '');
        $color = trim($_POST['color'] ?? '');
        $identifying_characteristics = trim($_POST['identifying_characteristics'] ?? '');
        $date_acquired = trim($_POST['date_acquired'] ?? '');

        if ($goat_name === '') $errors[] = "Goat Name is required.";
        if ($breed === '') $errors[] = "Breed is required.";
        if ($date_of_birth !== '' && !DateTime::createFromFormat('Y-m-d', $date_of_birth)) {
            $errors[] = "Date of Birth must be a valid date.";
        }
        if ($date_acquired !== '' && !DateTime::createFromFormat('Y-m-d', $date_acquired)) {
            $errors[] = "Date Acquired must be a valid date.";
        }

        if (empty($errors)) {
            if ($goat_name !== $goat['goat_name']) { $updates[] = "goat_name = :goat_name"; $params[':goat_name'] = $goat_name; }
            if ($tag_number !== (string)$goat['tag_number']) { $updates[] = "tag_number = :tag_number"; $params[':tag_number'] = $tag_number !== '' ? $tag_number : null; }
            if ($breed !== $goat['breed']) { $updates[] = "breed = :breed"; $params[':breed'] = $breed; }
            if ($date_of_birth !== (string)$goat['date_of_birth']) { $updates[] = "date_of_birth = :dob"; $params[':dob'] = $date_of_birth !== '' ? $date_of_birth : null; }
            if ($color !== (string)$goat['color']) { $updates[] = "color = :color"; $params[':color'] = $color !== '' ? $color : null; }
            if ($identifying_characteristics !== (string)$goat['identifying_characteristics']) { $updates[] = "identifying_characteristics = :characteristics"; $params[':characteristics'] = $identifying_characteristics !== '' ? $identifying_characteristics : null; }
            if ($date_acquired !== (string)$goat['date_acquired']) { $updates[] = "date_acquired = :date_acquired"; $params[':date_acquired'] = $date_acquired !== '' ? $date_acquired : null; }

            if (!empty($updates)) {
                $logEntries[] = ['goat_updated', ['field' => 'info']];
            }

            $newStatus = normalizeGoatStatus($_POST['status'] ?? $goat['status']);
            if ($newStatus === null) {
                $errors[] = "The selected goat status is not valid.";
            } elseif ($newStatus !== $goat['status']) {
                $updates[] = "status = :status";
                $params[':status'] = $newStatus;
                $statusAction = ($newStatus === 'sold') ? 'goat_marked_sold'
                              : (($newStatus === 'deceased') ? 'goat_marked_deceased' : 'goat_status_changed');
                $logEntries[] = [$statusAction, ['previous' => $goat['status'], 'new' => $newStatus]];
            }
        }
    }

    // ---- Health status ----
    if (($canFullEdit || $canHealthOnly) && empty($errors)) {
        $newHealth = normalizeHealthStatus($_POST['health_status'] ?? $goat['health_status']);
        if ($newHealth === null) {
            $errors[] = "The selected health status is not valid.";
        } elseif ($newHealth !== $goat['health_status']) {
            $updates[] = "health_status = :health_status";
            $params[':health_status'] = $newHealth;
            $logEntries[] = ['goat_health_status_changed', ['previous' => $goat['health_status'], 'new' => $newHealth]];
        }
    }

    // ---- Ownership correction (Admin only) ----
    if ($canCorrectOwner && empty($errors)) {
        $newOwnerId = (int)($_POST['owner_id'] ?? $goat['user_id']);
        if ($newOwnerId !== (int)$goat['user_id']) {
            $ownerIsValidFarmer = false;
            foreach ($farmers as $f) {
                if ((int)$f['id'] === $newOwnerId) { $ownerIsValidFarmer = true; break; }
            }
            if (!$ownerIsValidFarmer) {
                $errors[] = "Please select a valid Farmer for the ownership correction.";
            } else {
                $updates[] = "user_id = :owner_id";
                $params[':owner_id'] = $newOwnerId;
                $logEntries[] = ['goat_ownership_corrected', ['previous_owner' => (int)$goat['user_id'], 'new_owner' => $newOwnerId]];
            }
        }
    }

    // ---- Execute ----
    if (!empty($errors)) {
        $message = implode(' ', $errors);
        $success = false;
    } elseif (empty($updates)) {
        $message = "No changes were made.";
        $success = false;
    } else {
        try {
            $updates[] = "updated_at = NOW()";
            $params[':goat_id_pk'] = $goat['id'];
            $sql = "UPDATE goats SET " . implode(', ', $updates) . " WHERE id = :goat_id_pk";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            foreach ($logEntries as $entry) {
                logGoatActivity($pdo, $currentUserId, $_SESSION['email'], $entry[0], 'success', $goat['goat_id'], $entry[1]);
            }

            $message = "✅ Goat record updated successfully!";
            $success = true;

            // Refresh goat data
            $stmt = $pdo->prepare("SELECT * FROM goats WHERE id = ?");
            $stmt->execute([$goat['id']]);
            $goat = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            $message = "❌ Error updating goat record. Please try again.";
            $success = false;
            logGoatActivity($pdo, $currentUserId, $_SESSION['email'], 'goat_updated', 'failed', $goat['goat_id']);
        }
    }
}

$title = 'Edit Goat - ' . htmlspecialchars($goat['goat_id']);
renderHeader($title);
?>

<!-- ===== FULLSCREEN STYLES (consistent with dashboards) ===== -->
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

    /* Card */
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
    .form-group select,
    .form-group textarea {
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
    .form-group select:focus,
    .form-group textarea:focus {
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
    .form-group textarea {
        font-family: inherit;
        resize: vertical;
        min-height: 80px;
    }
    .form-group .help-text {
        font-size: 12px;
        color: #94a3b8;
        margin-top: 2px;
    }
    .form-group .help-text i { margin-right: 4px; }

    /* Buttons */
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
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 12px;
    }

    /* Messages */
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

    /* Info boxes */
    .info-box {
        background: #f0fdf4;
        border-left: 5px solid #22C55E;
        padding: 14px 18px;
        border-radius: 10px;
        color: #166534;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .info-box.warning {
        background: #fef3c7;
        border-left-color: #f59e0b;
        color: #92400e;
    }
    .info-box i { font-size: 18px; }

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

    /* Responsive tweaks */
    @media (max-width: 640px) {
        .card { padding: 14px 16px; }
        .nav-bar a { font-size: 13px; padding: 6px 12px; }
        .btn { font-size: 13px; padding: 8px 16px; }
        .form-group input, .form-group select, .form-group textarea {
            font-size: 14px;
            padding: 8px 12px;
        }
        .card-title { font-size: 17px; }
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
                        Edit Goat
                        <span style="background:rgba(255,255,255,0.2);padding:2px 14px;border-radius:20px;font-size:14px;font-weight:500;color:white;"><?php echo htmlspecialchars($goat['goat_id']); ?></span>
                    </h1>
                    <p style="font-size:16px;color:rgba(255,255,255,0.8);margin:4px 0 0 0;">
                        <i class="fas fa-edit" style="font-size:14px;"></i> Update the details of this goat
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
        <a href="<?php echo BASE_URL; ?>/app/goats/goat-view.php?id=<?php echo urlencode($goat['id']); ?>">
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

    <!-- PERMISSION INFO BOXES -->
    <?php if ($canHealthOnly && !$canFullEdit): ?>
    <div class="info-box warning">
        <i class="fas fa-info-circle"></i>
        <div>
            <strong>Manager Access:</strong> You may only update the <strong>Health Status</strong> of this goat. Other details can only be changed by the Farmer (owner) or an Administrator.
        </div>
    </div>
    <?php endif; ?>

    <?php if ($canCorrectOwner && $canFullEdit): ?>
    <div class="info-box">
        <i class="fas fa-shield-alt"></i>
        <div>
            <strong>Administrator Access:</strong> You can edit all fields and also reassign ownership to another Farmer.
        </div>
    </div>
    <?php endif; ?>

    <!-- MESSAGE -->
    <?php if ($message): ?>
        <div class="message <?php echo $success ? 'success' : 'error'; ?>">
            <i class="fas <?php echo $success ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- FORM -->
    <div class="card">
        <div class="card-title">
            <i class="fas fa-pen"></i> Goat Information
            <span style="font-size:13px;font-weight:400;color:#94a3b8;margin-left:8px;">
                <?php echo $canFullEdit ? 'Full edit access' : 'Limited access (health only)'; ?>
            </span>
        </div>

        <form method="POST">
            <div class="form-grid">

                <!-- GOAT NAME -->
                <?php if ($canFullEdit): ?>
                <div class="form-group">
                    <label for="goat_name">Goat Name <span class="required">*</span></label>
                    <input type="text" id="goat_name" name="goat_name" required
                           value="<?php echo htmlspecialchars($goat['goat_name']); ?>"
                           placeholder="e.g. Bella, Blackie">
                </div>
                <?php else: ?>
                <input type="hidden" name="goat_name" value="<?php echo htmlspecialchars($goat['goat_name']); ?>">
                <?php endif; ?>

                <!-- TAG NUMBER -->
                <?php if ($canFullEdit): ?>
                <div class="form-group">
                    <label for="tag_number">Ear Tag Number</label>
                    <input type="text" id="tag_number" name="tag_number"
                           value="<?php echo htmlspecialchars((string)$goat['tag_number']); ?>"
                           placeholder="e.g. T-001">
                    <div class="help-text"><i class="fas fa-info-circle"></i> Optional unique identifier</div>
                </div>
                <?php endif; ?>

                <!-- SEX (disabled) -->
                <div class="form-group">
                    <label for="sex">Sex <span class="required">*</span></label>
                    <input type="text" id="sex" value="<?php echo ucfirst($goat['sex']); ?>" disabled>
                    <div class="help-text"><i class="fas fa-lock"></i> Sex cannot be changed after registration</div>
                </div>

                <!-- BREED -->
                <?php if ($canFullEdit): ?>
                <div class="form-group">
                    <label for="breed">Breed <span class="required">*</span></label>
                    <input type="text" id="breed" name="breed" required
                           value="<?php echo htmlspecialchars($goat['breed']); ?>"
                           placeholder="e.g. Saanen, Anglo-Nubian">
                </div>
                <?php endif; ?>

                <!-- DATE OF BIRTH -->
                <?php if ($canFullEdit): ?>
                <div class="form-group">
                    <label for="date_of_birth">Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth"
                           value="<?php echo htmlspecialchars((string)$goat['date_of_birth']); ?>">
                </div>
                <?php endif; ?>

                <!-- COLOR -->
                <?php if ($canFullEdit): ?>
                <div class="form-group">
                    <label for="color">Color</label>
                    <input type="text" id="color" name="color"
                           value="<?php echo htmlspecialchars((string)$goat['color']); ?>"
                           placeholder="e.g. White, Brown, Black">
                </div>
                <?php endif; ?>

                <!-- DATE ACQUIRED -->
                <?php if ($canFullEdit): ?>
                <div class="form-group">
                    <label for="date_acquired">Date Acquired</label>
                    <input type="date" id="date_acquired" name="date_acquired"
                           value="<?php echo htmlspecialchars((string)$goat['date_acquired']); ?>">
                </div>
                <?php endif; ?>

                <!-- CHARACTERISTICS (full width) -->
                <?php if ($canFullEdit): ?>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="identifying_characteristics">Identifying Characteristics</label>
                    <textarea id="identifying_characteristics" name="identifying_characteristics" rows="3"
                              placeholder="e.g. White spot on forehead, missing left ear tag"><?php echo htmlspecialchars((string)$goat['identifying_characteristics']); ?></textarea>
                    <div class="help-text"><i class="fas fa-info-circle"></i> Any unique markings or features</div>
                </div>
                <?php endif; ?>

                <!-- HEALTH STATUS -->
                <div class="form-group" style="<?php echo ($canFullEdit || $canHealthOnly) ? '' : 'grid-column: 1 / -1;'; ?>">
                    <label for="health_status">Health Status <span class="required">*</span></label>
                    <select id="health_status" name="health_status" <?php echo ($canFullEdit || $canHealthOnly) ? '' : 'disabled'; ?>>
                        <option value="unknown" <?php echo $goat['health_status'] === 'unknown' ? 'selected' : ''; ?>>Unknown</option>
                        <option value="healthy" <?php echo $goat['health_status'] === 'healthy' ? 'selected' : ''; ?>>Healthy</option>
                        <option value="sick" <?php echo $goat['health_status'] === 'sick' ? 'selected' : ''; ?>>Sick</option>
                    </select>
                </div>

                <!-- STATUS -->
                <?php if ($canFullEdit): ?>
                <div class="form-group">
                    <label for="status">Goat Status <span class="required">*</span></label>
                    <select id="status" name="status">
                        <option value="active" <?php echo $goat['status'] === 'active' ? 'selected' : ''; ?>>Active / Currently Owned</option>
                        <option value="sold" <?php echo $goat['status'] === 'sold' ? 'selected' : ''; ?>>Sold</option>
                        <option value="deceased" <?php echo $goat['status'] === 'deceased' ? 'selected' : ''; ?>>Deceased</option>
                    </select>
                    <div class="help-text"><i class="fas fa-info-circle"></i> Sold/Deceased goats are kept for historical records</div>
                </div>
                <?php else: ?>
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($goat['status']); ?>">
                <?php endif; ?>

                <!-- OWNER CORRECTION (Admin only) -->
                <?php if ($canCorrectOwner): ?>
                <div class="form-group" style="grid-column: 1 / -1; border-top: 2px dashed #e2e8f0; padding-top: 16px; margin-top: 8px;">
                    <label for="owner_id">
                        <i class="fas fa-user-shield" style="color:#f59e0b;"></i> 
                        Owner (Farmer) — Administrative Correction
                    </label>
                    <select id="owner_id" name="owner_id">
                        <?php foreach ($farmers as $f): ?>
                            <option value="<?php echo $f['id']; ?>" <?php echo (int)$goat['user_id'] === (int)$f['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($f['full_name'] . ' (' . $f['username'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="help-text"><i class="fas fa-exclamation-triangle" style="color:#f59e0b;"></i> Reassigning ownership is logged as an administrative correction</div>
                </div>
                <?php endif; ?>

            </div> <!-- end form-grid -->

            <!-- BUTTONS -->
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <a href="<?php echo BASE_URL; ?>/app/goats/goat-view.php?id=<?php echo urlencode($goat['id']); ?>" class="btn btn-outline">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>

        </form>
    </div>

</div>

<?php renderFooter(); ?>