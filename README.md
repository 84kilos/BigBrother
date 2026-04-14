### BIG BROTHER (Attendance Tool)
A tool to help teachers and students keep track of their participation and attendance.
attendance-system/
│
├── index.php/login.html                 # Landing page / redirect to login
│
├── config/
│   └── db.php                 # MySQL database connection
│
├── auth/
│   ├── login.php              # Login logic (POST handler)
│   ├── logout.php             # Session destroy
│   └── register.php           # Register logic (POST handler)
│
├── pages/
│   ├── login-page.php         # Login form UI
│   ├── register-page.php      # Register form UI
│   ├── teacher-dashboard.php  # Teacher home after login
│   └── student-dashboard.php  # Student home after login
│
├── teacher/
│   ├── mark-attendance.php    # Mark attendance for a class/session
│   ├── view-attendance.php    # View all students' records
│   └── reports.php            # Generate class/student reports
│
├── student/
│   ├── my-attendance.php      # Student views their own history
│   └── my-percentage.php      # Student sees attendance percentage
│
├── includes/
│   ├── header.php             # Shared HTML header / nav
│   ├── footer.php             # Shared HTML footer
│   ├── auth-guard.php         # Session check (redirect if not logged in)
│   └── role-guard.php         # Role check (teacher vs student)
│
├── api/
│   ├── mark-attendance.php    # AJAX endpoint – save attendance
│   ├── get-attendance.php     # AJAX endpoint – fetch records
│   └── get-report.php         # AJAX endpoint – report data
│
├── css/
│   └── style.css              # Main stylesheet
│
├── js/
│   ├── attendance.js          # Attendance marking logic
│   ├── charts.js              # Analytics / chart rendering
│   └── validation.js          # Client-side form validation
│
├── sql/
│   └── schema.sql             # Full DB schema for submission
│
└── report/
    └── final-report.pdf       # Project report (submitted separately)
