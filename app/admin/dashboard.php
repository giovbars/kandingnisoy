<?php
require_once '../../config/config.php';
require_once '../../config/functions.php';
require_once '../../includes/activity-logger.php';
requireRole('admin');

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'admin'");
$totalAdmins = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'manager'");
$totalManagers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$totalRegularUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE is_verified = 0");
$unverifiedUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM activity_logs 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$dailyActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get action distribution
$stmt = $pdo->query("
    SELECT action, COUNT(*) as count 
    FROM activity_logs 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY action 
    ORDER BY count DESC 
    LIMIT 10
");
$actionStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get role-based activity
$stmt = $pdo->query("
    SELECT 
        COALESCE(u.role, 'unknown') as role, 
        COUNT(*) as count 
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    WHERE al.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY role
");
$roleStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

