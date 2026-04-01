<?php
require_once __DIR__ . '/../models/Member.php';

class MemberController {
    private $member;

    public function __construct() {
        $this->member = new Member();
    }

    public function handleRequest() {
        $action = $_GET['action'] ?? 'list';

        switch ($action) {
            case 'list': return $this->listMembers();
            case 'get': return $this->getMember();
            case 'create': return $this->createMember();
            case 'update': return $this->updateMember();
            case 'delete': return $this->deleteMember();
            case 'stats': return $this->getStats();
            default: return ['error' => 'Invalid action'];
        }
    }

    private function listMembers() {
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        return ['success' => true, 'data' => $this->member->getAll($search, $status)];
    }

    private function getMember() {
        $id = $_GET['id'] ?? 0;
        $data = $this->member->getById($id);
        if ($data) return ['success' => true, 'data' => $data];
        return ['success' => false, 'error' => 'Member not found'];
    }

    private function createMember() {
        $data = $_POST;
        $required = ['full_name', 'id_number', 'phone', 'joined_date'];
        foreach ($required as $field) {
            if (empty($data[$field])) return ['success' => false, 'error' => "Field '$field' is required"];
        }
        $data['email'] = $data['email'] ?? '';
        $data['address'] = $data['address'] ?? '';
        $data['dob'] = $data['dob'] ?? null;
        $data['gender'] = $data['gender'] ?? 'Male';
        $data['status'] = $data['status'] ?? 'Active';
        $data['share_capital'] = $data['share_capital'] ?? 5000;
        $data = array_merge($data, $this->processUploadedFiles($_FILES));
        return $this->member->create($data);
    }

    private function updateMember() {
        $id = intval($_GET['id'] ?? 0);
        $data = $_POST;
        $existing = $this->member->getById($id);
        if (!$existing) {
            return ['success' => false, 'error' => 'Member not found'];
        }
        $data = array_merge($existing, $data);
        $uploaded = $this->processUploadedFiles($_FILES);
        foreach ($uploaded as $key => $value) {
            if (!empty($value)) {
                $data[$key] = $value;
            }
        }
        return $this->member->update($id, $data);
    }

    private function processUploadedFiles($files) {
        $allowed = ['photo', 'id_front', 'id_back', 'signature'];
        $result = array_fill_keys($allowed, '');
        $uploadDir = __DIR__ . '/../uploads/members';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        foreach ($allowed as $field) {
            if (!empty($files[$field]) && $files[$field]['error'] === UPLOAD_ERR_OK) {
                $result[$field] = $this->saveUploadedFile($files[$field], $uploadDir);
            }
        }
        return $result;
    }

    private function saveUploadedFile($file, $uploadDir) {
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safeExt = preg_match('/^[a-zA-Z0-9]+$/', $extension) ? $extension : 'jpg';
        $filename = uniqid('member_', true) . '.' . $safeExt;
        $destination = $uploadDir . '/' . $filename;
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return 'uploads/members/' . $filename;
        }
        return '';
    }

    private function deleteMember() {
        $id = $_GET['id'] ?? 0;
        return $this->member->delete($id);
    }

    private function getStats() {
        return ['success' => true, 'data' => $this->member->getStats()];
    }
}