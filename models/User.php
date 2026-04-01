<?php
require_once __DIR__ . '/../config/db.php';

class User {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    public function getAll($search = '', $role = '') {
        $sql = "SELECT id, username, full_name, role, created_at FROM users WHERE 1=1";
        $params = [];
        $types = '';

        if (!empty($search)) {
            $sql .= " AND (username LIKE ? OR full_name LIKE ? OR role LIKE ?)";
            $like = "%$search%";
            $params = [$like, $like, $like];
            $types = 'sss';
        }
        if (!empty($role)) {
            $sql .= " AND role = ?";
            $params[] = $role;
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
        $stmt = $this->conn->prepare("SELECT id, username, full_name, role, created_at FROM users WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($data) {
        if (empty($data['username']) || empty($data['full_name']) || empty($data['password']) || empty($data['role'])) {
            return ['success' => false, 'error' => 'Username, full name, password and role are required'];
        }

        $username = trim($data['username']);
        $full_name = trim($data['full_name']);
        $role = trim($data['role']);
        $password = $data['password'];

        $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            return ['success' => false, 'error' => 'Username already exists'];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $insert = $this->conn->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
        $insert->bind_param('ssss', $username, $hash, $full_name, $role);
        if ($insert->execute()) {
            return ['success' => true, 'id' => $this->conn->insert_id, 'message' => 'User account created successfully'];
        }
        return ['success' => false, 'error' => $this->conn->error];
    }

    public function update($id, $data) {
        if (empty($data['full_name']) || empty($data['role'])) {
            return ['success' => false, 'error' => 'Full name and role are required'];
        }

        $full_name = trim($data['full_name']);
        $role = trim($data['role']);

        $stmt = $this->conn->prepare("UPDATE users SET full_name = ?, role = ? WHERE id = ?");
        $stmt->bind_param('ssi', $full_name, $role, $id);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'User updated successfully'];
        }
        return ['success' => false, 'error' => $this->conn->error];
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            return ['success' => true];
        }
        return ['success' => false, 'error' => $this->conn->error];
    }

    public function resetPassword($id, $password) {
        if (empty($password)) {
            return ['success' => false, 'error' => 'Password cannot be empty'];
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param('si', $hash, $id);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Password reset successfully'];
        }
        return ['success' => false, 'error' => $this->conn->error];
    }

    public function getStats() {
        $result = $this->conn->query("SELECT COUNT(*) as total, SUM(role='admin') as admins, SUM(role='officer') as officers, SUM(role='teller') as tellers FROM users");
        return $result->fetch_assoc();
    }
}
