const appConfig = {
  useDemoData: false,
  endpoints: {
    markAttendance: '../teacher/mark_attendance.php',
    teacherSummary: '../teacher/get_attendance_summary.php',
    studentSummary: '../student/get_attendance_history.php'
  }
};

const demoStudents = [
  { id: 1001, name: 'Maya Brooks', email: 'maya@student.edu', subject: 'Web Tech', status: 'Present', percent: 94 },
  { id: 1002, name: 'Jordan Lee', email: 'jordan@student.edu', subject: 'Web Tech', status: 'Late', percent: 88 },
  { id: 1003, name: 'Chris Hall', email: 'chris@student.edu', subject: 'Web Tech', status: 'Absent', percent: 71 },
  { id: 1004, name: 'Ava Smith', email: 'ava@student.edu', subject: 'Web Tech', status: 'Present', percent: 97 }
];

function statusClass(status) {
  const value = String(status).toLowerCase();
  if (value === 'present') return 'status-pill status-present';
  if (value === 'late') return 'status-pill status-late';
  return 'status-pill status-absent';
}

function renderTeacherTable() {
  const tableBody = document.getElementById('teacherAttendanceBody');
  if (!tableBody) return;

  tableBody.innerHTML = demoStudents.map(student => `
    <tr>
      <td>${student.id}</td>
      <td>${student.name}</td>
      <td>${student.subject}</td>
      <td><span class="${statusClass(student.status)}">${student.status}</span></td>
      <td>${student.percent}%</td>
      <td>
        <div class="inline-actions">
          <button class="btn btn-success" onclick="markAttendance(${student.id}, 'Present')">Present</button>
          <button class="btn btn-warning" onclick="markAttendance(${student.id}, 'Late')">Late</button>
          <button class="btn btn-danger" onclick="markAttendance(${student.id}, 'Absent')">Absent</button>
        </div>
      </td>
    </tr>
  `).join('');
}

function updateTeacherStats() {
  const presentCount = demoStudents.filter(student => student.status === 'Present').length;
  const lateCount = demoStudents.filter(student => student.status === 'Late').length;
  const absentCount = demoStudents.filter(student => student.status === 'Absent').length;

  const presentEl = document.getElementById('presentCount');
  const lateEl = document.getElementById('lateCount');
  const absentEl = document.getElementById('absentCount');

  if (presentEl) presentEl.textContent = presentCount;
  if (lateEl) lateEl.textContent = lateCount;
  if (absentEl) absentEl.textContent = absentCount;
}

function markAttendance(studentId, newStatus) {
  const student = demoStudents.find(item => item.id === studentId);
  if (!student) return;

  student.status = newStatus;
  if (newStatus === 'Present') student.percent = Math.min(student.percent + 1, 100);
  if (newStatus === 'Absent') student.percent = Math.max(student.percent - 2, 0);

  renderTeacherTable();
  updateTeacherStats();

  if (!appConfig.useDemoData) {
    fetch(appConfig.endpoints.markAttendance, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ student_id: studentId, status: newStatus })
    })
    .then(response => response.json())
    .then(data => console.log('Attendance updated:', data))
    .catch(error => console.error('Attendance update failed:', error));
  }
}

function renderStudentHistory() {
  const tableBody = document.getElementById('studentHistoryBody');
  if (!tableBody) return;

  const history = [
    { date: '2026-04-14', subject: 'Web Tech', status: 'Present' },
    { date: '2026-04-16', subject: 'Web Tech', status: 'Late' },
    { date: '2026-04-18', subject: 'Web Tech', status: 'Present' },
    { date: '2026-04-21', subject: 'Web Tech', status: 'Absent' }
  ];

  tableBody.innerHTML = history.map(row => `
    <tr>
      <td>${row.date}</td>
      <td>${row.subject}</td>
      <td><span class="${statusClass(row.status)}">${row.status}</span></td>
    </tr>
  `).join('');
}

function setStudentProgress(value) {
  const fill = document.getElementById('studentProgressFill');
  const text = document.getElementById('studentProgressText');

  if (fill) fill.style.width = `${value}%`;
  if (text) text.textContent = `${value}% overall attendance`;
}

function sendMessage(event, role) {
  event.preventDefault();

  const form = event.target;
  const status = form.querySelector('.form-status');
  const config = window.EMAILJS_CONFIG;
  const templateId = role === 'teacher' ? config?.teacherTemplateID : config?.studentTemplateID;
  const setStatus = (message, kind) => {
    if (!status) return;
    status.textContent = message;
    status.classList.add('is-visible');
    status.classList.remove('is-success', 'is-error');
    if (kind === 'success') status.classList.add('is-success');
    if (kind === 'error') status.classList.add('is-error');
  };

  if (!window.emailjs || !config || !templateId) {
    setStatus('Email sending is not configured yet.', 'error');
    return;
  }

  setStatus('Sending message...', null);

  emailjs.send(
    config.serviceID,
    templateId,
    {
      from_name: form.querySelector('[name="from_name"]')?.value ?? '',
      reply_to: form.querySelector('[name="reply_to"]')?.value ?? '',
      target_name: form.querySelector('[name="target_name"]')?.value ?? '',
      target_email: form.querySelector('[name="target_email"]')?.value ?? '',
      role: role,
      message: form.querySelector('[name="message"]')?.value ?? ''
    },
    config.publicKey
  )
  .then(() => {
    setStatus('Email sent successfully.', 'success');
    form.reset();
  })
  .catch(() => {
    setStatus('Unable to send the message right now.', 'error');
  });
}

window.markAttendance = markAttendance;
window.sendMessage = sendMessage;


