<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use PDO;

class AdminController
{
    public function index(): void
    {
        require_admin();
        View::render('admin/index');
    }

    public function mediaCreate(): void
    {
        require_admin();
        $controller = new MediaController();
        $controller->create();
    }

    public function mediaTodo(): void
    {
        require_admin();
        $controller = new MediaController();
        $controller->todo();
    }

    public function mediaEdit(): void
    {
        require_admin();
        $controller = new MediaController();
        $controller->edit();
    }

    public function parserRules(): void
    {
        require_admin();
        $pdo = Database::connection();

        $sites = $pdo->query("SELECT * FROM parser_sites ORDER BY name ASC, domain ASC")->fetchAll(PDO::FETCH_ASSOC);
        $rules = $pdo->query("SELECT pr.*, ps.name AS site_name FROM parser_rules pr LEFT JOIN parser_sites ps ON ps.id = pr.site_id ORDER BY ps.name ASC, pr.field_name ASC, pr.fallback_order ASC")->fetchAll(PDO::FETCH_ASSOC);

        View::render('admin/parser-rules', [
            'sites' => $sites,
            'rules' => $rules,
        ]);
    }

    public function saveParserRule(): void
    {
        require_admin();
        $pdo = Database::connection();

        $id = (int)($_POST['id'] ?? 0);
        $siteId = (int)($_POST['site_id'] ?? 0);
        $fieldName = trim($_POST['field_name'] ?? '');
        $ruleType = trim($_POST['rule_type'] ?? 'regex');
        $ruleValue = trim($_POST['rule_value'] ?? '');
        $fallbackOrder = (int)($_POST['fallback_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($siteId <= 0 || $fieldName === '' || $ruleValue === '') {
            http_response_code(422);
            exit('Site, field and rule value are required.');
        }

        if ($ruleType !== 'regex' && $ruleType !== 'meta') {
            $ruleType = 'regex';
        }

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE parser_rules SET site_id = :site_id, field_name = :field_name, rule_type = :rule_type, rule_value = :rule_value, fallback_order = :fallback_order, is_active = :is_active WHERE id = :id");
            $stmt->execute([
                'site_id' => $siteId,
                'field_name' => $fieldName,
                'rule_type' => $ruleType,
                'rule_value' => $ruleValue,
                'fallback_order' => $fallbackOrder,
                'is_active' => $isActive,
                'id' => $id,
            ]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO parser_rules (site_id, field_name, rule_type, rule_value, fallback_order, is_active) VALUES (:site_id, :field_name, :rule_type, :rule_value, :fallback_order, :is_active)");
            $stmt->execute([
                'site_id' => $siteId,
                'field_name' => $fieldName,
                'rule_type' => $ruleType,
                'rule_value' => $ruleValue,
                'fallback_order' => $fallbackOrder,
                'is_active' => $isActive,
            ]);
        }

        header('Location: ' . url('/admin/parser-rules'));
        exit;
    }

    public function deleteParserRule(): void
    {
        require_admin();
        $pdo = Database::connection();
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM parser_rules WHERE id = :id");
            $stmt->execute(['id' => $id]);
        }

        header('Location: ' . url('/admin/parser-rules'));
        exit;
    }
}
