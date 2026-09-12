<?php
/**
 * Goat Inventory Management Module - Helper Functions
 */

// ---------------------------------------------------------------------
// Allowed value sets
// ---------------------------------------------------------------------

function allowedHealthStatuses() {
    return ['healthy', 'sick', 'unknown'];
}

function allowedGoatStatuses() {
    return ['active', 'sold', 'deceased'];
}

function allowedGoatSexes() {
    return ['male', 'female'];
}

function normalizeHealthStatus($value) {
    $value = strtolower(trim((string)$value));
    return in_array($value, allowedHealthStatuses(), true) ? $value : null;
}

function normalizeGoatStatus($value) {
    $value = strtolower(trim((string)$value));
    return in_array($value, allowedGoatStatuses(), true) ? $value : null;
}

function normalizeGoatSex($value) {
    $value = strtolower(trim((string)$value));
    return in_array($value, allowedGoatSexes(), true) ? $value : null;
}

// ---------------------------------------------------------------------
// Display labels
// ---------------------------------------------------------------------

function healthStatusLabel($value) {
    switch ($value) {
        case 'healthy': return 'Healthy';
        case 'sick': return 'Sick';
        case 'unknown': return 'Unknown';
        default: return ucfirst((string)$value);
    }
}

function goatStatusLabel($value) {
    switch ($value) {
        case 'active': return 'Active / Currently Owned';
        case 'sold': return 'Sold';
        case 'deceased': return 'Deceased';
        default: return ucfirst((string)$value);
    }
}

function healthStatusBadgeClass($value) {
    switch ($value) {
        case 'healthy': return 'badge-active';
        case 'sick': return 'badge-admin';
        default: return 'badge-inactive';
    }
}

function goatStatusBadgeClass($value) {
    switch ($value) {
        case 'active': return 'badge-active';
        case 'sold': return 'badge-manager';
        case 'deceased': return 'badge-inactive';
        default: return 'badge-inactive';
    }
}

// ---------------------------------------------------------------------
// RBAC: Permission Functions - FIXED for User/Farmer
// ---------------------------------------------------------------------

/**
 * Can the current user view this goat record?
 */
function canAccessGoat($currentRole, $currentUserId, $goatOwnerId) {
    if ($currentRole === 'admin' || $currentRole === 'manager') {
        return true;
    }
    return (int)$goatOwnerId === (int)$currentUserId;
}

/**
 * Can the current user edit this goat's farmer-editable fields?
 * 
 * FIXED: Admin, Manager, and the owning Farmer can edit.
 */
function canEditGoat($currentRole, $currentUserId, $goatOwnerId) {
    // Admin can edit any goat
    if ($currentRole === 'admin') {
        return true;
    }
    // Manager can edit any goat (monitoring/verification role)
    if ($currentRole === 'manager') {
        return true;
    }
    // User/Farmer can ONLY edit their OWN goats
    if ($currentRole === 'user') {
        return (int)$goatOwnerId === (int)$currentUserId;
    }
    return false;
}

/**
 * Can the current user update just the goat's health status?
 * 
 * FIXED: Admin, Manager, and the owning Farmer can update health.
 */
function canUpdateHealthStatus($currentRole, $currentUserId, $goatOwnerId) {
    // Admin and Manager can update health of any goat
    if ($currentRole === 'admin' || $currentRole === 'manager') {
        return true;
    }
    // User/Farmer can ONLY update health of their OWN goats
    if ($currentRole === 'user') {
        return (int)$goatOwnerId === (int)$currentUserId;
    }
    return false;
}

/**
 * Can the current user change goat ownership (user_id)?
 * Administrator only.
 */
function canCorrectOwnership($currentRole) {
    return $currentRole === 'admin';
}

/**
 * Can the current user register (create) new goats?
 */
function canRegisterGoat($currentRole) {
    return $currentRole === 'user' || $currentRole === 'admin';
}