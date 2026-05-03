<?php
declare(strict_types=1);

function get_classrooms(): never
{
    require_auth();
    $user = current_user();
    $db   = db();

    if ($user['role'] === 'admin') {
        $stmt = $db->prepare(
            'SELECT c.*, u.full_name as creator,
             (SELECT COUNT(*) FROM classroom_members cm WHERE cm.classroom_id=c.id AND cm.role="student") as student_count,
             (SELECT COUNT(*) FROM classroom_members cm WHERE cm.classroom_id=c.id AND cm.role="teacher") as teacher_count
             FROM classrooms c JOIN users u ON u.id=c.created_by ORDER BY c.created_at DESC'
        );
    } else {
        $uid  = $user['id'];
        $stmt = $db->prepare(
            'SELECT c.*, u.full_name as creator,
             (SELECT COUNT(*) FROM classroom_members cm WHERE cm.classroom_id=c.id AND cm.role="student") as student_count,
             (SELECT COUNT(*) FROM classroom_members cm WHERE cm.classroom_id=c.id AND cm.role="teacher") as teacher_count
             FROM classrooms c
             JOIN users u ON u.id=c.created_by
             JOIN classroom_members cm ON cm.classroom_id=c.id AND cm.user_id=?
             ORDER BY c.created_at DESC'
        );
        $stmt->bind_param('i', $uid);
    }
    $stmt->execute();
    json_out(['classrooms' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
}

function create_classroom(): never
{
    require_role('admin');
    $data = body();
    $name = trim($data['name'] ?? '');
    $grade = trim($data['grade'] ?? '');
    $desc  = trim($data['description'] ?? '');
    if (!$name) json_error('Classroom name is required');

    $uid  = current_user()['id'];
    $stmt = db()->prepare('INSERT INTO classrooms (name,grade,description,created_by) VALUES (?,?,?,?)');
    $stmt->bind_param('sssi', $name, $grade, $desc, $uid);
    $stmt->execute();
    json_out(['message' => 'Classroom created', 'id' => $stmt->insert_id], 201);
}

function delete_classroom(int $id): never
{
    require_role('admin');
    $stmt = db()->prepare('DELETE FROM classrooms WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    if ($stmt->affected_rows === 0) json_error('Classroom not found', 404);
    json_out(['message' => 'Classroom deleted']);
}

function add_member(int $classroom_id): never
{
    require_role('admin');
    $data    = body();
    $user_id = (int)($data['user_id'] ?? 0);
    $role    = trim($data['role'] ?? '');
    if (!$user_id || !in_array($role, ['teacher','student'], true)) json_error('user_id and role (teacher|student) required');

    $stmt = db()->prepare('INSERT IGNORE INTO classroom_members (classroom_id,user_id,role) VALUES (?,?,?)');
    $stmt->bind_param('iis', $classroom_id, $user_id, $role);
    $stmt->execute();
    json_out(['message' => 'Member added']);
}

function remove_member(int $classroom_id, int $user_id): never
{
    require_role('admin');
    $stmt = db()->prepare('DELETE FROM classroom_members WHERE classroom_id=? AND user_id=?');
    $stmt->bind_param('ii', $classroom_id, $user_id);
    $stmt->execute();
    json_out(['message' => 'Member removed']);
}

function get_members(int $classroom_id): never
{
    require_auth();
    $stmt = db()->prepare(
        'SELECT u.id,u.full_name,u.phone,u.email,u.role as user_role,cm.role as classroom_role,cm.joined_at
         FROM classroom_members cm JOIN users u ON u.id=cm.user_id
         WHERE cm.classroom_id=? ORDER BY cm.role,u.full_name'
    );
    $stmt->bind_param('i', $classroom_id);
    $stmt->execute();
    json_out(['members' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
}

function get_subjects(int $classroom_id): never
{
    require_auth();
    $stmt = db()->prepare(
        'SELECT s.*,u.full_name as teacher_name FROM subjects s
         LEFT JOIN users u ON u.id=s.teacher_id WHERE s.classroom_id=?'
    );
    $stmt->bind_param('i', $classroom_id);
    $stmt->execute();
    json_out(['subjects' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
}

function create_subject(int $classroom_id): never
{
    require_role('admin','educational_user');
    $data       = body();
    $name       = trim($data['name'] ?? '');
    $teacher_id = (int)($data['teacher_id'] ?? 0) ?: null;
    if (!$name) json_error('Subject name required');
    $stmt = db()->prepare('INSERT INTO subjects (classroom_id,name,teacher_id) VALUES (?,?,?)');
    $stmt->bind_param('isi', $classroom_id, $name, $teacher_id);
    $stmt->execute();
    json_out(['message' => 'Subject created', 'id' => $stmt->insert_id], 201);
}
