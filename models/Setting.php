<?php
require_once __DIR__ . '/../config/db.php';

class Setting {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    public function getAll() {
        $stmt = $this->conn->prepare("SELECT setting_key, setting_value, description FROM settings ORDER BY setting_key ASC");
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        if (empty($rows)) {
            $this->initializeDefaults();
            return $this->getAll();
        }
        return $rows;
    }

    public function get($key) {
        $stmt = $this->conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? $row['setting_value'] : null;
    }

    public function set($key, $value) {
        $stmt = $this->conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->bind_param('ss', $key, $value);
        if ($stmt->execute()) {
            return ['success' => true];
        }
        return ['success' => false, 'error' => $this->conn->error];
    }

    private function initializeDefaults() {
        $defaults = [
            'default_interest_rate' => '12.00',
            'max_loan_multiplier' => '3',
            'min_savings_ratio' => '0.33',
            'max_loan_term_months' => '60',
            'loan_policy' => 'Loans are approved based on member savings, guarantor validation and available collateral.',
        ];
        foreach ($defaults as $key => $value) {
            $stmt = $this->conn->prepare("INSERT IGNORE INTO settings (setting_key, setting_value, description) VALUES (?, ?, '')");
            $stmt->bind_param('ss', $key, $value);
            $stmt->execute();
        }
    }
}
