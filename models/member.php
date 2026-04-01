<?php
require_once __DIR__ . '/../config/db.php';

class Member {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    public function getAll($search = '', $status = '') {
        $sql = "SELECT * FROM members WHERE 1=1";
        $params = [];
        $types = '';

        if (!empty($search)) {
            $sql .= " AND (full_name LIKE ? OR member_no LIKE ? OR phone LIKE ? OR id_number LIKE ?)";
            $like = "%$search%";
            $params = array_merge($params, [$like, $like, $like, $like]);
            $types .= 'ssss';
        }
        if (!empty($status)) {
            $sql .= " AND status = ?";
            $params[] = $status;
            $types .= 's';
        }
        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM members WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($data) {
        $member_no = $this->generateMemberNo();
        $stmt = $this->conn->prepare("INSERT INTO members (member_no, full_name, id_number, phone, email, address, dob, gender, status, joined_date, photo, id_front, id_back, signature, share_capital) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssssssssssssd', 
            $member_no, $data['full_name'], $data['id_number'], $data['phone'],
            $data['email'], $data['address'], $data['dob'], $data['gender'],
            $data['status'], $data['joined_date'], $data['photo'], $data['id_front'],
            $data['id_back'], $data['signature'], $data['share_capital']
        );
        if ($stmt->execute()) {
            return [
                'success' => true,
                'id' => $this->conn->insert_id,
                'member_no' => $member_no,
                'message' => 'Successfully registered'
            ];
        }
        return ['success' => false, 'error' => $this->conn->error];
    }

    public function update($id, $data) {
        $stmt = $this->conn->prepare("UPDATE members SET full_name=?, id_number=?, phone=?, email=?, address=?, dob=?, gender=?, status=?, photo=?, id_front=?, id_back=?, signature=?, share_capital=? WHERE id=?");
        $stmt->bind_param('ssssssssssssdi',
            $data['full_name'], $data['id_number'], $data['phone'],
            $data['email'], $data['address'], $data['dob'], $data['gender'],
            $data['status'], $data['photo'], $data['id_front'], $data['id_back'],
            $data['signature'], $data['share_capital'], $id
        );
        if ($stmt->execute()) return ['success' => true];
        return ['success' => false, 'error' => $this->conn->error];
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM members WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) return ['success' => true];
        return ['success' => false, 'error' => $this->conn->error];
    }

    public function getStats() {
        $stats = [];
        $result = $this->conn->query("SELECT COUNT(*) as total, SUM(share_capital) as total_shares FROM members");
        $row = $result->fetch_assoc();
        $stats['total_members'] = $row['total'];
        $stats['total_shares'] = $row['total_shares'] ?? 0;

        $result = $this->conn->query("SELECT COUNT(*) as active FROM members WHERE status='Active'");
        $stats['active_members'] = $result->fetch_assoc()['active'];

        $result = $this->conn->query("SELECT COUNT(*) as new FROM members WHERE MONTH(joined_date)=MONTH(NOW()) AND YEAR(joined_date)=YEAR(NOW())");
        $stats['new_this_month'] = $result->fetch_assoc()['new'];

        return $stats;
    }

    private function generateMemberNo() {
        $result = $this->conn->query("SELECT MAX(CAST(SUBSTRING(member_no, 4) AS UNSIGNED)) as max_no FROM members");
        $row = $result->fetch_assoc();
        $next = ($row['max_no'] ?? 0) + 1;
        return 'MEM' . str_pad($next, 3, '0', STR_PAD_LEFT);
    }
}