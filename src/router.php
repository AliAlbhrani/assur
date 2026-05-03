<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/controllers/auth.php';
require_once __DIR__ . '/controllers/users.php';
require_once __DIR__ . '/controllers/classrooms.php';
require_once __DIR__ . '/controllers/grades.php';
require_once __DIR__ . '/controllers/posts.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');
if (strtoupper($_SERVER['REQUEST_METHOD']) === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$method = method();
$path   = path();

// ── extract numeric segment: /api/users/5 → id=5 ─────────────
$parts = explode('/', trim($path, '/'));
// parts: [0]=api, [1]=resource, [2]=id?, [3]=sub?, [4]=sub_id?
$id     = isset($parts[2]) && is_numeric($parts[2]) ? (int) $parts[2] : null;
$sub    = $parts[3] ?? null;
$sub_id = isset($parts[4]) && is_numeric($parts[4]) ? (int) $parts[4] : null;
$base   = '/' . ($parts[0] ?? '') . '/' . ($parts[1] ?? '');

// ── ROUTING ───────────────────────────────────────────────────
match (true) {

    // Auth
    $method === 'POST' && $path === '/api/login'           => login(),
    $method === 'POST' && $path === '/api/logout'          => logout(),
    $method === 'GET'  && $path === '/api/me'              => me(),
    $method === 'POST' && $path === '/api/register'        => register(),
    $method === 'POST' && $path === '/api/change-password' => change_password(),
    $method === 'POST' && $path === '/api/createAdmin' => createAdmin(),

    // Users (admin)
    $method === 'GET'    && $path === '/api/users'              => get_users(),
    $method === 'GET'    && $base === '/api/users' && $id       => get_user($id),
    $method === 'PUT'    && $base === '/api/users' && $id       => update_user($id),
    $method === 'DELETE' && $base === '/api/users' && $id       => delete_user($id),
    $method === 'PATCH'  && $base === '/api/users' && $id && $sub === 'toggle' => toggle_user($id),
    $method === 'GET'    && $path === '/api/stats'              => get_stats(),
    $method === 'PATCH' && $base === '/api/users' && $id && $sub === 'password' => admin_change_password($id),

    // Classrooms
    $method === 'GET'  && $path === '/api/classrooms'           => get_classrooms(),
    $method === 'POST' && $path === '/api/classrooms'           => create_classroom(),
    $method === 'DELETE' && $base === '/api/classrooms' && $id  => delete_classroom($id),

    // Classroom members
    $method === 'GET'    && $base === '/api/classrooms' && $id && $sub === 'members'                     => get_members($id),
    $method === 'POST'   && $base === '/api/classrooms' && $id && $sub === 'members'                     => add_member($id),
    $method === 'DELETE' && $base === '/api/classrooms' && $id && $sub === 'members' && $sub_id          => remove_member($id, $sub_id),

    // Student self-enrollment
    $method === 'GET'    && $path === '/api/classrooms/browse'                                         => get_all_classrooms_public(),
    $method === 'POST'   && $base === '/api/classrooms' && $id && $sub === 'enroll'                      => enroll_self($id),
    $method === 'DELETE' && $base === '/api/classrooms' && $id && $sub === 'enroll'                      => unenroll_self($id),

    // Teachers & students lists for admin
    $method === 'GET' && $path === '/api/teachers'  => get_teachers(),
    $method === 'GET' && $path === '/api/students'  => get_students(),

    // Subjects
    $method === 'GET'    && $base === '/api/classrooms' && $id && $sub === 'subjects'  => get_subjects($id),
    $method === 'POST'   && $base === '/api/classrooms' && $id && $sub === 'subjects'  => create_subject($id),
    $method === 'DELETE' && $base === '/api/subjects'   && $id                       => delete_subject($id),

    // Grades
    $method === 'POST'   && $path === '/api/grades'                               => add_grade(),
    $method === 'GET'    && $base === '/api/students' && $id && $sub === 'grades'   => get_grades_for_student($id),
    $method === 'GET'    && $base === '/api/subjects' && $id && $sub === 'grades'   => get_grades_by_subject($id),
    $method === 'DELETE' && $base === '/api/grades' && $id                        => delete_grade($id),

    // Ratings
    $method === 'POST' && $path === '/api/ratings'                                => add_rating(),
    $method === 'GET'  && $base === '/api/students' && $id && $sub === 'ratings'    => get_ratings_for_student($id),

    // Attendance
    $method === 'POST' && $path === '/api/attendance'                                              => mark_attendance(),
    $method === 'GET'  && $base === '/api/students' && $id && $sub === 'attendance'                  => get_attendance_for_student($id),
    $method === 'GET'  && $base === '/api/classrooms' && $id && $sub === 'attendance'                => get_attendance_by_classroom($id),

    // Posts
    $method === 'GET'    && $path === '/api/posts'           => get_posts(),
    $method === 'POST'   && $path === '/api/posts'           => create_post(),
    $method === 'PUT'    && $base === '/api/posts' && $id    => update_post($id),
    $method === 'DELETE' && $base === '/api/posts' && $id    => delete_post($id),

    default => json_error("Route not found: $method $path", 404),
};
