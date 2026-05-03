BEGIN;

-- ── USERS ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name   VARCHAR(120)  NOT NULL,
    phone       VARCHAR(20)   NOT NULL UNIQUE,
    email       VARCHAR(150)  UNIQUE,
    password    VARCHAR(255)  NOT NULL,
    role        ENUM('admin','educational_user','student') NOT NULL DEFAULT 'student',
    avatar      VARCHAR(255)  DEFAULT NULL,
    is_active   TINYINT(1)    NOT NULL DEFAULT 1,
    created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ── CLASSROOMS ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS classrooms (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)  NOT NULL,
    grade       VARCHAR(50)   DEFAULT NULL,
    description TEXT          DEFAULT NULL,
    created_by  INT UNSIGNED  NOT NULL,
    created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    deleted_at  TIMESTAMP     DEFAULT NULL,
    deleted_by  INT UNSIGNED DEFAULT NULL,
    FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
);

-- ── CLASSROOM MEMBERS ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS classroom_members (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    classroom_id  INT UNSIGNED NOT NULL,
    user_id       INT UNSIGNED NOT NULL,
    role          ENUM('teacher','student') NOT NULL,
    joined_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at  TIMESTAMP     DEFAULT NULL,
    deleted_by  INT UNSIGNED DEFAULT NULL,

    UNIQUE KEY uq_member (classroom_id, user_id),

    FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)      REFERENCES users(id)      ON DELETE CASCADE
);

-- ── SUBJECTS ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS subjects (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    classroom_id  INT UNSIGNED NOT NULL,
    name          VARCHAR(100) NOT NULL,
    teacher_id    INT UNSIGNED DEFAULT NULL,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    deleted_at  TIMESTAMP     DEFAULT NULL,
    deleted_by  INT UNSIGNED DEFAULT NULL,
    FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id)   REFERENCES users(id)      ON DELETE SET NULL
);

-- ── GRADES ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS grades (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id  INT UNSIGNED   NOT NULL,
    subject_id  INT UNSIGNED   NOT NULL,
    score       DECIMAL(5,2)   NOT NULL,
    max_score   DECIMAL(5,2)   NOT NULL DEFAULT 100,
    label       VARCHAR(80)    DEFAULT NULL,   -- e.g. "Midterm", "Final"
    notes       TEXT           DEFAULT NULL,
    graded_by   INT UNSIGNED   NOT NULL,
    graded_at   TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id)     ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id)  ON DELETE CASCADE,
    FOREIGN KEY (graded_by)  REFERENCES users(id)     ON DELETE RESTRICT
);

-- ── RATINGS ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ratings (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id  INT UNSIGNED  NOT NULL,
    rated_by    INT UNSIGNED  NOT NULL,
    classroom_id INT UNSIGNED NOT NULL,
    score       TINYINT       NOT NULL CHECK (score BETWEEN 1 AND 5),
    comment     TEXT          DEFAULT NULL,
    rated_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id)  REFERENCES users(id)       ON DELETE CASCADE,
    FOREIGN KEY (rated_by)    REFERENCES users(id)       ON DELETE CASCADE,
    FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE
);

-- ── ATTENDANCE ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS attendance (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id    INT UNSIGNED NOT NULL,
    classroom_id  INT UNSIGNED NOT NULL,
    date          DATE         NOT NULL,
    status        ENUM('present','absent','late','excused') NOT NULL DEFAULT 'present',
    marked_by     INT UNSIGNED NOT NULL,
    notes         VARCHAR(255) DEFAULT NULL,
    UNIQUE KEY uq_attendance (student_id, classroom_id, date),
    FOREIGN KEY (student_id)   REFERENCES users(id)       ON DELETE CASCADE,
    FOREIGN KEY (classroom_id) REFERENCES classrooms(id)  ON DELETE CASCADE,
    FOREIGN KEY (marked_by)    REFERENCES users(id)       ON DELETE RESTRICT
);

-- ── POSTS ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS posts (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    author_id    INT UNSIGNED  NOT NULL,
    title        VARCHAR(200)  NOT NULL,
    body         TEXT          NOT NULL,
    attachment   VARCHAR(255)  DEFAULT NULL,
    created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at  TIMESTAMP     DEFAULT NULL,
    deleted_by  INT UNSIGNED DEFAULT NULL,

    FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (author_id)    REFERENCES users(id)       ON DELETE CASCADE,
);

COMMIT;
