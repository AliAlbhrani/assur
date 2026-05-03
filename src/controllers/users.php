<?php
declare(strict_types=1);

function get_users(): never
{
    require_role('admin');
    $db   = db();
    $role = trim($_GET['role'] ?? '');
    if ($role && in_array($role, ['admin','educational_user','student'], true)) {
        $stmt = $db->prepare('SELECT id,full_name,phone,email,role,is_active,created_at FROM users WHERE role=? ORDER BY created_at DESC');
        $stmt->bind_param('s', $role);
    } else {
        $stmt = $db->prepare('SELECT id,full_name,phone,email,role,is_active,created_at FROM users ORDER BY created_at DESC');
    }
    $stmt->execute();
    json_out(['users' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
}

function get_user(int $id): never
{
    require_role('admin');
    $stmt = db()->prepare('SELECT id,full_name,phone,email,role,is_active,created_at FROM users WHERE id=? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if (!$user) json_error('User not found', 404);
    json_out(['user' => $user]);
}

function update_user(int $id): never
{
    require_role('admin');
    $data = body();
    $full_name = trim($data['full_name'] ?? '');
    $email     = trim($data['email']     ?? '');
    $role      = trim($data['role']      ?? '');
    $is_active = array_key_exists('is_active', $data) ? (int)(bool)$data['is_active'] : null;

    $fields = []; $types = ''; $vals = [];
    if ($full_name) { $fields[] = 'full_name=?'; $types .= 's'; $vals[] = $full_name; }
    if ($email)     { $fields[] = 'email=?';     $types .= 's'; $vals[] = $email; }
    if ($role && in_array($role,['admin','educational_user','student'],true)) {
        $fields[] = 'role=?'; $types .= 's'; $vals[] = $role;
    }
    if ($is_active !== null) { $fields[] = 'is_active=?'; $types .= 'i'; $vals[] = $is_active; }
    if (empty($fields)) json_error('Nothing to update');

    $types .= 'i'; $vals[] = $id;
    $stmt = db()->prepare('UPDATE users SET '.implode(',',$fields).' WHERE id=?');
    $stmt->bind_param($types, ...$vals);
    $stmt->execute();
    json_out(['message' => 'User updated']);
}

function delete_user(int $id): never
{
    require_role('admin');
    if (current_user()['id'] === $id) json_error('Cannot delete your own account');
    $stmt = db()->prepare('DELETE FROM users WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    if ($stmt->affected_rows === 0) json_error('User not found', 404);
    json_out(['message' => 'User deleted']);
}

function toggle_user(int $id): never
{
    require_role('admin');
    $stmt = db()->prepare('UPDATE users SET is_active = NOT is_active WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    json_out(['message' => 'Status toggled']);
}

function get_stats(): never
{
    require_role('admin');
    $db = db();
    $stats = [];
    foreach (['admin','educational_user','student'] as $r) {
        $stmt = $db->prepare('SELECT COUNT(*) as cnt FROM users WHERE role=?');
        $stmt->bind_param('s', $r);
        $stmt->execute();
        $stats[$r] = (int)$stmt->get_result()->fetch_assoc()['cnt'];
    }
    $stmt = $db->prepare('SELECT COUNT(*) as cnt FROM classrooms');
    $stmt->execute();
    $stats['classrooms'] = (int)$stmt->get_result()->fetch_assoc()['cnt'];

    $stmt = $db->prepare('SELECT COUNT(*) as cnt FROM posts');
    $stmt->execute();
    $stats['posts'] = (int)$stmt->get_result()->fetch_assoc()['cnt'];
    json_out(['stats' => $stats]);
}
