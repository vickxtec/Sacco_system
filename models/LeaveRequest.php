<?php
require_once __DIR__ . '/../config/db.php';

class LeaveRequest {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    public function getAll($search = '', $status = '') {
        $sql = "SELECT lr.*, u.full_name as applicant_name, p.full_name as processed_by_name
                FROM leave_requests lr
                JOIN users u ON lr.user_id = u.id
                LEFT JOIN users p ON lr.processed_by = p.id
                WHERE 1=1";
        $params = [];
        $types = '';

        if (!empty($search)) {
            $sql .= " AND (u.full_name LIKE ? OR u.username LIKE ? OR lr.leave_type LIKE ? OR lr.status LIKE ? )";
            $like = "%$search%";
            $params = [$like, $like, $like, $like];
            $types = 'ssss';
        }
        if (!empty($status)) {
            $sql .= " AND lr.status = ?";
            $params[] = $status;
            $types .= 's';
        }
        $sql .= " ORDER BY lr.applied_date DESC";

        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT lr.*, u.full_name as applicant_name, u.username as applicant_username, p.full_name as processed_by_name FROM leave_requests lr JOIN users u ON lr.user_id = u.id LEFT JOIN users p ON lr.processed_by = p.id WHERE lr.id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($data) {
        $required = ['user_id', 'leave_type', 'start_date', 'end_date', 'reason'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'error' => "Field '$field' is required"];
            }
        }

        $status = 'Pending';
        $stmt = $this->conn->prepare("INSERT INTO leave_requests (user_id, leave_type, start_date, end_date, reason, status, applied_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('issssss', $data['user_id'], $data['leave_type'], $data['start_date'], $data['end_date'], $data['reason'], $status, $data['applied_date']);
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $this->conn->insert_id, 'message' => 'Leave request submitted'];
        }
        return ['success' => false, 'error' => $this->conn->error];
    }

    public function updateStatus($id, $status, $processed_by = null) {
        $processed_date = date('Y-m-d');
        $stmt = $this->conn->prepare("UPDATE leave_requests SET status = ?, processed_by = ?, processed_date = ? WHERE id = ?");
        $stmt->bind_param('sisi', $status, $processed_by, $processed_date, $id);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Leave status updated'];
        }
        return ['success' => false, 'error' => $this->conn->error];
    }

    public function getStats() {
        $result = $this->conn->query("SELECT COUNT(*) as total, SUM(status='Pending') as pending, SUM(status='Approved') as approved, SUM(status='Rejected') as rejected FROM leave_requests");
        return $result->fetch_assoc();
    }
}
