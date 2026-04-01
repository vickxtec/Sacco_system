<?php
require_once __DIR__ . '/../config/db.php';

class Saving {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    public function getAll($search = '', $limit = 0) {
        $sql = "SELECT s.*, m.full_name, m.member_no, m.phone 
                FROM savings s 
                JOIN members m ON s.member_id = m.id 
                WHERE 1=1";
        $params = [];
        $types = '';

        if (!empty($search)) {
            $sql .= " AND (m.full_name LIKE ? OR s.account_no LIKE ? OR m.member_no LIKE ?)";
            $like = "%$search%";
            $params = [$like, $like, $like];
            $types = 'sss';
        }
        $sql .= " ORDER BY s.opened_date DESC";
        if ($limit > 0) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
            $types .= 'i';
        }

        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT s.*, m.full_name, m.member_no FROM savings s JOIN members m ON s.member_id = m.id WHERE s.id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getTotalSavingsByMember($member_id) {
        $stmt = $this->conn->prepare("SELECT COALESCE(SUM(balance), 0) as total FROM savings WHERE member_id = ? AND status = 'Active'");
        $stmt->bind_param('i', $member_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return floatval($result['total'] ?? 0);
    }

    public function getTransactions($savings_id, $limit = 20) {
        $stmt = $this->conn->prepare("SELECT st.*, u.full_name as created_by_name FROM savings_transactions st LEFT JOIN users u ON st.created_by = u.id WHERE st.savings_id = ? ORDER BY st.transaction_date DESC LIMIT ?");
        $stmt->bind_param('ii', $savings_id, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function createAccount($data) {
        $account_no = $this->generateAccountNo();
        $stmt = $this->conn->prepare("INSERT INTO savings (member_id, account_no, account_type, balance, interest_rate, status, opened_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('issddss', $data['member_id'], $account_no, $data['account_type'], $data['initial_deposit'], $data['interest_rate'], $data['status'], $data['opened_date']);
        if ($stmt->execute()) {
            $savings_id = $this->conn->insert_id;
            if ($data['initial_deposit'] > 0) {
                $this->recordTransaction($savings_id, 'Deposit', $data['initial_deposit'], $data['initial_deposit'], 'Initial deposit', $data['created_by'] ?? null);
            }
            return ['success' => true, 'id' => $savings_id, 'account_no' => $account_no];
        }
        return ['success' => false, 'error' => $this->conn->error];
    }

    public function deposit($savings_id, $amount, $notes, $created_by) {
        $account = $this->getById($savings_id);
        if (!$account) return ['success' => false, 'error' => 'Account not found'];

        $new_balance = $account['balance'] + $amount;
        $this->conn->begin_transaction();
        try {
            $stmt = $this->conn->prepare("UPDATE savings SET balance = ? WHERE id = ?");
            $stmt->bind_param('di', $new_balance, $savings_id);
            $stmt->execute();
            $this->recordTransaction($savings_id, 'Deposit', $amount, $new_balance, $notes, $created_by);
            $this->conn->commit();
            return ['success' => true, 'new_balance' => $new_balance];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function withdraw($savings_id, $amount, $notes, $created_by) {
        $account = $this->getById($savings_id);
        if (!$account) return ['success' => false, 'error' => 'Account not found'];
        if ($account['balance'] < $amount) return ['success' => false, 'error' => 'Insufficient balance'];

        $new_balance = $account['balance'] - $amount;
        $this->conn->begin_transaction();
        try {
            $stmt = $this->conn->prepare("UPDATE savings SET balance = ? WHERE id = ?");
            $stmt->bind_param('di', $new_balance, $savings_id);
            $stmt->execute();
            $this->recordTransaction($savings_id, 'Withdrawal', $amount, $new_balance, $notes, $created_by);
            $this->conn->commit();
            return ['success' => true, 'new_balance' => $new_balance];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getStats() {
        $result = $this->conn->query("SELECT COUNT(*) as total_accounts, SUM(balance) as total_savings FROM savings WHERE status='Active'");
        $stats = $result->fetch_assoc();

        $result = $this->conn->query("SELECT SUM(amount) as deposits_today FROM savings_transactions WHERE transaction_type='Deposit' AND DATE(transaction_date)=CURDATE()");
        $stats['deposits_today'] = $result->fetch_assoc()['deposits_today'] ?? 0;

        $result = $this->conn->query("SELECT SUM(amount) as withdrawals_today FROM savings_transactions WHERE transaction_type='Withdrawal' AND DATE(transaction_date)=CURDATE()");
        $stats['withdrawals_today'] = $result->fetch_assoc()['withdrawals_today'] ?? 0;

        return $stats;
    }

    private function recordTransaction($savings_id, $type, $amount, $balance_after, $notes, $created_by) {
        $ref = 'TXN' . strtoupper(uniqid());
        $stmt = $this->conn->prepare("INSERT INTO savings_transactions (savings_id, transaction_type, amount, balance_after, reference, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('isddss' . ($created_by ? 'i' : 's'), $savings_id, $type, $amount, $balance_after, $ref, $notes, $created_by);
        $stmt->execute();
    }

    private function generateAccountNo() {
        $result = $this->conn->query("SELECT MAX(CAST(SUBSTRING(account_no, 4) AS UNSIGNED)) as max_no FROM savings");
        $row = $result->fetch_assoc();
        $next = ($row['max_no'] ?? 0) + 1;
        return 'SAV' . str_pad($next, 6, '0', STR_PAD_LEFT);
    }
}