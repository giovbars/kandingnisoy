<?php
/**
 * Goat Inventory Activity Logger
 *
 * Reuses the existing `activity_logs` table (the same table used by
 * includes/activity-logger.php) instead of creating a duplicate audit
 * system. Goat-related entries are distinguished by the `goat_id` and
 * `details` columns added in db/migration_goat_inventory.sql.
 *
 * This file is purely additive: it does not modify activity-logger.php
 * or the existing logActivity() function.
 */

/**
 * Log a goat-related activity for audit purposes.
 *
 * @param PDO $pdo Database connection
 * @param int $user_id The user performing the action (from session, never trusted input)
 * @param string $email The email of the user performing the action
 * @param string $action Action performed, e.g. 'goat_registered', 'goat_updated',
 *                        'goat_health_status_changed', 'goat_status_changed',
 *                        'goat_marked_sold', 'goat_marked_deceased',
 *                        'goat_ownership_corrected', 'goat_admin_correction'
 * @param string $status 'success' or 'failed'
 * @param string|null $goat_id The Goat ID (business/display identifier, e.g. "G-0002").
 *                              NOTE: this is unique per-farmer only, not globally unique.
 * @param array $details Structured context for the audit trail, e.g.
 *                        ['field' => 'health_status', 'previous' => 'unknown', 'new' => 'healthy']
 *                        or ['previous_owner' => 3, 'new_owner' => 5]
 * @param int|null $goat_pk The goat's actual database primary key (goats.id).
 *                           FIXED: pass this whenever available so history lookups
 *                           can be scoped to the exact goat, not just its display code.
 * @return bool True on success, false on failure
 */
function logGoatActivity($pdo, $user_id, $email, $action, $status = 'success', $goat_id = null, $details = [], $goat_pk = null) {
    try {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        if (strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }

        $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);

        $detailsJson = !empty($details) ? json_encode($details) : null;

        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, goat_id, goat_pk, email, action, status, details, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([$user_id, $goat_id, $goat_pk, $email, $action, $status, $detailsJson, $ip, $user_agent]);

    } catch (PDOException $e) {
        error_log("Goat Activity Log Error: " . $e->getMessage());
        return false;
    }
}

?>