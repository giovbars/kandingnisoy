<?php
require_once '../../config/config.php';
require_once '../../config/functions.php';
require_once '../../includes/activity-logger.php';
requireRole('manager');

// Get statistics for manager
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'user' AND is_verified = 0");
$unverifiedUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get activity statistics for manager and users only
$stmt = $pdo->query("
    SELECT DATE(al.created_at) as date, COUNT(*) as count 
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    WHERE al.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    AND (u.role IN ('manager', 'user') OR u.role IS NULL)
    GROUP BY DATE(al.created_at)
    ORDER BY date ASC
");
$dailyActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get action distribution for manager and users
$stmt = $pdo->query("
    SELECT al.action, COUNT(*) as count 
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    WHERE al.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    AND (u.role IN ('manager', 'user') OR u.role IS NULL)
    GROUP BY al.action 
    ORDER BY count DESC 
    LIMIT 10
");
$actionStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$title = 'Manager Dashboard';
renderHeader($title);
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">