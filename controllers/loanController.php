<?php
require_once __DIR__ . '/../models/Loan.php';
require_once __DIR__ . '/../models/Member.php';
require_once __DIR__ . '/../models/Saving.php';
require_once __DIR__ . '/../models/Setting.php';

class LoanController {
    private $loan;
    private $member;
    private $saving;
    private $setting;

    public function __construct() {
        $this->loan = new Loan();
        $this->member = new Member();
        $this->saving = new Saving();
        $this->setting = new Setting();
    }

    public function handleRequest() {
        $action = $_GET['action'] ?? 'list';

        switch ($action) {
            case 'list': return $this->listLoans();
            case 'get': return $this->getLoan();
            case 'repayments': return $this->getRepayments();
            case 'apply': return $this->applyLoan();
            case 'status': return $this->updateStatus();
            case 'repay': return $this->repay();
            case 'calculate': return $this->calculate();
            case 'stats': return $this->getStats();
            default: return ['error' => 'Invalid action'];
        }
    }

    private function listLoans() {
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $limit = intval($_GET['limit'] ?? 0);
        return ['success' => true, 'data' => $this->loan->getAll($search, $status, $limit)];
    }

    private function getLoan() {
        $id = $_GET['id'] ?? 0;
        $data = $this->loan->getById($id);
        if ($data) return ['success' => true, 'data' => $data];
        return ['success' => false, 'error' => 'Loan not found'];
    }

    private function getRepayments() {
        $id = $_GET['id'] ?? 0;
        return ['success' => true, 'data' => $this->loan->getRepayments($id)];
    }

    private function applyLoan() {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $required = ['member_id', 'loan_type', 'principal', 'interest_rate', 'term_months', 'guarantor_id', 'collateral_security'];
        foreach ($required as $f) {
            if (empty($data[$f]) && $data[$f] !== '0') {
                return ['success' => false, 'error' => "Field '$f' is required"];
            }
        }

        $data['member_id'] = intval($data['member_id']);
        $data['principal'] = floatval($data['principal']);
        $data['interest_rate'] = floatval($data['interest_rate']);
        $data['term_months'] = intval($data['term_months']);
        $data['guarantor_id'] = intval($data['guarantor_id']);
        $data['collateral_security'] = trim($data['collateral_security']);
        $data['applied_date'] = $data['applied_date'] ?? date('Y-m-d');
        $data['purpose'] = $data['purpose'] ?? '';

        if ($data['member_id'] <= 0) {
            return ['success' => false, 'error' => 'Member selection is required'];
        }
        if ($data['principal'] <= 0) {
            return ['success' => false, 'error' => 'Loan amount must be greater than zero'];
        }
        if ($data['interest_rate'] <= 0) {
            return ['success' => false, 'error' => 'Interest rate must be greater than zero'];
        }
        if ($data['term_months'] <= 0) {
            return ['success' => false, 'error' => 'Loan term must be greater than zero'];
        }
        if ($data['guarantor_id'] <= 0) {
            return ['success' => false, 'error' => 'A guarantor must be provided'];
        }
        if ($data['collateral_security'] === '') {
            return ['success' => false, 'error' => 'Collateral security details are required'];
        }

        $maxLoanMultiplier = floatval($this->setting->get('max_loan_multiplier') ?? 3);
        $minSavingsRatio = floatval($this->setting->get('min_savings_ratio') ?? 0.33);
        $maxLoanTerm = intval($this->setting->get('max_loan_term_months') ?? 60);

        if ($data['term_months'] > $maxLoanTerm) {
            return ['success' => false, 'error' => "Loan term cannot exceed $maxLoanTerm months"];
        }

        $member = $this->member->getById($data['member_id']);
        if (!$member || strtolower($member['status']) !== 'active') {
            return ['success' => false, 'error' => 'Member must exist and be active'];
        }

        $totalSavings = $this->saving->getTotalSavingsByMember($data['member_id']);
        $requiredSavings = max(ceil($data['principal'] * $minSavingsRatio), 0);
        if ($totalSavings < $requiredSavings) {
            return ['success' => false, 'error' => "Member savings must be at least KES $requiredSavings to support this loan"]; 
        }
        if ($data['principal'] > ($totalSavings * $maxLoanMultiplier)) {
            return ['success' => false, 'error' => "Loan amount cannot exceed {$maxLoanMultiplier} times the member's total savings"];
        }

        if ($this->loan->hasOpenLoan($data['member_id'])) {
            return ['success' => false, 'error' => 'Member already has an unpaid or pending loan'];
        }

        return $this->loan->apply($data);
    }

    private function updateStatus() {
        $id = $_GET['id'] ?? 0;
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $status = $data['status'] ?? '';
        if (empty($status)) return ['success' => false, 'error' => 'Status required'];
        return $this->loan->updateStatus($id, $status, $data);
    }

    private function repay() {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $loan_id = $data['loan_id'] ?? ($_GET['id'] ?? 0);
        $amount = floatval($data['amount'] ?? 0);
        if ($amount <= 0) return ['success' => false, 'error' => 'Invalid amount'];
        return $this->loan->repay($loan_id, $amount, $_SESSION['user_id'] ?? null);
    }

    private function calculate() {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_GET;
        $principal = floatval($data['principal'] ?? 0);
        $rate = floatval($data['interest_rate'] ?? 0);
        $months = intval($data['term_months'] ?? 0);
        if ($principal <= 0 || $rate <= 0 || $months <= 0) return ['success' => false, 'error' => 'Invalid parameters'];
        $monthly = $this->loan->calculateMonthlyPayment($principal, $rate, $months);
        return ['success' => true, 'monthly_payment' => $monthly, 'total_payable' => round($monthly * $months, 2), 'total_interest' => round(($monthly * $months) - $principal, 2)];
    }

    private function getStats() {
        return ['success' => true, 'data' => $this->loan->getStats()];
    }
}