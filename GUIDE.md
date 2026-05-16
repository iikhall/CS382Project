# School Dashboard — User & Logic Guide (CS382)

An internal school-management web app that merges **discipline tracking** and
**student-indicators analytics** into one system. PHP (OOP + PDO) + MySQL,
jQuery + AJAX, session auth, Chart.js. English-only, left-to-right.

---

## 1. Setup (run once)

1. **Import the database**
   Open Laragon → Database (HeidiSQL / phpMyAdmin) and run `schema.sql`.
   It creates the `cs382project` database, 9 tables, and all seed data
   (12 classes, sample subjects, attendance months, stat cards).

2. **Seed the login users**
   Visit `setup.php` once in the browser
   (e.g. `http://cs382project.test/setup.php`).
   It creates three accounts with bcrypt-hashed passwords:

   | Role       | Username | Password    |
   |------------|----------|-------------|
   | Admin      | `admin`  | `Admin@123` |
   | Supervisor | `super1` | `Super@123` |

   It is idempotent — running it again just reports "already existed".

3. **Log in** at `login.php`.

> DB connection lives in `classes/Database.php` (Laragon defaults:
> host `127.0.0.1`, user `root`). Adjust there if your MySQL differs.

> **Upgrading an existing database?** Strongly recommended: **re-import
> `schema.sql`** (it drops/recreates + reseeds), then **re-run `setup.php`**.
> That guarantees the final 2-role shape and the new `grade_supervisors`
> table. To migrate in place instead, run once:
> ```sql
> ALTER TABLE users MODIFY role ENUM('admin','supervisor') NOT NULL DEFAULT 'supervisor';
> ALTER TABLE classes DROP FOREIGN KEY fk_classes_supervisor;       -- if it exists
> ALTER TABLE classes DROP COLUMN supervisor_user_id;               -- if it exists
> ALTER TABLE subjects DROP FOREIGN KEY fk_subjects_teacher;        -- if it exists
> ALTER TABLE subjects DROP COLUMN teacher_user_id;                 -- if it exists
> CREATE TABLE grade_supervisors (
>   grade VARCHAR(20) NOT NULL PRIMARY KEY,
>   supervisor_user_id INT UNSIGNED NULL,
>   CONSTRAINT fk_gs_user FOREIGN KEY (supervisor_user_id)
>     REFERENCES users(id) ON DELETE SET NULL
> );
> INSERT INTO grade_supervisors (grade) SELECT DISTINCT grade FROM classes;
> ```
> Then set any non-admin users to `supervisor`.

---

## 2. How the system is organized (the logic)

### Layers

```
Pages (*.php)        →  render HTML using the shared shell
includes/            →  db.php (bootstrap+autoload), auth_check.php (guards),
                        header.php / footer.php (shell)
classes/             →  OOP models (Database, User, ClassModel, Subject,
                        Star, Stat, Attendance, Feedback, Message, Snapshot)
api/ (*.php)         →  AJAX endpoints, every response is JSON
assets/css/main.css  →  single design-system file (all styling)
assets/js/           →  app.js (shared toast) + one script per page
```

### Request flow

1. Every page first includes `includes/db.php` (starts the session, sets up
   the PDO connection, autoloads model classes).
2. It then includes `includes/auth_check.php` and calls a **guard**:
   - `require_login()` — must be signed in (any role).
   - `require_admin()` — must be an admin, else redirected to the dashboard.
3. Dynamic actions (saving scores, awarding a star, sending a message, etc.)
   are **not** form post-backs. The page's jQuery script sends an **AJAX**
   request to an `api/*.php` endpoint, which validates input, updates MySQL,
   and returns JSON. The UI updates live (no full reload).
4. API endpoints re-check auth independently
   (`api_require_login()` / `api_require_admin()`), so security never relies
   on the UI hiding a button.

### Authentication

- `User::login()` looks up the username, verifies the password with
  `password_verify()` against the stored bcrypt hash, regenerates the
  session ID, and stores the user (id, role, name) in `$_SESSION`.
- `logout.php` destroys the session and clears the cookie.

### The weekly cycle (auto-reset)

- A **Sunday-based week-of-year number** is computed in
  `ClassModel::currentWeek()`.
- On every dashboard load, `autoResetIfNewWeek()` compares that number to
  `_last_reset_week` (stored in the `stats` table as hidden meta).
- If the week changed, `resetCurrent()` runs in a transaction: all class
  scores → 0, motivation notes cleared, **all stars deleted**, and the new
  week number is stamped.
- **Archived snapshots are never touched** — they are a frozen JSON copy,
  so history stays accurate.

### Snapshots

`settings.php` → "Save to Archive" calls `Snapshot::save()`, which builds a
**denormalized JSON copy** of every class (scores + its stars) and stores it
in `week_snapshots.classes_json`. Editing classes later does not change past
snapshots. Snapshots can be viewed, downloaded as JSON, or deleted.

---

## 3. Roles — who can do what

Two roles only:

- **Admin** — system/IT owner. Full technical & administrative control:
  users, classes, courses, settings, messages, rankings, any PDF. Assigns
  one supervisor per grade. Not expected to evaluate daily, but *can*.
- **Supervisor** — assigned by the admin to a **whole grade** (e.g.
  Grade 1 = classes 1-A…1-D). Within that grade only: views the
  dashboard, evaluates classes (scores/notes + stars), sets the
  **teacher name** on each course, and generates a PDF report of the
  grade. No access to other grades, users, settings, messages, or the
  school-wide rankings.

**Teachers are not system users** — a course's teacher is just a name
the supervisor (or admin) types on the course. Teachers cannot log in.

**Assignment model:** Admin → assigns a **supervisor** to a **grade**
(`manage.php` → Grade Supervisors). Supervisor (or admin) → sets the
**teacher name** on each **course** of a class in that grade
(`class.php` → Courses & Teachers).

| Capability                                   | Supervisor (own grade) | Admin |
|----------------------------------------------|:----------------------:|:-----:|
| Log in / log out                             |   ✅                   |  ✅   |
| Dashboard + charts                           |   ✅ (own grade)       |  ✅ (all) |
| Open & **evaluate** a class + award stars    |   ✅ (own grade)       |  ✅ (any) |
| Set **teacher name** on a course             |   ✅ (own grade)       |  ✅ (any) |
| Export **PDF report**                        |   ✅ (own grade)       |  ✅ (school / any class) |
| View **Rankings** (school-wide, confidential)|   ❌                   |  ✅   |
| Add / delete **classes**                     |   ❌                   |  ✅   |
| Add / edit / delete **courses**              |   ❌                   |  ✅   |
| **Assign supervisor** to a grade             |   ❌                   |  ✅   |
| **Messages** (read / clear)                  |   ❌                   |  ✅   |
| **Settings** (school info, snapshots, reset) |   ❌                   |  ✅   |
| **Users** (create / edit / delete accounts)  |   ❌                   |  ✅   |

Enforcement is server-side, not just hidden nav: `require_admin()` and
`ClassModel::canEvaluate()` (admin, or the grade's supervisor) guard
every page and `api/*` endpoint. A supervisor opening a class/report
outside their grade is redirected; a tampered AJAX call gets `403 JSON`.

---

## 4. Page-by-page usage

### Login (`login.php`) — everyone
Enter username + password. The form submits via AJAX to `api/login.php`.
Wrong credentials show an inline red error without reloading; success
redirects to the dashboard.

### Dashboard (`dashboard.php`) — all roles (scoped)
- **3 stat cards** — headline indicators, colored accent border.
- **Class grid** — admin sees all 12 classes grouped by grade; a
  **supervisor sees only their assigned grade's classes** (an
  empty-state message if no grade assigned). Each card → class detail.
- **Attendance chart** — green area line over 12 Hijri months; no-data
  months render as a gap with an em-dash (`—`).
- **Academic donuts** — per course; supervisors see only their grade.
- Charts fetched via AJAX from `api/dashboard_data.php` (also scoped).
- The Sunday-based weekly auto-reset only fires on an **admin** visit.

### Class detail (`class.php?id=N`)
- A **supervisor** can only open a class in a grade they supervise
  (other IDs → redirected). Admin can open any class.
- **Motivational Stars (left)** — admin or the grade's supervisor can
  **Award Star**.
- **Weekly Evaluation (right)** — sliders for Order / Cleanliness /
  Behavior with live `/30` total, leader, supervisor label, notes,
  **Save Changes**. Persists via `api/class_update.php`, re-authorized
  by `ClassModel::canEvaluate()`.
- **Courses & Teachers panel** — admin and the grade's supervisor get a
  **teacher-name text box** per course + Save
  (`api/subject_assign_teacher.php`).
- **PDF Report** button → that class's printable report.

### Rankings (`rankings.php`) — admin only
A **podium** (1st center/gold, 2nd silver, 3rd copper) plus a ranked
list. Order = **total score desc, star count desc**
(`ClassModel::ranked()`). Confidential — supervisors have no nav link
and are redirected.

### Reports (`report.php`) — admin & supervisor
Printable report; **Generate PDF** opens the browser print dialog
(“Save as PDF” — no server-side PDF library needed).
- No `?id` → **admin: school-wide report** (all classes ranked);
  **supervisor: their grade report** (only their grade's classes).
- `?id=N` → **single-class report**: discipline scores, grade
  supervisor, courses (with teacher names), stars log. A supervisor may
  only open a class inside their grade.

### Classes (`manage.php`) — admin only
- **Grade Supervisors** (top card) — one row per grade with a
  **supervisor dropdown** + Save (`api/grade_assign_supervisor.php`).
  This is how a supervisor gets the grade they manage.
- **Add Class** — code, display name, grade, section, semester,
  supervisor label. **Delete Class** — courses/stars cascade away.
- **Add / Edit / Delete Course** — per class: name, **teacher name**
  (free text) and the grade-band distribution driving the donuts.

### Messages (`messages.php`) — admin only
Read any stored contact message with a colored category tag, recipient,
timestamp, and sender, plus rating summary cards. **Clear All Messages**
(with a confirmation modal) wipes them.

### Settings (`settings.php`) — admin only
- **School Information** — edit school / principal / vice-principal names
  (these feed the Award-Star dropdown and branding).
- **Save Current Week** — pick a date and archive a snapshot.
- **Weekly Archive** — list of saved weeks; each row has **View**
  (JSON modal), **Download** (JSON file), **Delete**. **Clear All** wipes
  the archive.
- **Start New Week** (red danger card) — **Reset Current Week Data** zeros
  all scores and clears all stars immediately (archive untouched), behind a
  confirmation modal.

### Users (`users.php`) — admin only
Table of all accounts. **Add User** opens a modal (username, display name,
role = admin / vice_principal / teacher, password ≥ 6 chars). **Edit**
reuses the modal (username locked; leave password blank to keep it).
**Delete** asks for confirmation. Safety guards (enforced server-side):
- You cannot delete your own account.
- You cannot delete the **last** admin.
- You cannot demote the **only** admin.

### Logout (`logout.php`)
Ends the session and returns to the login screen.

---

## 5. Business rules preserved from the original systems

- Scores are integers **0–10**; total is **0–30**.
- Platform ratings are **1–5**.
- Contact messages are **≤ 10 words**, with allow-listed recipients and
  categories — enforced in the browser, in PHP, and by MySQL `CHECK`s.
- Attendance uses the **12 Hijri months** (English transliterations);
  value `0` means "no data" and renders as a gap.
- The week boundary is **Sunday-based**; a new week auto-resets scores/stars.
- Snapshots are a **denormalized JSON copy**, so history never changes.
- Star awards reset together with scores each new week.

---

## 6. Quick role demo script

**As `admin` / `Admin@123`:**
1. Log in → full nav (Dashboard, Rankings, Reports, Classes, Messages,
   Settings, Users).
2. Classes → **Grade Supervisors** card → set **Grade 1 → Supervisor
   One** → Save.
3. Open a Grade 1 class → in **Courses & Teachers**, type a teacher
   name on a course → Save. (Also editable via the Classes course modal.)
4. Open any class → drag sliders (live `/30`) → Save → "Saved ✓".
5. Reports → Generate PDF (school-wide), then a class's **PDF Report**.

**As `super1` / `Super@123` (Supervisor):**
1. Log in → nav shows Dashboard, Reports, Logout only.
2. Dashboard shows **only Grade 1** classes (the grade admin assigned).
3. Open a Grade 1 class → evaluate it (sliders + Save), award a star,
   and set a course's teacher name in **Courses & Teachers**.
4. Reports → Generate PDF → it's the **Grade 1** report only.
5. Try a class URL from another grade, or `rankings.php` /
   `settings.php` / `users.php` → bounced away.

---

## 7. Notes

- The **Feedback** page was removed. **Messages** remains for admins to
  review/clear messages already stored; no new ones are created.
- **PDF export** uses the browser's print-to-PDF (this environment has no
  PHP PDF library/composer); the report pages have dedicated print CSS.
- **Deferred** (not yet built — listed in the role brief, flagged for a
  later round): a 4th *Participation* score, teacher attendance/absence
  recording, subject-grade entry by teachers, a weekly-report approval
  workflow, audit logs, and data archiving. The 3-score model
  (Order / Cleanliness / Behavior) is unchanged.
- All other listed pages are complete and functional.
