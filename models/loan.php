<?php
require_once __DIR__ . '/../config/db.php';

class Loan {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    public function getAll($search = '', $status = '', $limit = 0) {
        $sql = "SELECT l.*, m.full_name, m.member_no, m.phone,
                       g.full_name as guarantor_name
                FROM loans l 
                JOIN members m ON l.member_id = m.id 
                LEFT JOIN members g ON l.guarantor_id = g.id
                WHERE 1=1";
        $params = [];
        $types = '';

        if (!empty($search)) {
            $sql .= " AND (m.full_name LIKE ? OR l.loan_no LIKE ? OR m.member_no LIKE ?)";
            $like = "%$search%";
            $params = [$like, $like, $like];
            $types = 'sss';
        }
        if (!empty($status)) {
            $sql .= " AND l.status = ?";
            $params[] = $status;
            $types .= 's';
        }
        $sql .= " ORDER BY l.applied_date DESC";
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
        $stmt = $this->conn->prepare("SELECT l.*, m.full_name, m.member_no, m.phone, g.full_name as guarantor_name FROM loans l JOIN members m ON l.member_id = m.id LEFT JOIN members g ON l.guarantor_id = g.id WHERE l.id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function hasOpenLoan($member_id) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM loans WHERE member_id = ? AND status IN ('Pending','Approved','Active','Defaulted')");
        $stmt->bind_param('i', $member_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return intval($row['count'] ?? 0) > 0;
    }

    public function getRepayments($loan_id) {
        $stmt = $this->conn->prepare("SELECT lr.*, u.full_name as created_by_name FROM loan_repayments lr LEFT JOIN users u ON lr.created_by = u.id WHERE lr.loan_id = ? ORDER BY lr.payment_date DESC");
        $stmt->bind_param('i', $loan_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function apply($data) {
        $loan_no = $this->generateLoanNo();
        $monthly = $this->calculateMonthlyPayment($data['principal'], $data['interest_rate'], $data['term_months']);
        $total = $monthly * $data['term_months'];
        $guarantor_id = isset($data['guarantor_id']) && $data['guarantor_id'] !== null && $data['guarantor_id'] !== '' ? intval($data['guarantor_id']) : null;

        if ($guarantor_id === null) {
            $stmt = $this->conn->prepare("INSERT INTO loans (member_id, loan_no, loan_type, principal, interest_rate, term_months, monthly_payment, total_payable, balance, status, applied_date, purpose, collateral_security, guarantor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?, ?, NULL)");
            $stmt->bind_param('issddiiddsss',
                $data['member_id'], $loan_no, $data['loan_type'],
                $data['principal'], $data['interest_rate'], $data['term_months'],
                $monthly, $total, $data['principal'],
                $data['applied_date'], $data['purpose'], $data['collateral_security']
            );
        } else {
            $stmt = $this->conn->prepare("INSERT INTO loans (member_id, loan_no, loan_type, principal, interest_rate, term_months, monthly_payment, total_payable, balance, status, applied_date, purpose, collateral_security, guarantor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?, ?, ?)");
            $stmt->bind_param('issddiiddsssi',
                $data['member_id'], $loan_no, $data['loan_type'],
                $data['principal'], $data['interest_rate'], $data['term_months'],
                $monthly, $total, $data['principal'],
                $data['applied_date'], $data['purpose'], $data['collateral_security'], $guarantor_id
            );
        }
        if ($stmt->execute()) return ['success' => true, 'id' => $this->conn->insert_id, 'loan_no' => $loan_no];
        return ['success' => false, 'error' => $this->conn->error];
    }

    public function updateStatus($id, $status, $extra = []) {
        $fields = 'status=?';
        $params = [$status];
        $types = 's';

        if ($status === 'Approved' || $status === 'Active') {
            $fields .= ', approved_date=?';
            $params[] = date('Y-m-d');
            $types .= 's';
        }
        if ($status === 'Active') {
            $fields .= ', disbursed_date=?, due_date=?';
            $months = $extra['term_months'] ?? 12;
            $params[] = date('Y-m-d');
            $params[] = date('Y-m-d', strtotime("+$months months"));
            $types .= 'ss';
        }

        $params[] = $id;
        $types .= 'i';
        $stmt = $this->conn->prepare("UPDATE loans SET $fields WHERE id=?");
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) return ['success' => true];
        return ['success' => false, 'error' => $this->conn->error];
    }

    public function repay($loan_id, $amount, $created_by) {
        $loan = $this->getById($loan_id);
        if (!$loan) return ['success' => false, 'error' => 'Loan not found'];
        if ($loan['status'] !== 'Active') return ['success' => false, 'error' => 'Loan is not active'];

        $interest = round(($loan['balance'] * ($loan['interest_rate'] / 100)) / 12, 2);
        $principal_paid = $amount - $interest;
        $new_balance = max(0, $loan['balance'] - $principal_paid);
        $new_status = $new_balance <= 0 ? 'Completed' : 'Active';

        $this->conn->begin_transaction();
        try {
            $ref = 'RPY' . strtoupper(uniqid());
            $stmt = $this->conn->prepare("INSERT INTO loan_repayments (loan_id, amount, principal_paid, interest_paid, balance_after, reference, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('iddddsi', $loan_id, $amount, $principal_paid, $interest, $new_balance, $ref, $created_by);
            $stmt->execute();

            $stmt2 = $this->conn->prepare("UPDATE loans SET balance=?, status=? WHERE id=?");
            $stmt2->bind_param('dsi', $new_balance, $new_status, $loan_id);
            $stmt2->execute();

            $this->conn->commit();
            return ['success' => true, 'new_balance' => $new_balance, 'status' => $new_status];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getStats() {
        $result = $this->conn->query("SELECT COUNT(*) as active_loans, SUM(balance) as total_outstanding FROM loans WHERE status='Active'");
        $stats = $result->fetch_assoc();

        $result = $this->conn->query("SELECT COUNT(*) as pending FROM loans WHERE status='Pending'");
        $stats['pending_loans'] = $result->fetch_assoc()['pending'];

        $result = $this->conn->query("SELECT SUM(principal) as disbursed FROM loans WHERE status IN ('Active','Completed')");
        $stats['total_disbursed'] = $result->fetch_assoc()['disbursed'] ?? 0;

        $result = $this->conn->query("SELECT SUM(amount) as total_repayments FROM loan_repayments");
        $stats['total_repayments'] = $result->fetch_assoc()['total_repayments'] ?? 0;

        $result = $this->conn->query("SELECT COUNT(*) as defaulted FROM loans WHERE status='Defaulted'");
        $stats['defaulted'] = $result->fetch_assoc()['defaulted'];

        return $stats;
    }

    public function calculateMonthlyPayment($principal, $rate, $months) {
        $monthly_rate = ($rate / 100) / 12;
        if ($monthly_rate == 0) return $principal / $months;
        return round($principal * ($monthly_rate * pow(1 + $monthly_rate, $months)) / (pow(1 + $monthly_rate, $months) - 1), 2);
    }

    private function generateLoanNo() {
        $result = $this->conn->query("SELECT MAX(CAST(SUBSTRING(loan_no, 3) AS UNSIGNED)) as max_no FROM loans");
        $row = $result->fetch_assoc();
        $next = ($row['max_no'] ?? 0) + 1;
        return 'LN' . str_pad($next, 6, '0', STR_PAD_LEFT);
    }
}