<?php
declare(strict_types=1);

// ── GRADES ────────────────────────────────────────────────────

function add_grade(): never
{
    require_role('admin','educational_user');
    $data       = body();
    $student_id = (int)($data['student_id'] ?? 0);
    $subject_id = (int)($data['subject_id'] ?? 0);
    $score      = (float)($data['score']      ?? 0);
    $max_score  = (float)($data['max_score']  ?? 100);
    $label      = trim($data['label']  ?? '');
    $notes      = trim($data['notes']  ?? '');

    if (!$student_id || !$subject_id || $score < 0)
        json_error('student_id, subject_id and score are required');

    $graded_by = current_user()['id'];
    $stmt = db()->prepare(
        'INSERT INTO grades (student_id,subject_id,score,max_score,label,notes,graded_by)
         VALUES (?,?,?,?,?,?,?)'
    );
    $stmt->bind_param('iiddssi', $student_id, $subject_id, $score, $max_score, $label, $notes, $graded_by);
    $stmt->execute();
    json_out(['message' => 'Grade added', 'id' => $stmt->insert_id], 201);
}

function get_grades_for_student(int $student_id): never
{
    require_auth();
    $me = current_user();
    // Students can only see their own grades
    if ($me['role'] === 'student' && $me['id'] !== $student_id)
        json_error('Forbidden', 403);

    $stmt = db()->prepare(
        'SELECT g.*,s.name as subject_name,u.full_name as teacher_name,
                c.name as classroom_name
         FROM grades g
         JOIN subjects s ON s.id=g.subject_id
         JOIN classrooms c ON c.id=s.classroom_id
         LEFT JOIN users u ON u.id=g.graded_by
         WHERE g.student_id=?
         ORDER BY g.graded_at DESC'
    );
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    json_out(['grades' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
}

function get_grades_by_subject(int $subject_id): never
{
    require_role('admin','educational_user');
    $stmt = db()->prepare(
        'SELECT g.*,u.full_name as student_name
         FROM grades g JOIN users u ON u.id=g.student_id
         WHERE g.subject_id=? ORDER BY u.full_name'
    );
    $stmt->bind_param('i', $subject_id);
    $stmt->execute();
    json_out(['grades' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
}

function delete_grade(int $id): never
{
    require_role('admin','educational_user');
    $stmt = db()->prepare('DELETE FROM grades WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    if ($stmt->affected_rows === 0) json_error('Grade not found', 404);
    json_out(['message' => 'Grade deleted']);
}

// ── RATINGS ───────────────────────────────────────────────────

function add_rating(): never
{
    require_role('admin','educational_user');
    $data         = body();
    $student_id   = (int)($data['student_id']   ?? 0);
    $classroom_id = (int)($data['classroom_id'] ?? 0);
    $score        = (int)($data['score']        ?? 0);
    $comment      = trim($data['comment'] ?? '');

    if (!$student_id || !$classroom_id || $score < 1 || $score > 5)
        json_error('student_id, classroom_id and score (1-5) required');

    $rated_by = current_user()['id'];
    $stmt = db()->prepare(
        'INSERT INTO ratings (student_id,rated_by,classroom_id,score,comment) VALUES (?,?,?,?,?)'
    );
    $stmt->bind_param('iiiis', $student_id, $rated_by, $classroom_id, $score, $comment);
    $stmt->execute();
    json_out(['message' => 'Rating added'], 201);
}

function get_ratings_for_student(int $student_id): never
{
    require_auth();
    $me = current_user();
    if ($me['role'] === 'student' && $me['id'] !== $student_id)
        json_error('Forbidden', 403);

    $stmt = db()->prepare(
        'SELECT r.*,u.full_name as teacher_name,c.name as classroom_name
         FROM ratings r
         JOIN users u ON u.id=r.rated_by
         JOIN classrooms c ON c.id=r.classroom_id
         WHERE r.student_id=? ORDER BY r.rated_at DESC'
    );
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    json_out(['ratings' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
}

// ── ATTENDANCE ────────────────────────────────────────────────

function mark_attendance(): never
{
    require_role('admin','educational_user');
    $data         = body();
    $student_id   = (int)($data['student_id']   ?? 0);
    $classroom_id = (int)($data['classroom_id'] ?? 0);
    $date         = trim($data['date']   ?? date('Y-m-d'));
    $status       = trim($data['status'] ?? 'present');
    $notes        = trim($data['notes']  ?? '');

    if (!$student_id || !$classroom_id)
        json_error('student_id and classroom_id required');
    if (!in_array($status, ['present','absent','late','excused'], true))
        json_error('Invalid status');

    $marked_by = current_user()['id'];
    $stmt = db()->prepare(
        'INSERT INTO attendance (student_id,classroom_id,date,status,marked_by,notes)
         VALUES (?,?,?,?,?,?)
         ON DUPLICATE KEY UPDATE status=VALUES(status),notes=VALUES(notes)'
    );
    $stmt->bind_param('iissis', $student_id, $classroom_id, $date, $status, $marked_by, $notes);
    $stmt->execute();
    json_out(['message' => 'Attendance recorded']);
}

function get_attendance_for_student(int $student_id): never
{
    require_auth();
    $me = current_user();
    if ($me['role'] === 'student' && $me['id'] !== $student_id)
        json_error('Forbidden', 403);

    $stmt = db()->prepare(
        'SELECT a.*,c.name as classroom_name,u.full_name as marked_by_name
         FROM attendance a
         JOIN classrooms c ON c.id=a.classroom_id
         JOIN users u ON u.id=a.marked_by
         WHERE a.student_id=? ORDER BY a.date DESC'
    );
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    json_out(['attendance' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
}

function get_attendance_by_classroom(int $classroom_id): never
{
    require_role('admin','educational_user');
    $date = trim($_GET['date'] ?? date('Y-m-d'));
    $stmt = db()->prepare(
        'SELECT a.*,u.full_name as student_name
         FROM attendance a JOIN users u ON u.id=a.student_id
         WHERE a.classroom_id=? AND a.date=? ORDER BY u.full_name'
    );
    $stmt->bind_param('is', $classroom_id, $date);
    $stmt->execute();
    json_out(['attendance' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
}
