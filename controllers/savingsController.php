<?php
require_once __DIR__ . '/../models/Saving.php';

class SavingsController {
    private $saving;

    public function __construct() {
        $this->saving = new Saving();
    }

    public function handleRequest() {
        $action = $_GET['action'] ?? 'list';

        switch ($action) {
            case 'list': return $this->listAccounts();
            case 'get': return $this->getAccount();
            case 'transactions': return $this->getTransactions();
            case 'create': return $this->createAccount();
            case 'deposit': return $this->deposit();
            case 'withdraw': return $this->withdraw();
            case 'stats': return $this->getStats();
            default: return ['error' => 'Invalid action'];
        }
    }

    private function listAccounts() {
        $search = $_GET['search'] ?? '';
        $limit = intval($_GET['limit'] ?? 0);
        return ['success' => true, 'data' => $this->saving->getAll($search, $limit)];
    }

    private function getAccount() {
        $id = $_GET['id'] ?? 0;
        $data = $this->saving->getById($id);
        if ($data) return ['success' => true, 'data' => $data];
        return ['success' => false, 'error' => 'Account not found'];
    }

    private function getTransactions() {
        $id = $_GET['id'] ?? 0;
        return ['success' => true, 'data' => $this->saving->getTransactions($id)];
    }

    private function createAccount() {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $data['initial_deposit'] = $data['initial_deposit'] ?? 0;
        $data['interest_rate'] = $data['interest_rate'] ?? 3.5;
        $data['status'] = $data['status'] ?? 'Active';
        $data['opened_date'] = $data['opened_date'] ?? date('Y-m-d');
        $data['created_by'] = $_SESSION['user_id'] ?? null;
        return $this->saving->createAccount($data);
    }

    private function deposit() {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = $data['savings_id'] ?? ($_GET['id'] ?? 0);
        $amount = floatval($data['amount'] ?? 0);
        if ($amount <= 0) return ['success' => false, 'error' => 'Invalid amount'];
        return $this->saving->deposit($id, $amount, $data['notes'] ?? '', $_SESSION['user_id'] ?? null);
    }

    private function withdraw() {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = $data['savings_id'] ?? ($_GET['id'] ?? 0);
        $amount = floatval($data['amount'] ?? 0);
        if ($amount <= 0) return ['success' => false, 'error' => 'Invalid amount'];
        return $this->saving->withdraw($id, $amount, $data['notes'] ?? '', $_SESSION['user_id'] ?? null);
    }

    private function getStats() {
        return ['success' => true, 'data' => $this->saving->getStats()];
    }
}