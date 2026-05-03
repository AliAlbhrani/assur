<?php
declare(strict_types=1);

function get_posts(): never
{
    require_auth();
    $user         = current_user();
    $db           = db();
    $classroom_id = isset($_GET['classroom_id']) ? (int)$_GET['classroom_id'] : null;

    if ($classroom_id) {
        $stmt = $db->prepare(
            'SELECT p.*,u.full_name as author_name,u.role as author_role,
                    c.name as classroom_name
             FROM posts p JOIN users u ON u.id=p.author_id
             LEFT JOIN classrooms c ON c.id=p.classroom_id
             WHERE p.classroom_id=? ORDER BY p.created_at DESC'
        );
        $stmt->bind_param('i', $classroom_id);
    } else {
        // All posts visible to this user
        $stmt = $db->prepare(
            'SELECT p.*,u.full_name as author_name,u.role as author_role,
                    c.name as classroom_name
             FROM posts p JOIN users u ON u.id=p.author_id
             LEFT JOIN classrooms c ON c.id=p.classroom_id
             ORDER BY p.created_at DESC LIMIT 50'
        );
    }
    $stmt->execute();
    json_out(['posts' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
}

function create_post(): never
{
    require_role('admin','educational_user');
    $data         = body();
    $title        = trim($data['title']        ?? '');
    $body_text    = trim($data['body']         ?? '');
    $classroom_id = isset($data['classroom_id']) ? (int)$data['classroom_id'] : null;

    if (!$title || !$body_text) json_error('title and body are required');

    $author_id = current_user()['id'];
    $stmt = db()->prepare(
        'INSERT INTO posts (author_id,classroom_id,title,body) VALUES (?,?,?,?)'
    );
    $stmt->bind_param('iiss', $author_id, $classroom_id, $title, $body_text);
    $stmt->execute();
    json_out(['message' => 'Post created', 'id' => $stmt->insert_id], 201);
}

function update_post(int $id): never
{
    require_role('admin','educational_user');
    $data      = body();
    $title     = trim($data['title'] ?? '');
    $body_text = trim($data['body']  ?? '');
    $me        = current_user();

    // Only author or admin can edit
    $stmt = db()->prepare('SELECT author_id FROM posts WHERE id=? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $post = $stmt->get_result()->fetch_assoc();
    if (!$post) json_error('Post not found', 404);
    if ($me['role'] !== 'admin' && $post['author_id'] !== $me['id'])
        json_error('Forbidden', 403);

    $fields = []; $types = ''; $vals = [];
    if ($title)     { $fields[] = 'title=?'; $types .= 's'; $vals[] = $title; }
    if ($body_text) { $fields[] = 'body=?';  $types .= 's'; $vals[] = $body_text; }
    if (empty($fields)) json_error('Nothing to update');

    $types .= 'i'; $vals[] = $id;
    $stmt = db()->prepare('UPDATE posts SET '.implode(',',$fields).' WHERE id=?');
    $stmt->bind_param($types, ...$vals);
    $stmt->execute();
    json_out(['message' => 'Post updated']);
}

function delete_post(int $id): never
{
    require_role('admin','educational_user');
    $me   = current_user();
    $stmt = db()->prepare('SELECT author_id FROM posts WHERE id=? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $post = $stmt->get_result()->fetch_assoc();
    if (!$post) json_error('Post not found', 404);
    if ($me['role'] !== 'admin' && $post['author_id'] !== $me['id'])
        json_error('Forbidden', 403);

    $stmt = db()->prepare('DELETE FROM posts WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    json_out(['message' => 'Post deleted']);
}
