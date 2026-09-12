<?php
require_once '../config/config.php';
require_once '../config/functions.php';

header('Content-Type: application/json');

// Only Admin can view the activity log feed. Since this is an AJAX endpoint
// (called by the DataTable on app/admin/dashboard.php), we return a JSON
// error instead of using requireRole(), which would die() with plain text
// and break the DataTable's JSON parsing.
if (!isLoggedIn() || !hasRole('admin')) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Admin role required.', 'data' => []]);
    exit;
}

try {
    // Join to users so we can show which role performed each action.
    // LEFT JOIN because failed login attempts have no matching user_id
    // (e.g. a bad email/username that doesn't exist in the users table).
    $stmt = $pdo->query("
        SELECT
            al.id,
            al.email,
            u.role AS user_role,
            al.action,
            al.status,
            al.created_at
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC
        LIMIT 500
    ");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // DataTables (client-side mode, serverSide: false) expects the rows
    // wrapped in a "data" key.
    echo json_encode(['data' => $logs]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load activity logs.', 'data' => []]);
}
