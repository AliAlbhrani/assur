<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../../config/db.php';

function get_ratings(): never
{
    require_auth();
    $user       = current_user();
    $student_id = $user['role'] === 'student'
        ? (int)$user['id']
        : (int)($_GET['student_id'] ?? 0);

    $db  = db();
    $sql = 'SELECT r.*, u.full_name AS student_name, t.full_name AS teacher_name, c.name AS classroom_name
            FROM ratings r
            JOIN users u       ON u.id = r.student_id
            JOIN users t       ON t.id = r.rated_by
            JOIN classrooms c  ON c.id = r.classroom_id';

    if ($student_id) {
        $stmt = $db->prepare($sql . ' WHERE r.student_id = ? ORDER BY r.rated_at DESC');
        $stmt->bind_param('i', $student_id);
    } else {
        $stmt = $db->prepare($sql . ' ORDER BY r.rated_at DESC LIMIT 50');
    }

    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    json_out(['ratings' => $rows]);
}

function add_rating(): never
{
    require_role('admin', 'educational_user');
    $data         = body();
    $student_id   = (int)($data['student_id']   ?? 0);
    $classroom_id = (int)($data['classroom_id'] ?? 0);
    $score        = (int)($data['score']         ?? 0);
    $comment      = trim($data['comment']        ?? '');
    $rated_by     = (int)current_user()['id'];

    if (!$student_id || !$classroom_id) json_error('student_id and classroom_id are required');
    if ($score < 1 || $score > 5) json_error('Score must be between 1 and 5');

    $db   = db();
    $stmt = $db->prepare(
        'INSERT INTO ratings (student_id, rated_by, classroom_id, score, comment)
         VALUES (?,?,?,?,?)'
    );
    $stmt->bind_param('iiiis', $student_id, $rated_by, $classroom_id, $score, $comment);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();

    json_out(['message' => 'Rating added', 'id' => $id], 201);
}
