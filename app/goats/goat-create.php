<?php
require_once '../../config/config.php';
require_once '../../config/functions.php';
require_once '../../includes/goat-logger.php';
require_once '../../includes/goat-functions.php';
requireLogin();

$currentRole = $_SESSION['role'];
$currentUserId = $_SESSION['user_id'];

if (!canRegisterGoat($currentRole)) {
    die("Access denied. Only Farmers can register goats.");
}

// Where goat photos are stored on disk and served from.
define('GOAT_UPLOAD_DIR', __DIR__ . '/uploads/goats/');
define('GOAT_UPLOAD_URL', BASE_URL . '/app/goats/uploads/goats/');

/**
 * Validates and moves an uploaded goat photo to the uploads directory.
 * Returns the relative path (to store in DB) or null if no file was uploaded.
 * Throws an Exception with a user-facing message on validation failure.
 */
function handleGoatPhotoUpload(array $file): ?string {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // no file selected, that's fine - photo is optional
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Photo upload failed (error code {$file['error']}).");
    }

    $maxBytes = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxBytes) {
        throw new Exception("Photo is too large. Max size is 5MB.");
    }

    // Verify it's actually an image by inspecting its content -
    // never trust the client-supplied filename or mime type.
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        throw new Exception("Uploaded file is not a valid image.");
    }

    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];
    $mime = $imageInfo['mime'];
    if (!isset($allowedMimes[$mime])) {
        throw new Exception("Only JPG, PNG, or WEBP images are allowed.");
    }
    $ext = $allowedMimes[$mime];

    if (!is_dir(GOAT_UPLOAD_DIR)) {
        mkdir(GOAT_UPLOAD_DIR, 0755, true);
    }

    // Collision-resistant, safe filename - not based on user input.
    $filename = 'goat_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destination = GOAT_UPLOAD_DIR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception("Could not save the uploaded photo.");
    }

    return 'uploads/goats/' . $filename; // relative path, stored in DB
}

// Auto-generates the next sequential Goat ID *per farmer* - e.g. Farmer A's
// goats are G-0001, G-0002, ... and Farmer B's goats are ALSO G-0001, G-0002, ...
// (each farmer has their own independent numbering, not a shared global counter).
// IMPORTANT: this requires the DB uniqueness rule to be on (user_id, goat_id)
// together, not on goat_id alone - see migration note at the bottom of this file.
function generateNextGoatId(PDO $pdo, int $ownerId, string $prefix = 'G-', int $pad = 4): string {
    $stmt = $pdo->prepare("SELECT goat_id FROM goats WHERE user_id = :owner_id AND goat_id LIKE :pattern");
    $stmt->execute([':owner_id' => $ownerId, ':pattern' => $prefix . '%']);

    $max = 0;
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $existingId) {
        if (preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', $existingId, $m)) {
            $num = (int)$m[1];
            if ($num > $max) $max = $num;
        }
    }

    return $prefix . str_pad((string)($max + 1), $pad, '0', STR_PAD_LEFT);
}

// Farmers for the Admin "register on behalf of" dropdown
$farmers = [];
$nextGoatIdByFarmer = []; // farmer_id => next Goat ID, used for the live JS preview below
if ($currentRole === 'admin') {
    $farmers = $pdo->query("SELECT id, full_name, username FROM users WHERE role = 'user' ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($farmers as $f) {
        $nextGoatIdByFarmer[(int)$f['id']] = generateNextGoatId($pdo, (int)$f['id']);
    }
}

$message = '';
$success = false;

// ===== POST/REDIRECT/GET: pick up the success flag after a redirect =====
// This runs on the fresh GET load that follows a successful registration.
// It means refreshing the page after registering NEVER resubmits the form,
// because there's no POST data left to resubmit - just a plain GET request.
if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $success = true;
    $message = "Goat registered successfully! Goat ID: " . htmlspecialchars($_GET['goat_id'] ?? '');
}

// Preserve submitted values on validation failure.
// For a regular Farmer we already know the owner (themself), so we can show
// their next Goat ID right away. For an Admin, no Farmer is picked yet, so
// the field starts blank and fills in via JS once a Farmer is selected.
$goat_id_input = ($currentRole === 'admin') ? '' : generateNextGoatId($pdo, $currentUserId);
$goat_name = '';
$tag_number = '';
$sex = '';
$breed = '';
$date_of_birth = '';
$color = '';
$identifying_characteristics = '';
$date_acquired = '';
$health_status = 'unknown';
$owner_id_input = ($currentRole === 'admin') ? 0 : $currentUserId;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Goat ID is never taken from user input - it's always auto-generated
    // server-side right before insertion, below.
    $goat_name = trim($_POST['goat_name'] ?? '');
    $tag_number = trim($_POST['tag_number'] ?? '');
    $sex = normalizeGoatSex($_POST['sex'] ?? '');
    $breed = trim($_POST['breed'] ?? '');
    $date_of_birth = trim($_POST['date_of_birth'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $identifying_characteristics = trim($_POST['identifying_characteristics'] ?? '');
    $date_acquired = trim($_POST['date_acquired'] ?? '');
    $health_status = normalizeHealthStatus($_POST['health_status'] ?? 'unknown') ?? 'unknown';

    // The owning Farmer's User ID is ALWAYS taken from the authenticated
    // session. A Farmer can never choose another User ID. An Admin may
    // pick a Farmer from a server-verified list when registering on
    // their behalf, but never a raw, unverified ID.
    if ($currentRole === 'admin') {
        $owner_id_input = (int)($_POST['owner_id'] ?? 0);
        $ownerIsValidFarmer = false;
        foreach ($farmers as $f) {
            if ((int)$f['id'] === $owner_id_input) {
                $ownerIsValidFarmer = true;
                break;
            }
        }
        $ownerId = $ownerIsValidFarmer ? $owner_id_input : 0;
    } else {
        $ownerId = $currentUserId;
    }

    $errors = [];
    if ($goat_name === '') $errors[] = "Goat Name/Tag Number label is required.";
    if ($sex === null) $errors[] = "Sex must be Male or Female.";
    if ($breed === '') $errors[] = "Breed is required.";
    if ($date_of_birth !== '' && !DateTime::createFromFormat('Y-m-d', $date_of_birth)) {
        $errors[] = "Date of Birth must be a valid date.";
    }
    if ($date_acquired !== '' && !DateTime::createFromFormat('Y-m-d', $date_acquired)) {
        $errors[] = "Date Acquired must be a valid date.";
    }
    if ($ownerId <= 0) {
        $errors[] = ($currentRole === 'admin') ? "Please select a valid Farmer to register this goat under." : "Unable to determine owner.";
    }

    // Handle the photo upload once, up front (not inside the retry loop
    // below - that loop only retries the goat_id number, not the file).
    $photo_path = null;
    if (empty($errors)) {
        try {
            if (!empty($_FILES['goat_photo']) && $_FILES['goat_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
                $photo_path = handleGoatPhotoUpload($_FILES['goat_photo']);
            }
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        // Goat ID is auto-generated here, right before insert (not earlier),
        // so it reflects the latest number even if other goats were added
        // while this form was open. The UNIQUE constraint is the source of
        // truth; a small retry loop covers the rare case where two
        // registrations race for the same auto-generated number.
        $maxAttempts = 5;
        $inserted = false;

        for ($attempt = 1; $attempt <= $maxAttempts && !$inserted; $attempt++) {
            $goat_id_input = generateNextGoatId($pdo, $ownerId);

            try {
                $stmt = $pdo->prepare("
                    INSERT INTO goats
                        (goat_id, user_id, goat_name, tag_number, sex, breed, date_of_birth, color,
                         identifying_characteristics, photo_path, date_acquired, health_status, status, date_registered)
                    VALUES
                        (:goat_id, :user_id, :goat_name, :tag_number, :sex, :breed, :dob, :color,
                         :characteristics, :photo_path, :date_acquired, :health_status, 'active', NOW())
                ");
                $stmt->execute([
                    ':goat_id' => $goat_id_input,
                    ':user_id' => $ownerId,
                    ':goat_name' => $goat_name,
                    ':tag_number' => $tag_number !== '' ? $tag_number : null,
                    ':sex' => $sex,
                    ':breed' => $breed,
                    ':dob' => $date_of_birth !== '' ? $date_of_birth : null,
                    ':color' => $color !== '' ? $color : null,
                    ':characteristics' => $identifying_characteristics !== '' ? $identifying_characteristics : null,
                    ':photo_path' => $photo_path,
                    ':date_acquired' => $date_acquired !== '' ? $date_acquired : null,
                    ':health_status' => $health_status,
                ]);

                $inserted = true;
                $registeredGoatId = $goat_id_input;

                logGoatActivity($pdo, $currentUserId, $_SESSION['email'], 'goat_registered', 'success', $goat_id_input, [
                    'owner_id' => $ownerId,
                    'health_status' => $health_status,
                    'status' => 'active',
                    'photo' => $photo_path ? 'uploaded' : 'none',
                ]);

                // ===== POST/REDIRECT/GET =====
                // Redirect to a plain GET request instead of falling through
                // and re-rendering the page. This means if the user refreshes
                // the page afterward, the browser just reloads a GET page -
                // there's no POST data left to resubmit, so no duplicate insert.
                header('Location: ' . BASE_URL . '/app/goats/goat-create.php?registered=1&goat_id=' . urlencode($goat_id_input));
                exit;

            } catch (PDOException $e) {
                // MySQL 1062 = duplicate entry (the UNIQUE constraint caught a race).
                // Loop again to generate the next number; anything else is a real error.
                if ($e->getCode() == 23000) {
                    continue;
                }
                $message = "Error registering goat. Please try again.";
                logGoatActivity($pdo, $currentUserId, $_SESSION['email'], 'goat_registered', 'failed', $goat_id_input);
                $success = false;
                break;
            }
        }

        if (!$inserted) {
            // Clean up the orphaned photo file if the insert never succeeded.
            if ($photo_path && file_exists(__DIR__ . '/' . $photo_path)) {
                unlink(__DIR__ . '/' . $photo_path);
            }
            if (!$success && $message === '') {
                $message = "Could not generate a unique Goat ID right now. Please try again.";
                $success = false;
            }
        }
    } else {
        // Validation failed - clean up any photo we already saved to disk
        // (the record won't be inserted, so don't leave an orphaned file).
        if ($photo_path && file_exists(__DIR__ . '/' . $photo_path)) {
            unlink(__DIR__ . '/' . $photo_path);
        }
        $message = implode(' ', $errors);
    }
}

$title = 'Add Goat';
renderHeader($title);
?>

<!-- FULLSCREEN STYLES - matches Goat Inventory dashboard + navigation classes -->
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
        max-width: 1000px !important;
        padding: 16px 12px 28px 12px !important;
        margin: 0 auto !important;
        min-height: 100vh !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: flex-start !important;
        background: #f0fdf4 !important;
        box-sizing: border-box !important;
    }
    @media (min-width: 640px) {
        .main-content .container { padding: 28px 24px 36px 24px !important; }
    }
    @media (min-width: 1024px) {
        .main-content .container { padding: 40px 36px 40px 36px !important; }
    }
    html, body {
        height: 100% !important;
        min-height: 100vh !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #f0fdf4 !important;
        font-size: 16px !important;
        font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
    }

    /* ---------- Navigation classes (matching dashboard) ---------- */
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

    @keyframes sway { 0%,100% { transform: rotate(-2deg); } 50% { transform: rotate(2deg); } }

    /* Form section cards */
    .gform-card {
        background: white;
        border-radius: 10px;
        padding: 8px 20px 4px 20px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        border: 1px solid #dcfce7;
        margin-bottom: 8px;
    }
    .gform-section-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 15px;
        font-weight: 700;
        color: #166534;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 14px 0 4px 0;
    }
    .gform-section-hint {
        font-size: 13px;
        color: #94a3ad;
        margin: 0 0 14px 0;
    }
    .gform-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px 16px;
        padding-bottom: 16px;
    }
    .gform-grid .span-2 { grid-column: 1 / -1; }
    @media (max-width: 600px) {
        .gform-grid { grid-template-columns: 1fr; }
    }

    .gform-card label {
        font-size: 13px;
        font-weight: 600;
        color: #4a5568;
        display: block;
        margin-bottom: 4px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .gform-card label .req { color: #ef4444; margin-left: 2px; }
    .gform-card label .opt { color: #a0aec0; font-weight: 400; text-transform: none; letter-spacing: 0; }

    .gform-card input[type="text"],
    .gform-card input[type="date"],
    .gform-card input[type="file"],
    .gform-card select,
    .gform-card textarea {
        width: 100%;
        box-sizing: border-box;
        padding: 8px 14px;
        border: 2px solid #bbf7d0;
        border-radius: 8px;
        font-size: 15px;
        background: white;
        color: #2d3748;
        font-family: inherit;
    }
    .gform-card textarea { resize: vertical; min-height: 70px; }
    .gform-card input:focus,
    .gform-card select:focus,
    .gform-card textarea:focus {
        border-color: #22C55E !important;
        outline: none !important;
        box-shadow: 0 0 0 3px rgba(34,197,94,0.15) !important;
    }

    .gform-note { font-size: 12px; color: #a0aec0; margin-top: 4px; }

    .gform-photo-preview {
        margin-top: 10px;
        display: none;
        max-width: 160px;
        border-radius: 10px;
        overflow: hidden;
        border: 2px solid #bbf7d0;
    }
    .gform-photo-preview img {
        display: block;
        width: 100%;
        height: auto;
    }

    .gform-admin-box {
        background: #fffbeb;
        border: 2px solid #fde68a;
        border-radius: 10px;
        padding: 14px 16px 4px 16px;
        margin: 10px 0 14px 0;
    }
    .gform-admin-box label { color: #92400e; }
    .gform-admin-box .gform-note { color: #b45309; }

    .btn-submit {
        padding: 10px 26px;
        font-size: 15px;
        border-radius: 8px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        background: #22C55E;
        color: white;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        height: 46px;
        box-shadow: 0 2px 6px rgba(34,197,94,0.3);
    }
    .btn-submit:hover { background: #16a34a; }
    .btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }
    .btn-cancel {
        padding: 10px 26px;
        font-size: 15px;
        border-radius: 8px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        background: #6b7280;
        color: white;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        height: 46px;
        text-decoration: none;
    }
    .btn-cancel:hover { background: #4b5563; }
</style>

<div style="width:100%;height:100%;min-height:100vh;display:flex;flex-direction:column;background:#f0fdf4;">

    <!-- ===== HEADER ===== -->
    <div class="header-grass">
        <div class="blob-1"></div>
        <div class="blob-2"></div>
        <div class="header-inner">
            <div class="header-title-row">
                <span class="header-goat">🐐</span>
                <div style="min-width:0;">
                    <h1>
                        Register New Goat
                        <span class="header-role-badge"><?php echo ucfirst($currentRole); ?></span>
                    </h1>
                    <p>
                        <i class="fas fa-leaf" style="font-size:12px;"></i> Add a goat record to the inventory
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

    <!-- ===== NAVIGATION (FULLY VISIBLE ON MOBILE) ===== -->
    <?php
    $dashboardLink = BASE_URL . '/app/' . $currentRole . '/dashboard.php';
    ?>
    <nav class="dash-nav">
        <a href="<?php echo $dashboardLink; ?>" class="dash-nav-link">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="<?php echo BASE_URL; ?>/app/goats/dashboard.php" class="dash-nav-link">
            <i class="fas fa-goat"></i> Inventory
        </a>
        <a href="<?php echo BASE_URL; ?>/app/goats/goat-create.php" class="dash-nav-link active">
            <i class="fas fa-plus-circle"></i> Add Goat
        </a>
        <?php if ($currentRole === 'manager' || $currentRole === 'admin'): ?>
            <a href="<?php echo BASE_URL; ?>/app/goats/farmer-overview.php" class="dash-nav-link">
                <i class="fas fa-tractor"></i> Farmers
            </a>
        <?php endif; ?>
        <a href="<?php echo BASE_URL; ?>/app/auth/signout.php" class="dash-nav-link logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>

    <!-- ===== ALERT MESSAGE ===== -->
    <?php if ($message): ?>
        <div style="background:white;border-radius:10px;padding:14px 18px;border-left:5px solid <?php echo $success ? '#22C55E' : '#ef4444'; ?>;margin-bottom:8px;font-size:15px;color:<?php echo $success ? '#166534' : '#991b1b'; ?>;box-shadow:0 1px 4px rgba(0,0,0,0.04);display:flex;align-items:center;gap:10px;">
            <i class="fas fa-<?php echo $success ? 'check-circle' : 'exclamation-circle'; ?>" style="font-size:20px;color:<?php echo $success ? '#22C55E' : '#ef4444'; ?>;"></i>
            <span><?php echo htmlspecialchars($message); ?></span>
        </div>
    <?php endif; ?>

    <!-- ===== INFO BOX ===== -->
    <div style="background:white;border-radius:10px;padding:14px 18px;border-left:5px solid #22C55E;margin-bottom:8px;font-size:15px;color:#4a5568;box-shadow:0 1px 4px rgba(0,0,0,0.04);line-height:1.7;">
        <i class="fas fa-info-circle" style="color:#22C55E;"></i>
        Fields marked <strong style="color:#ef4444;">*</strong> are required.
        <?php if ($currentRole === 'admin'): ?>
            As an Administrator, you can register a goat on behalf of any Farmer below &mdash; this action is logged.
        <?php endif; ?>
    </div>

    <!-- ===== FORM ===== -->
    <form method="POST" class="gform-card" enctype="multipart/form-data">

        <?php if ($currentRole === 'admin'): ?>
        <div class="gform-section-title"><i class="fas fa-user-tie" style="color:#166534;"></i> Registering On Behalf Of</div>
        <div class="gform-admin-box">
            <label for="owner_id">Farmer (Owner)<span class="req">*</span></label>
            <select id="owner_id" name="owner_id" required>
                <option value="">-- Select Farmer --</option>
                <?php foreach ($farmers as $f): ?>
                    <option value="<?php echo $f['id']; ?>" <?php echo $owner_id_input === (int)$f['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($f['full_name'] . ' (' . $f['username'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="gform-note"><i class="fas fa-shield-alt"></i> Administrator registration on behalf of a Farmer. This is logged.</p>
        </div>
        <?php else: ?>
            <input type="hidden" name="owner_id" value="<?php echo $currentUserId; ?>">
        <?php endif; ?>

        <div class="gform-section-title"><i class="fas fa-id-card" style="color:#166534;"></i> Identification</div>
        <p class="gform-section-hint"></p>
        <div class="gform-grid">
            <div class="span-2">
                <label for="goat_id_display">Goat ID <span class="opt"></span></label>
                <div style="position:relative;">
                    <input type="text" id="goat_id_display"
                           value="<?php echo htmlspecialchars($goat_id_input); ?>"
                           placeholder="<?php echo $currentRole === 'admin' ? 'Select a Farmer above to preview the ID' : ''; ?>"
                           readonly
                           style="background:#f0fdf4;color:#166534;font-weight:700;letter-spacing:0.5px;cursor:not-allowed;">
                    <i class="fas fa-lock" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);color:#86efac;"></i>
                </div>
                <p class="gform-note"><i class="fas fa-magic"></i> Auto-generated based on the farmer's sequence.</p>
            </div>
            <div>
                <label for="goat_name">Goat Name / Tag Label<span class="req">*</span></label>
                <input type="text" id="goat_name" name="goat_name" required
                       value="<?php echo htmlspecialchars($goat_name); ?>" placeholder="e.g. Bantay">
            </div>
            <div>
                <label for="tag_number">Ear Tag Number <span class="opt">(optional)</span></label>
                <input type="text" id="tag_number" name="tag_number"
                       value="<?php echo htmlspecialchars($tag_number); ?>" placeholder="e.g. 0245">
            </div>
            <div class="span-2">
                <label for="goat_photo">Goat Photo <span class="opt">(optional)</span></label>
                <input type="file" id="goat_photo" name="goat_photo" accept="image/jpeg,image/png,image/webp">
                <p class="gform-note"><i class="fas fa-image"></i> JPG, PNG, or WEBP. Max 5MB.</p>
                <div class="gform-photo-preview" id="goat_photo_preview">
                    <img id="goat_photo_preview_img" src="" alt="Photo preview">
                </div>
            </div>
        </div>

        <div class="gform-section-title"><i class="fas fa-paw" style="color:#166534;"></i> Physical Description</div>
        <div class="gform-grid">
            <div>
                <label for="sex">Sex<span class="req">*</span></label>
                <select id="sex" name="sex" required>
                    <option value="">-- Select --</option>
                    <option value="male" <?php echo $sex === 'male' ? 'selected' : ''; ?>>Male</option>
                    <option value="female" <?php echo $sex === 'female' ? 'selected' : ''; ?>>Female</option>
                </select>
            </div>
            <div>
                <label for="breed">Breed<span class="req">*</span></label>
                <input type="text" id="breed" name="breed" required
                       value="<?php echo htmlspecialchars($breed); ?>" placeholder="e.g. Boer, Anglo-Nubian">
            </div>
            <div>
                <label for="color">Color <span class="opt">(optional)</span></label>
                <input type="text" id="color" name="color"
                       value="<?php echo htmlspecialchars($color); ?>" placeholder="e.g. Brown and white">
            </div>
            <div>
                <label for="date_of_birth">Date of Birth <span class="opt">(optional)</span></label>
                <input type="date" id="date_of_birth" name="date_of_birth"
                       value="<?php echo htmlspecialchars($date_of_birth); ?>">
            </div>
            <div class="span-2">
                <label for="identifying_characteristics">Other Identifying Characteristics <span class="opt">(optional)</span></label>
                <textarea id="identifying_characteristics" name="identifying_characteristics" rows="3"
                          placeholder="Scars, markings, horn shape, or anything else that helps identify this goat"><?php echo htmlspecialchars($identifying_characteristics); ?></textarea>
            </div>
        </div>

        <div class="gform-section-title"><i class="fas fa-heartbeat" style="color:#166534;"></i> Acquisition &amp; Health</div>
        <div class="gform-grid">
            <div>
                <label for="date_acquired">Date Acquired <span class="opt">(optional)</span></label>
                <input type="date" id="date_acquired" name="date_acquired"
                       value="<?php echo htmlspecialchars($date_acquired); ?>">
            </div>
            <div>
                <label for="health_status">Health Status</label>
                <select id="health_status" name="health_status">
                    <option value="unknown" <?php echo $health_status === 'unknown' ? 'selected' : ''; ?>>Unknown</option>
                    <option value="healthy" <?php echo $health_status === 'healthy' ? 'selected' : ''; ?>>Healthy</option>
                    <option value="sick" <?php echo $health_status === 'sick' ? 'selected' : ''; ?>>Sick</option>
                </select>
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:10px;padding:14px 0 18px 0;">
            <a href="<?php echo BASE_URL; ?>/app/goats/dashboard.php" class="btn-cancel">
                <i class="fas fa-times"></i> Cancel
            </a>
            <button type="submit" class="btn-submit">
                <i class="fas fa-plus-circle"></i> Register Goat
            </button>
        </div>
    </form>

</div>

<?php if ($currentRole === 'admin'): ?>
<script>
    // Per-farmer next Goat ID, precomputed server-side. Purely a UI preview -
    // the real ID is always (re)generated server-side at submit time.
    const nextGoatIdByFarmer = <?php echo json_encode($nextGoatIdByFarmer); ?>;

    document.addEventListener('DOMContentLoaded', function () {
        const ownerSelect = document.getElementById('owner_id');
        const goatIdDisplay = document.getElementById('goat_id_display');
        if (!ownerSelect || !goatIdDisplay) return;

        function updatePreview() {
            const id = nextGoatIdByFarmer[ownerSelect.value];
            goatIdDisplay.value = id || '';
        }

        ownerSelect.addEventListener('change', updatePreview);
        updatePreview(); // in case a farmer is already selected (e.g. after a validation error)
    });
</script>
<?php endif; ?>

<script>
    // Live client-side preview of the selected goat photo before upload.
    // This is purely cosmetic - all real validation happens server-side.
    document.addEventListener('DOMContentLoaded', function () {
        const photoInput = document.getElementById('goat_photo');
        const previewBox = document.getElementById('goat_photo_preview');
        const previewImg = document.getElementById('goat_photo_preview_img');
        if (!photoInput || !previewBox || !previewImg) return;

        photoInput.addEventListener('change', function () {
            const file = photoInput.files && photoInput.files[0];
            if (!file) {
                previewBox.style.display = 'none';
                previewImg.src = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = function (e) {
                previewImg.src = e.target.result;
                previewBox.style.display = 'block';
            };
            reader.readAsDataURL(file);
        });
    });
</script>

<script>
    // Prevent duplicate submissions from double-clicking the submit button.
    // (The server-side redirect-after-success handles the "refreshed the
    // page after submitting" case; this handles the "clicked twice" case.)
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('form.gform-card');
        const submitBtn = form ? form.querySelector('.btn-submit') : null;
        if (!form || !submitBtn) return;

        form.addEventListener('submit', function () {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registering...';
        });
    });
</script>

<?php renderFooter(); ?>