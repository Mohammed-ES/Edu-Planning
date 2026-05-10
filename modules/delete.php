<?php
require_once __DIR__ . '/_bootstrap.php';

$id = (int)($_POST['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../module.php');
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    header('Location: ../module.php');
    exit;
}

if ($id > 0) {
    $stmt = $pdo->prepare('DELETE FROM modules WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $user_id]);
}

header('Location: ../module.php');
exit;
