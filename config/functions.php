<?php

function redirect($path) {
    header("Location: " . BASE_URL . $path);
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/index.php');
    }
}

function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        die("Access denied. Required role: $role");
    }
}

function hasAnyRole($roles) {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], $roles);
}

function requireAnyRole($roles) {
    requireLogin();
    if (!hasAnyRole($roles)) {
        die("Access denied. Required role: " . implode(' or ', $roles));
    }
}

// Role hierarchy used across the User Management module.
// Admin can manage everyone. Manager can only manage regular users
// (Farmers) and can never create, edit, or delete Admin or Manager accounts.
function canManageTarget($currentRole, $currentUserId, $targetRole, $targetUserId) {
    if ($currentRole === 'admin') {
        return true;
    }
    if ($currentRole === 'manager') {
        return $targetRole === 'user';
    }
    // Regular users can only manage (view/update password of) themselves
    return $targetUserId == $currentUserId;
}

function canDeleteTarget($currentRole, $currentUserId, $targetRole, $targetUserId) {
    // Nobody can delete their own account
    if ($targetUserId == $currentUserId) {
        return false;
    }
    if ($currentRole === 'admin') {
        return true;
    }
    if ($currentRole === 'manager') {
        return $targetRole === 'user';
    }
    return false;
}

function canAssignRole($currentRole) {
    // Only Admin can assign/change roles
    return $currentRole === 'admin';
}

function canViewTarget($currentRole, $currentUserId, $targetRole, $targetUserId) {
    if ($targetUserId == $currentUserId) {
        return true; // Everyone can view their own profile
    }
    if ($currentRole === 'admin') {
        return true;
    }
    if ($currentRole === 'manager') {
        return $targetRole === 'user';
    }
    return false;
}

function renderHeader($title) {
    $currentRole = $_SESSION['role'] ?? 'guest';
    $currentEmail = $_SESSION['email'] ?? '';
    $isLoggedIn = isset($_SESSION['user_id']);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title); ?></title>
        <!-- Material Icons -->
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">


    </head>
    <body>
        <?php if ($isLoggedIn): ?>
            <div class="main-content">
                <div class="container">
        <?php else: ?>
            <div class="container" style="margin: 0 auto; max-width: 500px; padding-top: 100px;">
        <?php endif; ?>
    <?php
}

function renderFooter() {
    $isLoggedIn = isset($_SESSION['user_id']);
    ?>
        <?php if ($isLoggedIn): ?>
                </div>
            </div>
        <?php else: ?>
            </div>
        <?php endif; ?>
    </body>
    </html>
    <?php
}

?>