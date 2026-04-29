<?php
session_start();
require '../db.php';

$teacherId = $_SESSION['id'] ?? null;
$teacherName = $_SESSION['full_name'] ?? 'Professor Carter';
$teacherEmail = $_SESSION['email'] ?? 'teacher@example.com';
$teacherRole = $_SESSION['role'] ?? null;

if ($teacherId === null || $teacherRole !== 'teacher') {
    header('Location: ../index.php');
    exit;
}

$subjectMessage = '';
$subjectMessageKind = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_subject') {
    $subjectName = trim((string)($_POST['subject_name'] ?? ''));
    $subjectCode = strtoupper(trim((string)($_POST['subject_code'] ?? '')));
    $subjectDescription = trim((string)($_POST['subject_description'] ?? ''));

    if ($subjectName === '') {
        $subjectMessage = 'Subject name cannot be blank.';
        $subjectMessageKind = 'error';
    } elseif ($subjectCode === '') {
        $subjectMessage = 'Subject code cannot be blank.';
        $subjectMessageKind = 'error';
    } else {
        $stmt = $conn->prepare('INSERT INTO subjects (teacher_id, name, code, description) VALUES (?, ?, ?, ?)');

        if ($stmt) {
            $stmt->bind_param('isss', $teacherId, $subjectName, $subjectCode, $subjectDescription);
            $ok = $stmt->execute();

            if ($ok) {
                $subjectMessage = 'Class added successfully.';
                $subjectMessageKind = 'success';
            } elseif ($stmt->errno === 1062) {
                $subjectMessage = 'That subject code is already in use.';
                $subjectMessageKind = 'error';
            } else {
                $subjectMessage = 'Unable to add the class right now.';
                $subjectMessageKind = 'error';
            }

            $stmt->close();
        } else {
            $subjectMessage = 'Unable to prepare the class insert.';
            $subjectMessageKind = 'error';
        }
    }
}

$subjects = [];
$subjectsStmt = $conn->prepare('SELECT id, name, code, description, created_at FROM subjects WHERE teacher_id = ? ORDER BY created_at DESC, id DESC');
if ($subjectsStmt) {
    $subjectsStmt->bind_param('i', $teacherId);
    $subjectsStmt->execute();
    $subjectsResult = $subjectsStmt->get_result();
    if ($subjectsResult) {
        $subjects = $subjectsResult->fetch_all(MYSQLI_ASSOC);
    }
    $subjectsStmt->close();
}

$todayStats = [
    'present_count' => 0,
    'late_count' => 0,
    'absent_count' => 0
];

$studentAttendanceRows = [];

$statsStmt = $conn->prepare(
    "SELECT
        COALESCE(SUM(CASE WHEN attendance.status = 'present' THEN 1 ELSE 0 END), 0) AS present_count,
        COALESCE(SUM(CASE WHEN attendance.status = 'late' THEN 1 ELSE 0 END), 0) AS late_count,
        COALESCE(SUM(CASE WHEN attendance.status = 'absent' THEN 1 ELSE 0 END), 0) AS absent_count
     FROM attendance
     JOIN sessions ON sessions.id = attendance.session_id
     JOIN subjects ON subjects.id = sessions.subject_id
     JOIN enrollments
       ON enrollments.subject_id = subjects.id
      AND enrollments.student_id = attendance.student_id
     WHERE subjects.teacher_id = ?
       AND attendance.marked_by = ?
       AND DATE(attendance.marked_at) = CURDATE()"
);

if ($statsStmt) {
    $statsStmt->bind_param('ii', $teacherId, $teacherId);
    $statsStmt->execute();
    $statsResult = $statsStmt->get_result();
    if ($statsResult) {
        $statsRow = $statsResult->fetch_assoc();
        if ($statsRow) {
            $todayStats = [
                'present_count' => (int)($statsRow['present_count'] ?? 0),
                'late_count' => (int)($statsRow['late_count'] ?? 0),
                'absent_count' => (int)($statsRow['absent_count'] ?? 0)
            ];
        }
    }
    $statsStmt->close();
}

$studentAttendanceStmt = $conn->prepare(
    "SELECT
        users.id AS student_id,
        users.full_name,
        GROUP_CONCAT(DISTINCT subjects.code ORDER BY subjects.code SEPARATOR ', ') AS subject_codes,
        COALESCE((
            SELECT attendance_today.status
            FROM attendance AS attendance_today
            JOIN sessions AS sessions_today
              ON sessions_today.id = attendance_today.session_id
            JOIN subjects AS subjects_today
              ON subjects_today.id = sessions_today.subject_id
            WHERE attendance_today.student_id = users.id
              AND subjects_today.teacher_id = ?
              AND DATE(attendance_today.marked_at) = CURDATE()
            ORDER BY attendance_today.marked_at DESC, attendance_today.id DESC
            LIMIT 1
        ), 'NA') AS status_today,
        COALESCE(COUNT(DISTINCT sessions.id), 0) AS total_sessions,
        COALESCE(SUM(CASE WHEN attendance.status = 'present' THEN 1 ELSE 0 END), 0) AS present_count,
        COALESCE(SUM(CASE WHEN attendance.status = 'late' THEN 1 ELSE 0 END), 0) AS late_count
     FROM enrollments
     JOIN users
       ON users.id = enrollments.student_id
     JOIN subjects
       ON subjects.id = enrollments.subject_id
     LEFT JOIN sessions
       ON sessions.subject_id = subjects.id
     LEFT JOIN attendance
       ON attendance.session_id = sessions.id
      AND attendance.student_id = enrollments.student_id
     WHERE subjects.teacher_id = ?
     GROUP BY users.id, users.full_name
     ORDER BY users.full_name ASC"
);

if ($studentAttendanceStmt) {
    $studentAttendanceStmt->bind_param('ii', $teacherId, $teacherId);
    $studentAttendanceStmt->execute();
    $studentAttendanceResult = $studentAttendanceStmt->get_result();
    if ($studentAttendanceResult) {
        while ($studentAttendance = $studentAttendanceResult->fetch_assoc()) {
            $totalSessions = (int)($studentAttendance['total_sessions'] ?? 0);
            $presentCount = (int)($studentAttendance['present_count'] ?? 0);
            $lateCount = (int)($studentAttendance['late_count'] ?? 0);
            $attendancePercent = '0%';

            if ($totalSessions > 0) {
                $attendancePercent = (string)round((($presentCount + $lateCount) / $totalSessions) * 100) . '%';
            }

            $statusToday = (string)($studentAttendance['status_today'] ?? 'NA');
            $statusTodayLower = strtolower($statusToday);
            if (!in_array($statusTodayLower, ['present', 'late', 'absent'], true)) {
                $statusTodayLower = 'na';
                $statusToday = 'NA';
            } else {
                $statusToday = ucfirst($statusTodayLower);
            }

            $studentAttendanceRows[] = [
                'full_name' => (string)$studentAttendance['full_name'],
                'subject_codes' => (string)($studentAttendance['subject_codes'] ?? ''),
                'status_today' => $statusToday,
                'status_today_class' => $statusTodayLower,
                'attendance_percent' => $attendancePercent
            ];
        }
    }
    $studentAttendanceStmt->close();
}

$subjectNoticeClass = 'notice-box';
if ($subjectMessageKind === 'success') {
    $subjectNoticeClass .= ' status-present';
} elseif ($subjectMessageKind === 'error') {
    $subjectNoticeClass .= ' status-absent';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Teacher Dashboard | BigBrother</title>
  <script src="../assets/theme.js"></script>
  <link rel="stylesheet" href="../assets/style.css">
  <link rel="stylesheet" href="../assets/eye.css">
</head>
<body>
  <div class="page-shell">
    <div class="layout-grid">
      <aside class="sidebar">
        <div class="brand-badge">
          <span class="logo-dot"></span>
          Teacher Panel
        </div>
        <div class="bb-login-hero">
          <span class="bb-icon-eye bb-icon-eye--fluid" data-follow-mouse data-follow-enabled="false" role="img" aria-label="Camera lens"></span>
        </div>
        <h2>BigBrother</h2>
        <p class="dashboard-subtitle">Attendance management made simple.</p>
        <nav class="nav-links">
          <a class="active" href="dashboard.php">Dashboard</a>
          <a href="#subjectsSection">Classes</a>
          <a href="#attendanceSection">Attendance</a>
          <a href="#contactSection">Contact</a>
        </nav>

        <div class="theme-toggle">
          <div class="theme-toggle__card">
            <div class="theme-toggle__copy">
              <span class="theme-toggle__label">Theme</span>
              <span class="theme-toggle__hint">Blue / Dystopia</span>
            </div>
            <label class="theme-toggle__switch" for="themeToggle">
              <input type="checkbox" id="themeToggle" aria-label="Toggle dystopian theme">
              <span class="theme-toggle__slider"></span>
            </label>
          </div>
        </div>

        <nav class="nav-links">
          <a href="../logout.php">Logout</a>
        </nav>
      </aside>

      <main class="main-panel">
        <section class="navbar">
          <div>
            <h1>Teacher Dashboard</h1>
            <p class="dashboard-subtitle">Mark attendance, check the class summary, and message students.</p>
          </div>

          <div class="topbar-user">
            <div>
              <strong><?php echo htmlspecialchars($teacherName); ?></strong>
              <p class="muted"><?php echo htmlspecialchars($teacherEmail); ?></p>
            </div>
            <div class="avatar">T</div>
          </div>
        </section>

        <section class="panel-grid">
          <div class="stat-card">
            <p class="muted">Present today</p>
            <div class="stat-value" id="presentCount"><?php echo $todayStats['present_count']; ?></div>
          </div>

          <div class="stat-card">
            <p class="muted">Late today</p>
            <div class="stat-value" id="lateCount"><?php echo $todayStats['late_count']; ?></div>
          </div>

          <div class="stat-card">
            <p class="muted">Absent today</p>
            <div class="stat-value" id="absentCount"><?php echo $todayStats['absent_count']; ?></div>
          </div>

          <section class="content-card medium" id="subjectsSection">
            <div class="card-header">
              <div>
                <h3>Add a New Class</h3>
                <p class="muted">Create a subject with a course name, code, and optional description.</p>
              </div>
            </div>

            <?php if ($subjectMessage !== ''): ?>
              <div class="<?php echo htmlspecialchars($subjectNoticeClass, ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($subjectMessage, ENT_QUOTES, 'UTF-8'); ?>
              </div>
            <?php endif; ?>

            <form class="form-grid" method="POST" action="dashboard.php#subjectsSection">
              <input type="hidden" name="action" value="create_subject">

              <div class="form-row two">
                <div class="form-group">
                  <label for="subject_name">Subject Name</label>
                  <input class="text-input" type="text" id="subject_name" name="subject_name" placeholder="Web Technologies" required>
                </div>

                <div class="form-group">
                  <label for="subject_code">Subject Code</label>
                  <input class="text-input" type="text" id="subject_code" name="subject_code" placeholder="CSCI 4410" required>
                </div>
              </div>

              <div class="form-group">
                <label for="subject_description">Description</label>
                <textarea class="text-area" id="subject_description" name="subject_description" placeholder="Brief details about the class, schedule, or goals."></textarea>
              </div>

              <div class="btn-row">
                <button class="btn btn-primary" type="submit">Add Class</button>
              </div>
            </form>
          </section>

          <section class="content-card medium">
            <div class="card-header">
              <div>
                <h3>Your Classes</h3>
                <p class="muted"><?php echo count($subjects); ?> subject<?php echo count($subjects) === 1 ? '' : 's'; ?> created.</p>
              </div>
            </div>

            <?php if (count($subjects) === 0): ?>
              <div class="notice-box">No classes added yet. Your new subject will show up here.</div>
            <?php else: ?>
              <table>
                <thead>
                  <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Description</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($subjects as $subject): ?>
                    <tr class="is-clickable" tabindex="0" data-href="<?php echo htmlspecialchars('class.php?subject_id=' . (int)$subject['id'], ENT_QUOTES, 'UTF-8'); ?>">
                      <td><?php echo htmlspecialchars($subject['code'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($subject['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars((string)($subject['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </section>

          <section class="table-wrap full">
            <div class="card-header">
              <div>
                <h3>Student Attendance</h3>
                <p class="muted">All students enrolled in your classes, with today's latest status and overall attendance across your subjects.</p>
              </div>
            </div>

            <table>
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Subject</th>
                  <th>Status Today</th>
                  <th>Attendance %</th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($studentAttendanceRows) === 0): ?>
                  <tr>
                    <td colspan="4">No students are enrolled in your classes yet.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($studentAttendanceRows as $studentAttendance): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($studentAttendance['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($studentAttendance['subject_codes'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td>
                        <?php if ($studentAttendance['status_today_class'] === 'present'): ?>
                          <span class="status-pill status-present"><?php echo htmlspecialchars($studentAttendance['status_today'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php elseif ($studentAttendance['status_today_class'] === 'late'): ?>
                          <span class="status-pill status-late"><?php echo htmlspecialchars($studentAttendance['status_today'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php elseif ($studentAttendance['status_today_class'] === 'absent'): ?>
                          <span class="status-pill status-absent"><?php echo htmlspecialchars($studentAttendance['status_today'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php else: ?>
                          <span class="muted"><?php echo htmlspecialchars($studentAttendance['status_today'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                      </td>
                      <td><?php echo htmlspecialchars($studentAttendance['attendance_percent'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </section>

          <section class="contact-card" id="contactSection">
            <div class="card-header">
              <div>
                <h3>Teacher to Student Contact</h3>
              </div>
            </div>

            <form class="form-grid" onsubmit="sendMessage(event, 'teacher')">
              <div class="form-row two">
                <div class="form-group">
                  <label for="teacher_name">Your Name</label>
                  <input class="text-input" type="text" id="teacher_name" name="from_name" value="<?php echo htmlspecialchars($teacherName); ?>" required>
                </div>
                <div class="form-group">
                  <label for="teacher_email">Your Email</label>
                  <input class="text-input" type="email" id="teacher_email" name="reply_to" value="<?php echo htmlspecialchars($teacherEmail); ?>" required>
                </div>
              </div>

              <div class="form-row two">
                <div class="form-group">
                  <label for="target_name">Student Name</label>
                  <input class="text-input" type="text" id="target_name" name="target_name" placeholder="Student name" required>
                </div>
                <div class="form-group">
                  <label for="target_email">Student Email</label>
                  <input class="text-input" type="email" id="target_email" name="target_email" placeholder="student@example.com" required>
                </div>
              </div>

              <div class="form-group">
                <label for="teacher_message">Message</label>
                <textarea class="text-area" id="teacher_message" name="message" placeholder="Write your message here..." required></textarea>
              </div>

              <div class="btn-row">
                <button class="btn btn-primary" type="submit">Send Message</button>
              </div>

              <p class="form-status muted"></p>
            </form>
          </section>
        </section>
      </main>
    </div>
  </div>

  <script src="../assets/email-config.js?v=2"></script>
  <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
  <script src="../assets/eye-follow.js" defer></script>
  <script>
    if (window.emailjs && window.EMAILJS_CONFIG) {
      emailjs.init(window.EMAILJS_CONFIG.publicKey);
    }
  </script>
  <script src="../assets/script.js?v=2"></script>
  <script>
    document.querySelectorAll('tr.is-clickable[data-href]').forEach((row) => {
      const href = row.getAttribute('data-href');
      if (!href) return;

      row.addEventListener('click', () => {
        window.location.href = href;
      });

      row.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          window.location.href = href;
        }
      });
    });
  </script>
</body>
</html>
