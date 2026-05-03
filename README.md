# Assur — Educational Management System

A web-based school management platform built with **Pure PHP 8.1+**, **MySQL**, and **Vanilla JS**. Three role-based dashboards for admins, teachers, and students — no framework required.

---

## Table of Contents

- [Requirements](#requirements)
- [Project Structure](#project-structure)
- [Installation](#installation)
- [Default Login](#default-login)
- [User Roles](#user-roles)
- [Workflow](#workflow)
- [API Reference](#api-reference)
- [Database Schema](#database-schema)
- [Dashboards](#dashboards)
- [Security Notes](#security-notes)

---

## Requirements

| Tool | Minimum Version |
|------|----------------|
| PHP | 8.1 |
| MySQL | 8.0 |
| Composer | Any recent version |

---

## Project Structure

```
assur/
├── index.php                        ← Entry point — routes API vs frontend HTML files
├── schema.sql                       ← Full database schema, run this first
├── composer.json                    ← PHP dependencies (vlucas/phpdotenv)
├── .env.example                     ← Environment variable template
├── .gitignore
│
├── config/
│   └── db.php                       ← Database singleton — call db() anywhere
│
├── src/
│   ├── helpers.php                  ← Shared utilities: json_out, require_role, body, etc.
│   ├── router.php                   ← All API routes registered here
│   └── controllers/
│       ├── auth.php                 ← login, logout, register, me, change-password
│       ├── users.php                ← User CRUD + stats (admin only)
│       ├── classrooms.php           ← Classrooms, members, subjects, enrollment
│       ├── grades.php               ← Grades, ratings, attendance
│       └── posts.php                ← Posts and announcements
│
└── public/
    ├── index.html                   ← Login page (all roles)
    ├── admin.html                   ← Admin dashboard
    ├── teacher.html                 ← Teacher / educational staff dashboard
    └── student.html                 ← Student portal
```

---

## Installation

### 1. Clone or download the project

```bash
cd /your/web/directory
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Set up environment variables

```bash
cp .env.example .env
```

Open `.env` and fill in your database credentials:

```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=assur
DB_USER=your_db_user
DB_PASS=your_db_password

APP_ENV=development
SESSION_LIFETIME=7200
```

### 4. Create the database and import the schema

```bash
mysql -u root -p -e "CREATE DATABASE assur CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p assur < schema.sql
```

Or from inside the MySQL shell:

```sql
SOURCE /path/to/assur/schema.sql;
```

### 5. Start the development server

```bash
php -S localhost:8000 index.php
```

Open your browser at **http://localhost:8000**

---

## Default Login

The schema seeds one admin account automatically:

| Field | Value |
|-------|-------|
| Phone | `00000` |
| Password | `password` |
| Role | Admin |

> **Change this password immediately after your first login.**

---

## User Roles

### Admin
- Full access to the entire system
- Create, edit, deactivate, and delete user accounts
- Create and delete classrooms
- Assign teachers and students to classrooms
- Add, assign, and delete subjects within classrooms
- View system-wide stats (user counts, classrooms, posts)
- Publish and delete any post

### Educational User (Teacher / Staff)
- View classrooms they are assigned to
- Add grades for students per subject
- Rate students (1–5 stars) with optional comments
- Mark daily attendance (present, absent, late, excused)
- Publish posts and announcements visible to all users
- Delete their own posts

### Student
- Browse all classrooms and self-enroll or leave
- View their own grades per subject with letter grade and progress bar
- View their ratings from teachers
- View their full attendance history with a present/absent summary
- Read all posts and announcements

> **Note:** Only admins can create user accounts. Students and teachers cannot self-register.

---

## Workflow

Follow this order when setting up the system for the first time:

```
1. Admin logs in at http://localhost:8000
2. Admin creates user accounts for teachers and students
3. Admin creates classrooms
4. Admin opens ⚙ Manage Members on each classroom:
   ├── Teachers tab  → assign teachers to the classroom
   ├── Students tab  → assign students  (or students self-enroll)
   └── Subjects tab  → add subjects, optionally assign a teacher to each
5. Teacher logs in → selects classroom → adds grades, ratings, attendance
6. Student logs in → views grades, ratings, attendance, announcements
```

---

## API Reference

All endpoints are prefixed with `/api`. The API accepts and returns JSON. Authentication is session-based via PHP sessions and cookies.

---

### Auth

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/api/login` | — | Login with phone and password |
| `POST` | `/api/logout` | ✓ | Destroy the current session |
| `GET` | `/api/me` | ✓ | Get the currently logged-in user |
| `POST` | `/api/register` | Admin | Create a new user account |
| `POST` | `/api/change-password` | ✓ | Change your own password |

**POST `/api/login`**
```json
{
  "phone": "09999",
  "password": "yourpassword"
}
```

**POST `/api/register`** *(admin only)*
```json
{
  "full_name": "Sara Ahmed",
  "phone": "01234",
  "email": "sara@school.com",
  "password": "securepass",
  "role": "student"
}
```
`role` must be one of: `admin`, `educational_user`, `student`.

**POST `/api/change-password`**
```json
{
  "old_password": "current",
  "new_password": "newpassword"
}
```

---

### Users

All user endpoints require **Admin** role.

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/users` | List all users. Filter by `?role=student` |
| `GET` | `/api/users/{id}` | Get a single user |
| `PUT` | `/api/users/{id}` | Update name, email, or role |
| `DELETE` | `/api/users/{id}` | Permanently delete a user |
| `PATCH` | `/api/users/{id}/toggle` | Toggle active / inactive status |
| `GET` | `/api/stats` | System-wide counts of users, classrooms, posts |
| `GET` | `/api/teachers` | List all active teachers (for dropdowns) |
| `GET` | `/api/students` | List all active students (for dropdowns) |

---

### Classrooms

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/api/classrooms` | ✓ | List classrooms. Admin sees all; others see only their own |
| `GET` | `/api/classrooms/browse` | ✓ | All classrooms with `is_enrolled` flag — for student enrollment page |
| `POST` | `/api/classrooms` | Admin | Create a new classroom |
| `DELETE` | `/api/classrooms/{id}` | Admin | Delete a classroom and all its data |
| `GET` | `/api/classrooms/{id}/members` | ✓ | List all members of a classroom |
| `POST` | `/api/classrooms/{id}/members` | Admin | Assign a user to a classroom |
| `DELETE` | `/api/classrooms/{id}/members/{user_id}` | Admin | Remove a member from a classroom |
| `POST` | `/api/classrooms/{id}/enroll` | Student | Self-enroll in a classroom |
| `DELETE` | `/api/classrooms/{id}/enroll` | Student | Leave a classroom |

**POST `/api/classrooms`**
```json
{
  "name": "Grade 10 — Section A",
  "grade": "Grade 10",
  "description": "Main science track"
}
```

**POST `/api/classrooms/{id}/members`** *(admin only)*
```json
{
  "user_id": 5,
  "role": "teacher"
}
```
`role` must be `teacher` or `student`.

---

### Subjects

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/api/classrooms/{id}/subjects` | ✓ | List subjects in a classroom |
| `POST` | `/api/classrooms/{id}/subjects` | Admin / Teacher | Add a subject to a classroom |
| `DELETE` | `/api/subjects/{id}` | Admin | Delete a subject and all its grades |

**POST `/api/classrooms/{id}/subjects`**
```json
{
  "name": "Mathematics",
  "teacher_id": 4
}
```
`teacher_id` is optional. If provided, the user must already be assigned as a teacher in this classroom.

---

### Grades

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/api/grades` | Admin / Teacher | Add a grade for a student |
| `GET` | `/api/students/{id}/grades` | ✓ | Get all grades for a student |
| `GET` | `/api/subjects/{id}/grades` | Admin / Teacher | Get all grades for a subject |
| `DELETE` | `/api/grades/{id}` | Admin / Teacher | Delete a grade |

**POST `/api/grades`**
```json
{
  "student_id": 12,
  "subject_id": 3,
  "score": 88,
  "max_score": 100,
  "label": "Midterm",
  "notes": "Good improvement"
}
```

---

### Ratings

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/api/ratings` | Admin / Teacher | Rate a student (1–5 stars) |
| `GET` | `/api/students/{id}/ratings` | ✓ | Get all ratings for a student |

**POST `/api/ratings`**
```json
{
  "student_id": 12,
  "classroom_id": 2,
  "score": 4,
  "comment": "Very engaged in class discussions"
}
```
`score` must be between `1` and `5`.

---

### Attendance

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/api/attendance` | Admin / Teacher | Mark attendance for a student |
| `GET` | `/api/students/{id}/attendance` | ✓ | Get full attendance history for a student |
| `GET` | `/api/classrooms/{id}/attendance` | Admin / Teacher | Get attendance for a class on a date |

**POST `/api/attendance`**
```json
{
  "student_id": 12,
  "classroom_id": 2,
  "date": "2026-05-03",
  "status": "present",
  "notes": ""
}
```
`status` must be one of: `present`, `absent`, `late`, `excused`.

Submitting attendance for the same student + classroom + date a second time **updates** the existing record rather than creating a duplicate.

**GET `/api/classrooms/{id}/attendance?date=YYYY-MM-DD`**
Returns all records for that classroom on the given date. Defaults to today if no date is provided.

---

### Posts

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/api/posts` | ✓ | Get all posts. Filter by `?classroom_id=` |
| `POST` | `/api/posts` | Admin / Teacher | Create a post |
| `PUT` | `/api/posts/{id}` | Admin / Author | Update a post |
| `DELETE` | `/api/posts/{id}` | Admin / Author | Delete a post |

**POST `/api/posts`**
```json
{
  "title": "Exam Schedule Update",
  "body": "The final exams have been moved to next week.",
  "classroom_id": null
}
```
Set `classroom_id` to `null` for a school-wide post, or provide an ID to target a specific classroom.

---

## Database Schema

### Tables

| Table | Description |
|-------|-------------|
| `users` | All users — admins, teachers, students |
| `classrooms` | Classroom definitions |
| `classroom_members` | Links users to classrooms with a `teacher` or `student` role |
| `subjects` | Subjects within a classroom, optionally linked to a teacher |
| `grades` | Student scores per subject with label and notes |
| `ratings` | Teacher star ratings (1–5) per student per classroom |
| `attendance` | Daily attendance per student per classroom |
| `posts` | Announcements — school-wide or classroom-specific |
| `sessions` | Session tracking table (reserved for future use) |

### Foreign key type rule

All foreign key columns that reference `users.id` must be `INT UNSIGNED` since `users.id` is defined as `INT UNSIGNED AUTO_INCREMENT`. Mismatched signedness causes MySQL error `3780`.

```sql
-- correct
some_column INT UNSIGNED DEFAULT NULL

-- wrong — causes FK error 3780
some_column INT DEFAULT NULL
```

---

## Dashboards

### Login — `http://localhost:8000`
Single login page for all roles. Includes a role selector (Admin / Teacher / Student). After a successful login, the user is automatically redirected to their role-specific dashboard.

### Admin — `/public/admin.html`
- **Dashboard** — stats overview: teacher count, student count, classrooms, posts, recent users, recent posts
- **Users** — searchable and filterable table with create, deactivate/activate, and delete
- **Classrooms** — card grid; each card has a **⚙ Manage Members** button opening a three-tab modal:
  - **Teachers tab** — assign or remove teachers
  - **Students tab** — assign or remove students
  - **Subjects tab** — add subjects with optional teacher assignment, delete subjects
- **Posts** — full post list with delete

### Teacher — `/public/teacher.html`
- **Dashboard** — assigned classrooms, recent posts, quick stats
- **Grades** — select classroom → subject populates → add grade per student with score, max score, label, and notes
- **Ratings** — interactive star picker (1–5) with optional comment per student
- **Attendance** — mark daily attendance by classroom and date, view records by date
- **Posts** — create and delete announcements visible to all users

### Student — `/public/student.html`
- **Overview** — personal stats: average score, number of subjects, attendance rate, average star rating; recent grades list and classroom list
- **My Grades** — full table with subject, classroom, score out of max, letter grade (A/B/C/D), label, teacher name, and date
- **My Ratings** — star ratings received from teachers with comments and dates
- **Attendance** — summary bar (present / absent / late / excused counts) and full history table
- **Enroll** — browse all classrooms with member counts; one-click enroll or leave
- **Announcements** — read all posts from teachers and admins

---

## Security Notes

- Passwords are hashed with **bcrypt** at cost 12 — never stored in plain text
- Session ID is regenerated on every successful login to prevent session fixation attacks
- All role checks are enforced **server-side** — the frontend redirect is cosmetic only
- Students can only read their own grades, ratings, and attendance — enforced per controller
- Only admins can create user accounts
- Set `APP_ENV=production` in `.env` to enable `Secure` and `SameSite=Strict` cookie flags
- Always run behind **HTTPS** in production
