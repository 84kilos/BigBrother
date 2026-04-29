<?php
session_start();
require '../db.php';

$teacherId = $_SESSION['id'] ?? null;
$teacherRole = $_SESSION['role'] ?? null;

if ($teacherId === null || $teacherRole !== 'teacher') {
    header('Location: ../index.php');
    exit;
}

$subjectId = (int)($_GET['subject_id'] ?? 0);
if ($subjectId <= 0) {
    header('Location: dashboard.php');
    exit;
}

$subject = null;
$subjectStmt = $conn->prepare('SELECT id, name, code, description FROM subjects WHERE id = ? AND teacher_id = ?');
if ($subjectStmt) {
    $subjectStmt->bind_param('ii', $subjectId, $teacherId);
    $subjectStmt->execute();
    $subjectResult = $subjectStmt->get_result();
    if ($subjectResult) {
        $subject = $subjectResult->fetch_assoc();
    }
    $subjectStmt->close();
}

if (!$subject) {
    header('Location: dashboard.php');
    exit;
}

$isSessionRequest = $_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'create_session');
if (!$isSessionRequest && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    if ($rawInput !== false && $rawInput !== '') {
        $decodedInput = json_decode($rawInput, true);
        if (is_array($decodedInput) && (($decodedInput['action'] ?? '') === 'create_session')) {
            $isSessionRequest = true;
        }
    }
}

if ($isSessionRequest) {
    header('Content-Type: application/json');

    $payload = $_POST;
    if (empty($payload)) {
        $rawPayload = file_get_contents('php://input');
        $decodedPayload = json_decode($rawPayload ?: '', true);
        if (is_array($decodedPayload)) {
            $payload = $decodedPayload;
        }
    }

    $attendanceItems = $payload['attendance'] ?? null;
    if (!is_array($attendanceItems)) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'Attendance selections were not received.'
        ]);
        exit;
    }

    $enrolledIds = [];
    $enrolledStmt = $conn->prepare(
        'SELECT enrollments.student_id
         FROM enrollments
         JOIN subjects ON subjects.id = enrollments.subject_id
         WHERE enrollments.subject_id = ? AND subjects.teacher_id = ?'
    );

    if (!$enrolledStmt) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Unable to prepare enrollment validation.'
        ]);
        exit;
    }

    $enrolledStmt->bind_param('ii', $subjectId, $teacherId);
    $enrolledStmt->execute();
    $enrolledResult = $enrolledStmt->get_result();
    if ($enrolledResult) {
        while ($row = $enrolledResult->fetch_assoc()) {
            $enrolledIds[] = (int)$row['student_id'];
        }
    }
    $enrolledStmt->close();

    if (count($enrolledIds) === 0) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'This class has no enrolled students yet.'
        ]);
        exit;
    }

    $allowedStatuses = ['present', 'late', 'absent'];
    $normalizedAttendance = [];

    foreach ($attendanceItems as $item) {
        if (!is_array($item)) {
            continue;
        }

        $studentIdValue = (int)($item['student_id'] ?? 0);
        $statusValue = strtolower(trim((string)($item['status'] ?? '')));

        if ($studentIdValue <= 0 || !in_array($statusValue, $allowedStatuses, true)) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'Each student needs one valid attendance status.'
            ]);
            exit;
        }

        if (!in_array($studentIdValue, $enrolledIds, true)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'One or more attendance rows do not belong to this class.'
            ]);
            exit;
        }

        if (isset($normalizedAttendance[$studentIdValue])) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'A student was selected more than once.'
            ]);
            exit;
        }

        $normalizedAttendance[$studentIdValue] = $statusValue;
    }

    sort($enrolledIds);
    $submittedIds = array_keys($normalizedAttendance);
    sort($submittedIds);

    if ($submittedIds !== $enrolledIds) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'Please mark every enrolled student before confirming the session.'
        ]);
        exit;
    }

    $sessionDate = date('Y-m-d');

    try {
        $conn->begin_transaction();

        $sessionStmt = $conn->prepare('INSERT INTO sessions (subject_id, session_date) VALUES (?, ?)');
        if (!$sessionStmt) {
            throw new RuntimeException('Unable to prepare the session insert.');
        }

        $sessionStmt->bind_param('is', $subjectId, $sessionDate);
        if (!$sessionStmt->execute()) {
            throw new RuntimeException('Unable to create the class session.');
        }

        $sessionId = (int)$conn->insert_id;
        $sessionStmt->close();

        $attendanceStmt = $conn->prepare(
            'INSERT INTO attendance (session_id, student_id, status, marked_by, notes)
             VALUES (?, ?, ?, ?, NULL)'
        );

        if (!$attendanceStmt) {
            throw new RuntimeException('Unable to prepare the attendance insert.');
        }

        foreach ($normalizedAttendance as $studentIdValue => $statusValue) {
            $attendanceStmt->bind_param('iisi', $sessionId, $studentIdValue, $statusValue, $teacherId);
            if (!$attendanceStmt->execute()) {
                throw new RuntimeException('Unable to save one or more attendance records.');
            }
        }

        $attendanceStmt->close();
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Session created and attendance saved.',
            'session_id' => $sessionId,
            'session_date' => $sessionDate
        ]);
    } catch (Throwable $exception) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $exception->getMessage()
        ]);
    }

    exit;
}

$students = [];
$studentsStmt = $conn->prepare(
    "SELECT
        users.id AS student_id,
        users.full_name,
        COALESCE(SUM(CASE WHEN attendance.status = 'present' THEN 1 ELSE 0 END), 0) AS present_count,
        COALESCE(SUM(CASE WHEN attendance.status = 'late' THEN 1 ELSE 0 END), 0) AS late_count,
        COALESCE(SUM(CASE WHEN attendance.status = 'absent' THEN 1 ELSE 0 END), 0) AS absent_count
     FROM enrollments
     JOIN users ON users.id = enrollments.student_id
     LEFT JOIN sessions ON sessions.subject_id = enrollments.subject_id
     LEFT JOIN attendance
       ON attendance.session_id = sessions.id
      AND attendance.student_id = enrollments.student_id
     WHERE enrollments.subject_id = ?
     GROUP BY users.id, users.full_name
     ORDER BY users.full_name ASC"
);

if ($studentsStmt) {
    $studentsStmt->bind_param('i', $subjectId);
    $studentsStmt->execute();
    $studentsResult = $studentsStmt->get_result();
    if ($studentsResult) {
        $students = $studentsResult->fetch_all(MYSQLI_ASSOC);
    }
    $studentsStmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars((string)$subject['code'], ENT_QUOTES, 'UTF-8'); ?> | Class Roster</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
  <div class="page-shell">
    <div class="layout-grid">
      <aside class="sidebar">
        <div class="brand-badge">
          <span class="logo-dot"></span>
          Teacher Panel
        </div>
        <h2>BigBrother</h2>
        <p class="dashboard-subtitle">Class details.</p>
        <nav class="nav-links">
          <a href="dashboard.php">Back to Dashboard</a>
          <a href="../logout.php">Logout</a>
        </nav>
      </aside>

      <main class="main-panel">
        <section class="navbar">
          <div>
            <h1><?php echo htmlspecialchars((string)$subject['name'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="dashboard-subtitle">
              <?php echo htmlspecialchars((string)$subject['code'], ENT_QUOTES, 'UTF-8'); ?>
              <?php if (!empty($subject['description'])): ?>
                • <?php echo htmlspecialchars((string)$subject['description'], ENT_QUOTES, 'UTF-8'); ?>
              <?php endif; ?>
            </p>
          </div>
        </section>

        <section class="table-wrap full">
          <div class="card-header">
            <div>
              <h3>Enrolled Students</h3>
              <p class="muted" id="sessionSummaryText"><?php echo count($students); ?> student<?php echo count($students) === 1 ? '' : 's'; ?> enrolled.</p>
            </div>
            <div class="inline-actions">
              <button class="btn btn-secondary" type="button" id="newSessionToggle"<?php echo count($students) === 0 ? ' disabled' : ''; ?>>New Session</button>
            </div>
          </div>

          <div class="notice-box session-builder-notice" id="sessionBuilderNotice">
            Click <strong>New Session</strong> to start marking attendance for this class.
          </div>

          <table>
            <thead>
              <tr>
                <th>Student Name</th>
                <th>Present</th>
                <th>Late</th>
                <th>Absent</th>
              </tr>
            </thead>
            <tbody id="attendanceSessionTable">
              <?php if (count($students) === 0): ?>
                <tr>
                  <td colspan="4">No students are enrolled in this class yet.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($students as $student): ?>
                  <tr data-student-row data-student-id="<?php echo (int)$student['student_id']; ?>">
                    <td><?php echo htmlspecialchars((string)$student['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                      <div class="attendance-cell">
                        <span class="attendance-count" data-status-count="present" data-base-count="<?php echo (int)$student['present_count']; ?>"><?php echo (int)$student['present_count']; ?></span>
                        <button
                          class="btn btn-success attendance-toggle"
                          type="button"
                          data-status-button="present"
                          hidden
                          aria-label="Toggle present for <?php echo htmlspecialchars((string)$student['full_name'], ENT_QUOTES, 'UTF-8'); ?>"
                        >+</button>
                      </div>
                    </td>
                    <td>
                      <div class="attendance-cell">
                        <span class="attendance-count" data-status-count="late" data-base-count="<?php echo (int)$student['late_count']; ?>"><?php echo (int)$student['late_count']; ?></span>
                        <button
                          class="btn btn-warning attendance-toggle"
                          type="button"
                          data-status-button="late"
                          hidden
                          aria-label="Toggle late for <?php echo htmlspecialchars((string)$student['full_name'], ENT_QUOTES, 'UTF-8'); ?>"
                        >+</button>
                      </div>
                    </td>
                    <td>
                      <div class="attendance-cell">
                        <span class="attendance-count" data-status-count="absent" data-base-count="<?php echo (int)$student['absent_count']; ?>"><?php echo (int)$student['absent_count']; ?></span>
                        <button
                          class="btn btn-danger attendance-toggle"
                          type="button"
                          data-status-button="absent"
                          hidden
                          aria-label="Toggle absent for <?php echo htmlspecialchars((string)$student['full_name'], ENT_QUOTES, 'UTF-8'); ?>"
                        >+</button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>

          <?php if (count($students) > 0): ?>
            <div class="session-confirm-row" id="sessionConfirmRow" hidden>
              <button class="btn btn-primary" type="button" id="confirmSessionButton" disabled>Confirm Session</button>
            </div>
          <?php endif; ?>
        </section>
      </main>
    </div>
  </div>

  <script>
    (() => {
      const toggleButton = document.getElementById('newSessionToggle');
      const confirmButton = document.getElementById('confirmSessionButton');
      const confirmRow = document.getElementById('sessionConfirmRow');
      const notice = document.getElementById('sessionBuilderNotice');
      const summaryText = document.getElementById('sessionSummaryText');
      const rows = Array.from(document.querySelectorAll('[data-student-row]'));
      const totalStudents = rows.length;

      if (!toggleButton || rows.length === 0 || !confirmButton || !confirmRow || !notice || !summaryText) {
        return;
      }

      const sessionState = {
        active: false,
        selections: {}
      };

      const statusOrder = ['present', 'late', 'absent'];

      function getSelectedCount() {
        return Object.keys(sessionState.selections).length;
      }

      function refreshSummary() {
        const selectedCount = getSelectedCount();
        const remaining = totalStudents - selectedCount;

        if (!sessionState.active) {
          summaryText.textContent = `${totalStudents} student${totalStudents === 1 ? '' : 's'} enrolled.`;
          notice.innerHTML = 'Click <strong>New Session</strong> to start marking attendance for this class.';
          confirmButton.disabled = true;
          confirmRow.hidden = true;
          return;
        }

        summaryText.textContent = `${selectedCount} of ${totalStudents} student${totalStudents === 1 ? '' : 's'} marked.`;
        confirmRow.hidden = false;
        confirmButton.disabled = remaining !== 0;

        if (remaining === 0) {
          notice.textContent = 'Every student has a status selected. You can confirm the session now.';
        } else {
          notice.textContent = `${remaining} student${remaining === 1 ? '' : 's'} still need a status before this session can be confirmed.`;
        }
      }

      function updateRow(row) {
        const studentId = row.getAttribute('data-student-id');
        const selectedStatus = sessionState.selections[studentId] || null;

        statusOrder.forEach((status) => {
          const countEl = row.querySelector(`[data-status-count="${status}"]`);
          const buttonEl = row.querySelector(`[data-status-button="${status}"]`);
          if (!countEl || !buttonEl) {
            return;
          }

          const baseCount = Number(countEl.getAttribute('data-base-count') || '0');
          const isSelected = selectedStatus === status;
          countEl.textContent = String(baseCount + (isSelected ? 1 : 0));

          if (!sessionState.active) {
            buttonEl.hidden = true;
            buttonEl.textContent = '+';
            return;
          }

          buttonEl.hidden = selectedStatus !== null && !isSelected;
          buttonEl.textContent = isSelected ? '-' : '+';
        });
      }

      function refreshRows() {
        rows.forEach(updateRow);
        refreshSummary();
      }

      function resetSelections() {
        sessionState.selections = {};
        refreshRows();
      }

      toggleButton.addEventListener('click', () => {
        sessionState.active = !sessionState.active;
        toggleButton.textContent = sessionState.active ? 'Cancel Session' : 'New Session';

        if (!sessionState.active) {
          resetSelections();
        } else {
          refreshRows();
        }
      });

      rows.forEach((row) => {
        statusOrder.forEach((status) => {
          const buttonEl = row.querySelector(`[data-status-button="${status}"]`);
          if (!buttonEl) {
            return;
          }

          buttonEl.addEventListener('click', () => {
            if (!sessionState.active) {
              return;
            }

            const studentId = row.getAttribute('data-student-id');
            if (!studentId) {
              return;
            }

            if (sessionState.selections[studentId] === status) {
              delete sessionState.selections[studentId];
            } else {
              sessionState.selections[studentId] = status;
            }

            refreshRows();
          });
        });
      });

      confirmButton.addEventListener('click', async () => {
        const selectedCount = getSelectedCount();
        if (selectedCount !== totalStudents) {
          refreshSummary();
          return;
        }

        confirmButton.disabled = true;
        toggleButton.disabled = true;
        notice.textContent = 'Saving this session...';

        const attendance = rows.map((row) => {
          const studentId = Number(row.getAttribute('data-student-id') || '0');
          return {
            student_id: studentId,
            status: sessionState.selections[String(studentId)]
          };
        });

        try {
          const response = await fetch(`class.php?subject_id=<?php echo (int)$subjectId; ?>`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
              action: 'create_session',
              attendance
            })
          });

          const data = await response.json();
          if (!response.ok || !data.success) {
            throw new Error(data.message || 'Unable to save the session.');
          }

          rows.forEach((row) => {
            const studentId = row.getAttribute('data-student-id');
            const selectedStatus = studentId ? sessionState.selections[studentId] : null;
            if (!selectedStatus) {
              return;
            }

            const countEl = row.querySelector(`[data-status-count="${selectedStatus}"]`);
            if (!countEl) {
              return;
            }

            const currentBase = Number(countEl.getAttribute('data-base-count') || '0');
            countEl.setAttribute('data-base-count', String(currentBase + 1));
          });

          sessionState.active = false;
          sessionState.selections = {};
          toggleButton.textContent = 'New Session';
          notice.textContent = `Session saved for ${data.session_date}.`;
          refreshRows();
        } catch (error) {
          notice.textContent = error instanceof Error ? error.message : 'Unable to save the session.';
        } finally {
          toggleButton.disabled = false;
          refreshSummary();
        }
      });

      refreshRows();
    })();
  </script>
</body>
</html>
