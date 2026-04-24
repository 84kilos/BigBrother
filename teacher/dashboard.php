<?php
session_start();
$teacherName = $_SESSION['full_name'] ?? 'Professor Carter';
$teacherEmail = $_SESSION['email'] ?? 'teacher@example.com';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Teacher Dashboard | BigBrother</title>
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
        <p class="dashboard-subtitle">Attendance management made simple.</p>
        <nav class="nav-links">
          <a class="active" href="dashboard.php">Dashboard</a>
          <a href="#attendanceSection">Attendance</a>
          <a href="#contactSection">Contact</a>
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
            <div class="stat-value" id="presentCount">0</div>
          </div>

          <div class="stat-card">
            <p class="muted">Late today</p>
            <div class="stat-value" id="lateCount">0</div>
          </div>

          <div class="stat-card">
            <p class="muted">Absent today</p>
            <div class="stat-value" id="absentCount">0</div>
          </div>

          <section class="content-card large" id="attendanceSection">
            <div class="card-header">
              <div>
                <h3>Quick Attendance Tools</h3>
                <p class="muted">Use the buttons below to mark each student.</p>
              </div>
            </div>

            <div class="notice-box">
            </div>
          </section>

          <section class="table-wrap full">
            <div class="card-header">
            <div>
  <h3>Student Attendance</h3>
</div>
            </div>

            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Subject</th>
                  <th>Status</th>
                  <th>Attendance %</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="teacherAttendanceBody"></tbody>
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

  <script src="../assets/email-config.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
  <script>
    if (window.emailjs && window.EMAILJS_CONFIG) {
      emailjs.init(window.EMAILJS_CONFIG.publicKey);
    }
  </script>
  <script src="../assets/script.js"></script>
</body>
</html>
