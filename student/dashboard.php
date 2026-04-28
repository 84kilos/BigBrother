<?php
session_start();
$studentName = $_SESSION['full_name'] ?? 'Student';
$studentEmail = $_SESSION['email'] ?? 'student@example.com';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BigBrother | Student Dashboard</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<div class="page-shell">

  <div class="layout-grid">

    <!-- Sidebar -->
    <aside class="sidebar glass-card">
      <h2>BigBrother</h2>
      <p class="dashboard-subtitle">Student Panel</p>

      <div class="nav-links">
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="#attendanceSection">Attendance</a>
        <a href="#contactSection">Contact</a>
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

        <!-- Attendance Overview -->
        <div class="stat-card">
          <h3>Attendance Overview</h3>

          <div class="progress-track">
            <div class="progress-fill" style="width: 0%;"></div>
          </div>

          <div class="stat-value">--%</div>
        </div>

        <!-- Attendance Table -->
        <div class="table-wrap" id="attendanceSection">
          <div class="card-header">
            <h3>Attendance History</h3>
          </div>

          <table>
            <thead>
              <tr>
                <th>Date</th>
                <th>Subject</th>
                <th>Status</th>
              </tr>
            </thead>

            <tbody id="attendanceTable">
              <!-- Backend data goes here -->
            </tbody>
          </table>
        </div>

        <!-- Enrollment Table -->
        <div class="table-wrap" id="enrollmentSection">
          <div class="card-header">
            <h3>Enrollment</h3>
          </div>

          <table>
            <thead>
              <tr>
                <th>Professor</th>
                <th>Subject Name</th>
                <th>Code</th>
              </tr>
            </thead>

            <tbody id="attendanceTable">
              <!-- Backend data goes here -->
            </tbody>
          </table>
        </div>

        <!-- Contact -->
        <div class="contact-card" id="contactSection">
          <div class="card-header">
            <h3>Contact</h3>
          </div>

          <form id="contactForm">
            <div class="form-group">
              <label>Name</label>
              <input class="text-input" type="text" placeholder="Your name" required>
            </div>

            <div class="form-group">
              <label>Email</label>
              <input class="text-input" type="email" placeholder="Your email" required>
            </div>

            <div class="form-group">
              <label>Message</label>
              <textarea class="text-area" placeholder="Write your message" required></textarea>
            </div>

            <div class="btn-row">
              <button class="btn btn-primary" type="submit">Send Message</button>
            </div>
          </form>
        </div>

      </div>

    </main>

  </div>

</div>

<script src="../assets/script.js"></script>
</body>
</html>
