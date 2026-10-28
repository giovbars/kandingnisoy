<?php
/**
 * Schema self-check.
 * The imported soy_php_pdo.sql is older than the code, so a few tables/columns
 * the app uses are missing. On every request this runs ONE cheap query; only if
 * something is missing does it add it (additive only: never drops or changes data).
 */
function gfSyncSchema(PDO $pdo): void {
    try {
        // users + goats must already be imported; if not, do nothing.
        $have = $pdo->query(
            "SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND (
                (table_name = 'users' AND column_name IN
                    ('phone_number','barangay','municipality','sms_opt_in','remember_token','token_expiry'))
                OR (table_name = 'goats' AND column_name = 'photo_path')
                OR (table_name = 'advisory_logs' AND column_name = 'id')
                OR (table_name = 'advisory_recipients' AND column_name = 'id')
             )"
        )->fetchColumn();
        if ((int)$have >= 9) return;   // everything already there

        $base = (int)$pdo->query(
            "SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name IN ('users','goats')"
        )->fetchColumn();
        if ($base < 2) return;         // database not imported yet

        $need = [
            'users' => [
                'phone_number'   => "VARCHAR(20) NULL",
                'barangay'       => "VARCHAR(100) NULL",
                'municipality'   => "VARCHAR(100) NULL DEFAULT 'Manolo Fortich'",
                'sms_opt_in'     => "TINYINT(1) NOT NULL DEFAULT 1",
                'remember_token' => "VARCHAR(64) NULL",
                'token_expiry'   => "DATETIME NULL",
            ],
            'goats' => [
                'photo_path'     => "VARCHAR(255) NULL",
            ],
        ];
        foreach ($need as $table => $cols) {
            $q = $pdo->prepare("SELECT column_name FROM information_schema.columns
                                WHERE table_schema = DATABASE() AND table_name = ?");
            $q->execute([$table]);
            $existing = array_map('strtolower', $q->fetchAll(PDO::FETCH_COLUMN));
            foreach ($cols as $col => $ddl) {
                if (!in_array(strtolower($col), $existing, true)) {
                    $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$col` $ddl");
                }
            }
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS advisory_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            advisory_type VARCHAR(50) NOT NULL,
            priority VARCHAR(20) NOT NULL DEFAULT 'medium',
            message TEXT NOT NULL,
            trigger_data TEXT NULL,
            recipient_count INT NOT NULL DEFAULT 0,
            success_count INT NOT NULL DEFAULT 0,
            failed_count INT NOT NULL DEFAULT 0,
            sent_by INT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_adv_created (created_at),
            INDEX idx_adv_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS advisory_recipients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            advisory_id INT NOT NULL,
            user_id INT NULL,
            phone_number VARCHAR(20) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            provider_response TEXT NULL,
            sent_at DATETIME NULL,
            INDEX idx_rec_advisory (advisory_id),
            INDEX idx_rec_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    } catch (Throwable $e) {
        error_log('Schema sync failed: ' . $e->getMessage());
    }
}
