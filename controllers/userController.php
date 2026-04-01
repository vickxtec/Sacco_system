<?php
require_once __DIR__ . '/../models/User.php';

class UserController {
    private $user;

    public function __construct() {
        $this->user = new User();
    }

    public function handleRequest() {
        $action = $_GET['action'] ?? 'list';

        switch ($action) {
            case 'list': return $this->listUsers();
            case 'get': return $this->getUser();
            case 'create': return $this->createUser();
            case 'update': return $this->updateUser();
            case 'delete': return $this->deleteUser();
            case 'reset_password': return $this->resetPassword();
            case 'stats': return $this->getStats();
            default: return ['error' => 'Invalid action'];
        }
    }

    private function listUsers() {
        $search = $_GET['search'] ?? '';
        $role = $_GET['role'] ?? '';
        return ['success' => true, 'data' => $this->user->getAll($search, $role)];
    }

    private function getUser() {
        $id = intval($_GET['id'] ?? 0);
        $data = $this->user->getById($id);
        if ($data) return ['success' => true, 'data' => $data];
        return ['success' => false, 'error' => 'User not found'];
    }

    private function createUser() {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        return $this->user->create($data);
    }

    private function updateUser() {
        $id = intval($_GET['id'] ?? 0);
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        return $this->user->update($id, $data);
    }

    private function deleteUser() {
        $id = intval($_GET['id'] ?? 0);
        return $this->user->delete($id);
    }

    private function resetPassword() {
        $id = intval($_GET['id'] ?? 0);
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $password = $data['password'] ?? '';
        return $this->user->resetPassword($id, $password);
    }

    private function getStats() {
        return ['success' => true, 'data' => $this->user->getStats()];
    }
}
