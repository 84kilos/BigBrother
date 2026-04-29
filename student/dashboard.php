<?php
session_start();
require '../db.php';

$studentId = $_SESSION['id'] ?? null;
$studentName = $_SESSION['full_name'] ?? 'Student';
$studentEmail = $_SESSION['email'] ?? 'student@example.com';
$studentRole = $_SESSION['role'] ?? null;

if ($studentId === null || $studentRole !== 'student') {
    header('Location: ../index.php');
    exit;
}

$enrollmentMessage = '';
$enrollmentMessageKind = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'enroll_subject') {
    $subjectId = (int)($_POST['subject_id'] ?? 0);

    if ($subjectId <= 0) {
        $enrollmentMessage = 'Select a valid subject to enroll in.';
        $enrollmentMessageKind = 'error';
    } else {
        $enrollStmt = $conn->prepare('INSERT INTO enrollments (student_id, subject_id) VALUES (?, ?)');

        if ($enrollStmt) {
            $enrollStmt->bind_param('ii', $studentId, $subjectId);
            try {
                $ok = $enrollStmt->execute();

                if ($ok) {
                    $enrollmentMessage = 'Enrollment successful.';
                    $enrollmentMessageKind = 'success';
                } else {
                    $enrollmentMessage = 'Unable to enroll in that class right now.';
                    $enrollmentMessageKind = 'error';
                }
            } catch (mysqli_sql_exception $exception) {
                if ((int)$exception->getCode() === 1062) {
                    $enrollmentMessage = 'You are already enrolled in that class.';
                    $enrollmentMessageKind = 'error';
                } else {
                    throw $exception;
                }
            }

            $enrollStmt->close();
        } else {
            $enrollmentMessage = 'Unable to prepare the enrollment request.';
            $enrollmentMessageKind = 'error';
        }
    }
}

$availableSubjects = [];
$subjectsStmt = $conn->prepare(
    'SELECT subjects.id, subjects.name, subjects.code, subjects.description, users.full_name AS teacher_name
     FROM subjects
     JOIN users ON users.id = subjects.teacher_id
     WHERE NOT EXISTS (
         SELECT 1
         FROM enrollments
         WHERE enrollments.subject_id = subjects.id
           AND enrollments.student_id = ?
     )
     ORDER BY subjects.created_at DESC, subjects.id DESC'
);

if ($subjectsStmt) {
    $subjectsStmt->bind_param('i', $studentId);
    $subjectsStmt->execute();
    $subjectsResult = $subjectsStmt->get_result();
    if ($subjectsResult) {
        $availableSubjects = $subjectsResult->fetch_all(MYSQLI_ASSOC);
    }
    $subjectsStmt->close();
}

$enrolledSubjects = [];
$enrolledStmt = $conn->prepare(
    "SELECT
        subjects.id,
        subjects.name,
        subjects.code,
        users.full_name AS teacher_name,
        COALESCE(COUNT(DISTINCT sessions.id), 0) AS sessions_count,
        COALESCE(SUM(CASE WHEN attendance.status = 'present' THEN 1 ELSE 0 END), 0) AS present_count,
        COALESCE(SUM(CASE WHEN attendance.status = 'late' THEN 1 ELSE 0 END), 0) AS late_count,
        COALESCE(SUM(CASE WHEN attendance.status = 'absent' THEN 1 ELSE 0 END), 0) AS absent_count
     FROM enrollments
     JOIN subjects ON subjects.id = enrollments.subject_id
     JOIN users ON users.id = subjects.teacher_id
     LEFT JOIN sessions ON sessions.subject_id = subjects.id
     LEFT JOIN attendance
       ON attendance.session_id = sessions.id
      AND attendance.student_id = enrollments.student_id
     WHERE enrollments.student_id = ?
     GROUP BY subjects.id, subjects.name, subjects.code, users.full_name
     ORDER BY subjects.created_at DESC, subjects.id DESC"
);

if ($enrolledStmt) {
    $enrolledStmt->bind_param('i', $studentId);
    $enrolledStmt->execute();
    $enrolledResult = $enrolledStmt->get_result();
    if ($enrolledResult) {
        $enrolledSubjects = $enrolledResult->fetch_all(MYSQLI_ASSOC);
    }
    $enrolledStmt->close();
}

$overallSessions = 0;
$overallPresent = 0;
$overallLate = 0;
$attendancePercentText = '--%';
$attendanceBarWidth = 0;

$summaryStmt = $conn->prepare(
    "SELECT
        COALESCE(COUNT(DISTINCT sessions.id), 0) AS sessions_count,
        COALESCE(SUM(CASE WHEN attendance.status = 'present' THEN 1 ELSE 0 END), 0) AS present_count,
        COALESCE(SUM(CASE WHEN attendance.status = 'late' THEN 1 ELSE 0 END), 0) AS late_count
     FROM enrollments
     LEFT JOIN sessions ON sessions.subject_id = enrollments.subject_id
     LEFT JOIN attendance
       ON attendance.session_id = sessions.id
      AND attendance.student_id = enrollments.student_id
     WHERE enrollments.student_id = ?"
);

if ($summaryStmt) {
    $summaryStmt->bind_param('i', $studentId);
    $summaryStmt->execute();
    $summaryResult = $summaryStmt->get_result();
    if ($summaryResult) {
        $row = $summaryResult->fetch_assoc();
        if ($row) {
            $overallSessions = (int)($row['sessions_count'] ?? 0);
            $overallPresent = (int)($row['present_count'] ?? 0);
            $overallLate = (int)($row['late_count'] ?? 0);
        }
    }
    $summaryStmt->close();
}

if ($overallSessions > 0) {
    $attendanceRatio = ($overallPresent + $overallLate) / $overallSessions;
    $attendanceBarWidth = (int)round($attendanceRatio * 100);
    if ($attendanceBarWidth < 0) {
        $attendanceBarWidth = 0;
    } elseif ($attendanceBarWidth > 100) {
        $attendanceBarWidth = 100;
    }
    $attendancePercentText = $attendanceBarWidth . '%';
}

$enrollmentNoticeClass = 'notice-box';
if ($enrollmentMessageKind === 'success') {
    $enrollmentNoticeClass .= ' status-present';
} elseif ($enrollmentMessageKind === 'error') {
    $enrollmentNoticeClass .= ' status-absent';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BigBrother | Student Dashboard</title>
  <script src="../assets/theme.js"></script>
  <link rel="stylesheet" href="../assets/style.css">
  <link rel="stylesheet" href="../assets/eye.css">
</head>
<body>

<div class="page-shell">

  <div class="layout-grid">

    <!-- Sidebar -->
    <aside class="sidebar glass-card">
      <div class="bb-login-hero">
        <span class="bb-icon-eye bb-icon-eye--fluid" data-follow-mouse data-follow-enabled="false" role="img" aria-label="Camera lens"></span>
      </div>
      <h2>BigBrother</h2>
      <p class="dashboard-subtitle">Student Panel</p>

      <div class="nav-links">
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="#attendanceSection">Attendance</a>
        <a href="#enrollmentSection">Subjects</a>
        <a href="#contactSection">Contact</a>
      </div>

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

      <div class="nav-links">
        <a href="../logout.php">Logout</a>
      </div>
    </aside>

    <!-- Main -->
    <main class="main-panel">

      <!-- Top Bar -->
      <div class="navbar glass-card">
        <div>
          <h2>Student Dashboard</h2>
          <p class="dashboard-subtitle">View your attendance and track your progress.</p>
        </div>

        <div class="topbar-user">
          <div>
            <p><?php echo htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="muted"><?php echo htmlspecialchars($studentEmail, ENT_QUOTES, 'UTF-8'); ?></p>
          </div>
          <div class="avatar">S</div>
        </div>
      </div>

      <!-- Grid Content -->
      <div class="panel-grid">

        <div class="stat-card">
          <h3>Attendence History</h3>

          <div class="progress-track">
            <div class="progress-fill" style="width: <?php echo (int)$attendanceBarWidth; ?>%;"></div>
          </div>

          <div class="stat-value"><?php echo htmlspecialchars($attendancePercentText, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>

        <!-- Classes -->
        <div class="table-wrap" id="attendanceSection">
          <div class="card-header">
            <div>
              <h3>Class Attendance</h3>
              <p class="muted">Attendance totals across each class you are enrolled in.</p>
            </div>
          </div>

          <table>
            <thead>
              <tr>
                <th>Professor</th>
                <th>Subject Name</th>
                <th>Code</th>
                <th>Sessions</th>
                <th>Present</th>
                <th>Late</th>
                <th>Absent</th>
              </tr>
            </thead>

            <tbody id="attendanceTable">
              <?php if (count($enrolledSubjects) === 0): ?>
                <tr>
                  <td colspan="7">You are not enrolled in any classes yet.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($enrolledSubjects as $subject): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($subject['teacher_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($subject['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($subject['code'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string)$subject['sessions_count'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string)$subject['present_count'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string)$subject['late_count'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string)$subject['absent_count'], ENT_QUOTES, 'UTF-8'); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Enrollment Table -->
        <div class="table-wrap" id="enrollmentSection">
          <div class="card-header">
            <div>
              <h3>Available Subjects</h3>
              <p class="muted">Browse the classes currently offered by your teachers.</p>
            </div>
          </div>

          <table class="subjects-table">
            <colgroup>
              <col class="subjects-col-professor">
              <col class="subjects-col-name">
              <col class="subjects-col-code">
              <col class="subjects-col-description">
              <col class="subjects-col-action">
            </colgroup>
            <thead>
              <tr>
                <th>Professor</th>
                <th>Subject Name</th>
                <th>Code</th>
                <th>Description</th>
                <th></th>
              </tr>
            </thead>

            <tbody id="enrollmentTable">
              <?php if (count($availableSubjects) === 0): ?>
                <tr>
                  <td colspan="5">No subjects are available for enrollment right now.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($availableSubjects as $subject): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($subject['teacher_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($subject['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($subject['code'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string)($subject['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                      <form method="POST" action="dashboard.php#enrollmentSection">
                        <input type="hidden" name="action" value="enroll_subject">
                        <input type="hidden" name="subject_id" value="<?php echo (int)$subject['id']; ?>">
                        <button class="btn btn-secondary" type="submit">Enroll</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Contact -->
        <div class="contact-card" id="contactSection">
          <div class="card-header">
            <h3>Student to Teacher Contact</h3>
          </div>

          <form class="form-grid" onsubmit="sendMessage(event, 'student')">
            <div class="form-row two">
              <div class="form-group">
                <label for="student_name">Your Name</label>
                <input class="text-input" type="text" id="student_name" name="from_name" value="<?php echo htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'); ?>" required>
              </div>

              <div class="form-group">
                <label for="student_email">Your Email</label>
                <input class="text-input" type="email" id="student_email" name="reply_to" value="<?php echo htmlspecialchars($studentEmail, ENT_QUOTES, 'UTF-8'); ?>" required>
              </div>
            </div>

            <div class="form-row two">
              <div class="form-group">
                <label for="teacher_target_name">Teacher Name</label>
                <input class="text-input" type="text" id="teacher_target_name" name="target_name" placeholder="Teacher name" required>
              </div>

              <div class="form-group">
                <label for="teacher_target_email">Teacher Email</label>
                <input class="text-input" type="email" id="teacher_target_email" name="target_email" placeholder="teacher@example.com" required>
              </div>
            </div>

            <div class="form-group">
              <label for="student_message">Message</label>
              <textarea class="text-area" id="student_message" name="message" placeholder="Write your message here..." required></textarea>
            </div>

            <div class="btn-row">
              <button class="btn btn-primary" type="submit">Send Message</button>
            </div>

            <p class="form-status muted"></p>
          </form>
        </div>

      </div>

    </main>

  </div>

</div>

<script src="../assets/email-config.js?v=2"></script>
<script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
<script src="../assets/script.js?v=2"></script>
<script src="../assets/eye-follow.js" defer></script>
<script>
  if (window.emailjs && window.EMAILJS_CONFIG) {
    emailjs.init(window.EMAILJS_CONFIG.publicKey);
  }
</script>
</body>
</html>
