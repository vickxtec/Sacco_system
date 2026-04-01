<?php
require_once __DIR__ . '/../models/Setting.php';

class SettingsController {
    private $setting;

    public function __construct() {
        $this->setting = new Setting();
    }

    public function handleRequest() {
        $action = $_GET['action'] ?? 'list';

        switch ($action) {
            case 'list': return $this->listSettings();
            case 'get': return $this->getSetting();
            case 'update': return $this->updateSettings();
            default: return ['error' => 'Invalid action'];
        }
    }

    private function listSettings() {
        return ['success' => true, 'data' => $this->setting->getAll()];
    }

    private function getSetting() {
        $key = $_GET['key'] ?? '';
        if ($key === '') return ['success' => false, 'error' => 'Key required'];
        return ['success' => true, 'data' => $this->setting->get($key)];
    }

    private function updateSettings() {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        if (!is_array($data) || empty($data)) {
            return ['success' => false, 'error' => 'Invalid settings data'];
        }
        foreach ($data as $key => $value) {
            $result = $this->setting->set($key, trim((string)$value));
            if (!$result['success']) {
                return ['success' => false, 'error' => "Failed to update $key: {$result['error']}"];
            }
        }
        return ['success' => true, 'message' => 'Settings updated successfully'];
    }
}
