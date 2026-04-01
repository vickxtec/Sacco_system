<?php
require_once __DIR__ . '/../models/LeaveRequest.php';

class LeaveController {
    private $leave;

    public function __construct() {
        $this->leave = new LeaveRequest();
    }

    public function handleRequest() {
        $action = $_GET['action'] ?? 'list';
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            return ['success' => false, 'error' => 'Admin access required'];
        }

        switch ($action) {
            case 'list': return $this->listRequests();
            case 'get': return $this->getRequest();
            case 'create': return $this->createRequest();
            case 'status': return $this->updateStatus();
            case 'stats': return $this->getStats();
            default: return ['error' => 'Invalid action'];
        }
    }

    private function listRequests() {
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        return ['success' => true, 'data' => $this->leave->getAll($search, $status)];
    }

    private function getRequest() {
        $id = intval($_GET['id'] ?? 0);
        $data = $this->leave->getById($id);
        if ($data) return ['success' => true, 'data' => $data];
        return ['success' => false, 'error' => 'Leave request not found'];
    }

    private function createRequest() {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $data['applied_date'] = $data['applied_date'] ?? date('Y-m-d');
        return $this->leave->create($data);
    }

    private function updateStatus() {
        $id = intval($_GET['id'] ?? 0);
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $status = $data['status'] ?? '';
        if (!in_array($status, ['Approved', 'Rejected'])) {
            return ['success' => false, 'error' => 'Invalid status'];
        }
        return $this->leave->updateStatus($id, $status, $_SESSION['user_id'] ?? null);
    }

    private function getStats() {
        return ['success' => true, 'data' => $this->leave->getStats()];
    }
}
