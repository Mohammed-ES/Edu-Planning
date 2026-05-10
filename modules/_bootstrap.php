<?php
require_once __DIR__ . '/../include/auth.php';
require_login();

$user_id = (int) $_SESSION['user_id'];

function module_label(string $value): string
{
    return [
        'EASY' => 'Easy',
        'MEDIUM' => 'Medium',
        'HARD' => 'Hard',
        'LOW' => 'Low',
        'HIGH' => 'High',
    ][$value] ?? $value;
}

function module_initial(string $name): string
{
    $name = trim($name);
    return strtoupper($name === '' ? 'U' : substr($name, 0, 1));
}

$stmt = $pdo->prepare('SELECT name FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$current_user = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['name' => 'User'];
$username = $current_user['name'];
