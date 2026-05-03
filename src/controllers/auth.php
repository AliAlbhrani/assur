<?php

declare(strict_types=1);

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../../config/db.php';

// ── LOGIN ────────────────────────────────────────────────────
function login(): never
{
    $data = body();

    $phone    = trim($data['phone']    ?? '');
    $password = trim($data['password'] ?? '');

    if (!$phone || !$password) {
        json_error('Phone and password are required');
    }

    $db   = db();
    $stmt = $db->prepare(
        'SELECT id, full_name, phone, email, password, role, avatar, is_active
         FROM users WHERE phone = ? LIMIT 1'
    );
    $stmt->bind_param('s', $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    $user   = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        json_error('Invalid phone or password', 401);
    }

    if (!(bool) $user['is_active']) {
        json_error('Your account has been deactivated. Contact an admin.', 403);
    }

    if (!password_verify($password, $user['password'])) {
        json_error('Invalid phone or password', 401);
    }

    // Store safe subset in session (never store raw password)
    start_session();
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id'        => $user['id'],
        'full_name' => $user['full_name'],
        'phone'     => $user['phone'],
        'email'     => $user['email'],
        'role'      => $user['role'],
        'avatar'    => $user['avatar'],
    ];

    json_out([
        'message' => 'Login successful',
        'user'    => $_SESSION['user'],
    ]);
}

// ── REGISTER (admin only creates users) ──────────────────────
function register(): never
{
    require_role('admin');

    $data = body();

    $full_name = trim($data['full_name'] ?? '');
    $phone     = trim($data['phone']     ?? '');
    $email     = trim($data['email']     ?? '');
    $password  = trim($data['password']  ?? '');
    $role      = trim($data['role']      ?? 'student');

    // Validation
    if (!$full_name || !$phone || !$password) {
        json_error('full_name, phone, and password are required');
    }

    if (!in_array($role, ['admin', 'educational_user', 'student'], true)) {
        json_error('Invalid role');
    }

    if (strlen($password) < 8) {
        json_error('Password must be at least 8 characters');
    }

    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_error('Invalid email address');
    }

    $db = db();

    // Check phone uniqueness
    $stmt = $db->prepare('SELECT id FROM users WHERE phone = ? LIMIT 1');
    $stmt->bind_param('s', $phone);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        json_error('Phone number already registered', 409);
    }
    $stmt->close();

    // Check email uniqueness if provided
    if ($email) {
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            json_error('Email already registered', 409);
        }
        $stmt->close();
    }

    $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = $db->prepare(
        'INSERT INTO users (full_name, phone, email, password, role)
         VALUES (?, ?, ?, ?, ?)'
    );
    $emailVal = $email ?: null;
    $stmt->bind_param('sssss', $full_name, $phone, $emailVal, $hashed, $role);
    $stmt->execute();
    $new_id = $stmt->insert_id;
    $stmt->close();

    json_out([
        'message' => 'User created successfully',
        'user'    => [
            'id'        => $new_id,
            'full_name' => $full_name,
            'phone'     => $phone,
            'email'     => $email,
            'role'      => $role,
        ],
    ], 201);
}

// ── LOGOUT ───────────────────────────────────────────────────
function logout(): never
{
    start_session();
    $_SESSION = [];
    session_destroy();
    json_out(['message' => 'Logged out successfully']);
}

// ── ME (get current user) ────────────────────────────────────
function me(): never
{
    require_auth();
    json_out(['user' => current_user()]);
}

// ── CHANGE PASSWORD ──────────────────────────────────────────
function change_password(): never
{
    require_auth();
    $data = body();

    $old = trim($data['old_password'] ?? '');
    $new = trim($data['new_password'] ?? '');

    if (!$old || !$new) {
        json_error('old_password and new_password are required');
    }

    if (strlen($new) < 8) {
        json_error('New password must be at least 8 characters');
    }

    $user_id = current_user()['id'];
    $db      = db();

    $stmt = $db->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || !password_verify($old, $row['password'])) {
        json_error('Current password is incorrect', 401);
    }

    $hashed = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt   = $db->prepare('UPDATE users SET password = ? WHERE id = ?');
    $stmt->bind_param('si', $hashed, $user_id);
    $stmt->execute();
    $stmt->close();

    json_out(['message' => 'Password changed successfully']);
}

function createAdmin(): never
{
    /* require_role('admin'); */

    $data = body();

    $full_name = trim($data['full_name'] ?? '');
    $phone     = trim($data['phone']     ?? '');
    $email     = trim($data['email']     ?? '');
    $password  = trim($data['password']  ?? '');
    $role      = trim($data['role']      ?? 'student');

    // Validation
    if (!$full_name || !$phone || !$password) {
        json_error('full_name, phone, and password are required');
    }

    if (!in_array($role, ['admin', 'educational_user', 'student'], true)) {
        json_error('Invalid role');
    }

    if (strlen($password) < 8) {
        json_error('Password must be at least 8 characters');
    }

    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_error('Invalid email address');
    }

    $db = db();

    // Check phone uniqueness
    $stmt = $db->prepare('SELECT id FROM users WHERE phone = ? LIMIT 1');
    $stmt->bind_param('s', $phone);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        json_error('Phone number already registered', 409);
    }
    $stmt->close();

    // Check email uniqueness if provided
    if ($email) {
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            json_error('Email already registered', 409);
        }
        $stmt->close();
    }

    $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = $db->prepare(
        'INSERT INTO users (full_name, phone, email, password, role)
         VALUES (?, ?, ?, ?, ?)'
    );
    $emailVal = $email ?: null;
    $stmt->bind_param('sssss', $full_name, $phone, $emailVal, $hashed, $role);
    $stmt->execute();
    $new_id = $stmt->insert_id;
    $stmt->close();

    json_out([
        'message' => 'User created successfully',
        'user'    => [
            'id'        => $new_id,
            'full_name' => $full_name,
            'phone'     => $phone,
            'email'     => $email,
            'role'      => $role,
        ],
    ], 201);
}
// ── ADMIN CHANGE USER PASSWORD ────────────────────────────────
function admin_change_password(int $user_id): never
{
    require_role('admin');
    $data     = body();
    $password = trim($data['password'] ?? '');

    if (!$password) {
        json_error('Password is required');
    }
    if (strlen($password) < 8) {
        json_error('Password must be at least 8 characters');
    }

    $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt   = db()->prepare('UPDATE users SET password = ? WHERE id = ?');
    $stmt->bind_param('si', $hashed, $user_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        json_error('User not found', 404);
    }
    json_out(['message' => 'Password updated successfully']);
}
