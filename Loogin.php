<?php
require_once 'auth_check.php';
requireFaculty();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard | Attendance Management</title>
    <link rel="stylesheet" href="stylesheet.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .tab-container {
            margin: 20px 0;
        }
        .tab {
            display: inline-block;
            padding: 10px 20px;
            cursor: pointer;
            border: 1px solid #ccc;
        }
        .tab.active {
            background: #003366;
            color: white;
        }
        .tab-content {
            display: none;
            padding: 20px;
            border: 1px solid #ccc;
        }
        .tab-content.active {
            display: block;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
        }
        .attendance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
        }
        .attendance-card {
            border: 1px solid #ccc;
            padding: 15px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <header>
        <h1>Faculty Dashboard</h1>
        <div>
            Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (Faculty)
            <button onclick="logout()" style="margin-left: 20px;">Logout</button>
        </div>
    </header>

    <div class="container">
        <nav class="sidebar">
            <ul>
                <li class="active"><a href="#" onclick="showTab('dashboard')">Dashboard</a></li>
                <li><a href="#" onclick="showTab('courses')">My Courses</a></li>
                <li><a href="#" onclick="showTab('sessions')">Sessions</a></li>
                <li><a href="#" onclick="showTab('attendance')">Attendance</a></li>
                <li><a href="#" onclick="showTab('reports')">Reports</a></li>
            </ul>
        </nav>

        <main id="main-content">
            <div id="loading" style="text-align: center; padding: 40px;">Loading...</div>

            <div id="content" style="display: none;">
                <div class="tab-container">
                    <div class="tab active" onclick="showTab('dashboard')">Dashboard</div>
                    <div class="tab" onclick="showTab('courses')">Courses</div>
                    <div class="tab" onclick="showTab('sessions')">Sessions</div>
                    <div class="tab" onclick="showTab('attendance')">Attendance</div>
                    <div class="tab" onclick="showTab('reports')">Reports</div>
                </div>

                <div id="tab-dashboard" class="tab-content active">
                    <h2>Faculty Dashboard</h2>
                    <div id="faculty-stats"></div>
                </div>

                <div id="tab-courses" class="tab-content">
                    <h2>My Courses</h2>
                    <div id="courses-list"></div>
                </div>

                <div id="tab-sessions" class="tab-content">
                    <h2>Session Management</h2>
                    <button onclick="showCreateSessionForm()" class="btn-primary">Create New Session</button>
                    <div id="create-session-form" style="display: none; margin: 20px 0; padding: 20px; border: 1px solid #ccc;">
                        <h3>Create New Session</h3>
                        <div class="form-group">
                            <label>Course:</label>
                            <select id="session-course" required></select>
                        </div>
                        <div class="form-group">
                            <label>Session Name:</label>
                            <input type="text" id="session-name" required>
                        </div>
                        <div class="form-group">
                            <label>Date:</label>
                            <input type="date" id="session-date" required>
                        </div>
                        <div class="form-group">
                            <label>Start Time:</label>
                            <input type="time" id="start-time" required>
                        </div>
                        <div class="form-group">
                            <label>End Time:</label>
                            <input type="time" id="end-time" required>
                        </div>
                        <div class="form-group">
                            <label>Location (Optional):</label>
                            <input type="text" id="session-location">
                        </div>
                        <button onclick="createSession()" class="btn-primary">Create Session</button>
                        <button onclick="hideCreateSessionForm()" class="btn-danger">Cancel</button>
                    </div>
                    <div id="sessions-list"></div>
                </div>

                <div id="tab-attendance" class="tab-content">
                    <h2>Attendance Management</h2>
                    <div id="attendance-controls">
                        <label>Select Session:</label>
                        <select id="attendance-session" onchange="loadSessionAttendance()">
                            <option value="">-- Select Session --</option>
                        </select>
                    </div>
                    <div id="session-attendance"></div>
                </div>

                <div id="tab-reports" class="tab-content">
                    <h2>Attendance Reports</h2>
                    <div id="reports-content"></div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

            event.target.classList.add('active');
            document.getElementById('tab-' + tabName).classList.add('active');

            switch (tabName) {
                case 'dashboard':
                    loadFacultyStats();
                    break;
                case 'courses':
                    loadFacultyCourses();
                    break;
                case 'sessions':
                    loadFacultySessions();
                    loadFacultyCoursesForSessions();
                    break;
                case 'attendance':
                    loadSessionsForAttendance();
                    break;
                case 'reports':
                    loadAttendanceReports();
                    break;
            }
        }

        function loadFacultyStats() {
            fetch('get_faculty_stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const stats = data.stats;
                        document.getElementById('faculty-stats').innerHTML = `
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <h4>Total Courses</h4>
                                    <p class="stat-number">${stats.total_courses}</p>
                                </div>
                                <div class="stat-card">
                                    <h4>Active Sessions</h4>
                                    <p class="stat-number">${stats.active_sessions}</p>
                                </div>
                                <div class="stat-card">
                                    <h4>Total Students</h4>
                                    <p class="stat-number">${stats.total_students}</p>
                                </div>
                                <div class="stat-card">
                                    <h4>Avg Attendance</h4>
                                    <p class="stat-number">${stats.avg_attendance}%</p>
                                </div>
                            </div>
                        `;
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function loadFacultyCourses() {
            fetch('get_faculty_courses.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const coursesList = document.getElementById('courses-list');
                        if (data.courses.length > 0) {
                            coursesList.innerHTML = `
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Course Code</th>
                                            <th>Course Name</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.courses.map(course => `
                                            <tr>
                                                <td>${course.course_code}</td>
                                                <td>${course.course_name}</td>
                                                <td>
                                                    <button onclick="viewCourseAttendance(${course.course_id})" class="btn-info">Attendance</button>
                                                    <button onclick="viewCourseStudents(${course.course_id})" class="btn-info">Students</button>
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            `;
                        } else {
                            coursesList.innerHTML = '<p>No courses found.</p>';
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function loadFacultyCoursesForSessions() {
            fetch('get_faculty_courses.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('session-course');
                        select.innerHTML = '<option value="">-- Select Course --</option>' +
                            data.courses.map(course => 
                                `<option value="${course.course_id}">${course.course_code} - ${course.course_name}</option>`
                            ).join('');
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function showCreateSessionForm() {
            document.getElementById('create-session-form').style.display = 'block';
            document.getElementById('session-date').valueAsDate = new Date();
            document.getElementById('start-time').value = '09:00';
            document.getElementById('end-time').value = '10:30';
        }

        function hideCreateSessionForm() {
            document.getElementById('create-session-form').style.display = 'none';
            document.getElementById('session-name').value = '';
            document.getElementById('session-location').value = '';
        }

        function createSession() {
            const courseId = document.getElementById('session-course').value;
            const sessionName = document.getElementById('session-name').value;
            const sessionDate = document.getElementById('session-date').value;
            const startTime = document.getElementById('start-time').value;
            const endTime = document.getElementById('end-time').value;
            const location = document.getElementById('session-location').value;

            if (!courseId || !sessionName || !sessionDate || !startTime || !endTime) {
                alert('Please fill all required fields');
                return;
            }

            fetch('create_session.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    course_id: courseId,
                    session_name: sessionName,
                    session_date: sessionDate,
                    start_time: startTime,
                    end_time: endTime,
                    location: location
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Session created! Session Code: ${data.session_code}`);
                    hideCreateSessionForm();
                    loadFacultySessions();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
            });
        }

        function loadFacultySessions() {
            fetch('get_sessions.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const sessionsList = document.getElementById('sessions-list');
                        if (data.sessions.length > 0) {
                            sessionsList.innerHTML = `
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Course</th>
                                            <th>Session</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Code</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.sessions.map(session => `
                                            <tr>
                                                <td>${session.course_name}</td>
                                                <td>${session.session_name}</td>
                                                <td>${session.session_date}</td>
                                                <td>${session.start_time} - ${session.end_time}</td>
                                                <td><code>${session.session_code}</code></td>
                                                <td><span class="status-${session.status}">${session.status}</span></td>
                                                <td>
                                                    <button onclick="manageSessionAttendance(${session.session_id})" class="btn-info">Attendance</button>
                                                    <button onclick="updateSessionStatus(${session.session_id}, 'active')" class="btn-primary" ${session.status === 'active' ? 'disabled' : ''}>Start</button>
                                                    <button onclick="updateSessionStatus(${session.session_id}, 'completed')" class="btn-primary" ${session.status === 'completed' ? 'disabled' : ''}>Complete</button>
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            `;
                        } else {
                            sessionsList.innerHTML = '<p>No sessions found.</p>';
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function loadSessionsForAttendance() {
            fetch('get_sessions.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('attendance-session');
                        select.innerHTML = '<option value="">-- Select Session --</option>' +
                            data.sessions.map(session => 
                                `<option value="${session.session_id}">${session.course_name} - ${session.session_name} (${session.session_date})</option>`
                            ).join('');
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function loadSessionAttendance() {
            const sessionId = document.getElementById('attendance-session').value;
            if (!sessionId) return;

            fetch(`get_session_attendance.php?session_id=${sessionId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const container = document.getElementById('session-attendance');
                        if (data.attendance.length > 0) {
                            container.innerHTML = `
                                <h3>Attendance List</h3>
                                <div class="attendance-grid">
                                    ${data.attendance.map(record => `
                                        <div class="attendance-card">
                                            <h4>${record.first_name} ${record.last_name}</h4>
                                            <p>Status: 
                                                <select onchange="updateAttendanceStatus(${record.attendance_id}, ${record.student_id}, ${sessionId}, this.value)">
                                                    <option value="present" ${record.status === 'present' ? 'selected' : ''}>Present</option>
                                                    <option value="absent" ${record.status === 'absent' ? 'selected' : ''}>Absent</option>
                                                    <option value="late" ${record.status === 'late' ? 'selected' : ''}>Late</option>
                                                </select>
                                            </p>
                                            <p>Checked: ${record.check_in_time || 'Not checked'}</p>
                                        </div>
                                    `).join('')}
                                </div>
                            `;
                        } else {
                            container.innerHTML = '<p>No attendance records found for this session.</p>';
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function updateAttendanceStatus(attendanceId, studentId, sessionId, status) {
            fetch('mark_attendance.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    session_id: sessionId,
                    student_id: studentId,
                    status: status
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Attendance updated');
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function updateSessionStatus(sessionId, status) {
            fetch('update_session_status.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    session_id: sessionId,
                    status: status
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Session status updated');
                    loadFacultySessions();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
            });
        }

        function loadAttendanceReports() {
            fetch('get_course_attendance_summary.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const container = document.getElementById('reports-content');
                        if (data.summary.length > 0) {
                            container.innerHTML = `
                                <h3>Course Attendance Summary</h3>
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Course Code</th>
                                            <th>Course Name</th>
                                            <th>Sessions</th>
                                            <th>Students</th>
                                            <th>Attendance Rate</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.summary.map(course => `
                                            <tr>
                                                <td>${course.course_code}</td>
                                                <td>${course.course_name}</td>
                                                <td>${course.total_sessions || 0}</td>
                                                <td>${course.total_students || 0}</td>
                                                <td>${course.avg_attendance_rate ? course.avg_attendance_rate.toFixed(2) + '%' : 'N/A'}</td>
                                                <td>
                                                    <button onclick="viewCourseReport(${course.course_id})" class="btn-info">View Details</button>
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            `;
                        } else {
                            container.innerHTML = '<p>No courses found.</p>';
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function viewCourseReport(courseId) {
            fetch(`get_course_attendance_summary.php?course_id=${courseId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const container = document.getElementById('reports-content');
                        if (data.details && data.details.length > 0) {
                            container.innerHTML += `
                                <h3>Session Details</h3>
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Session</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Total</th>
                                            <th>Attended</th>
                                            <th>Absent</th>
                                            <th>Late</th>
                                            <th>Rate</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.details.map(session => {
                                            const total = session.total_students || 0;
                                            const attended = session.attended || 0;
                                            const rate = total > 0 ? ((attended / total) * 100).toFixed(2) : 0;
                                            return `
                                                <tr>
                                                    <td>${session.session_name}</td>
                                                    <td>${session.session_date}</td>
                                                    <td>${session.start_time} - ${session.end_time}</td>
                                                    <td>${total}</td>
                                                    <td>${attended}</td>
                                                    <td>${session.absent || 0}</td>
                                                    <td>${session.late || 0}</td>
                                                    <td>${rate}%</td>
                                                </tr>
                                            `;
                                        }).join('')}
                                    </tbody>
                                </table>
                                <button onclick="loadAttendanceReports()" class="btn-primary">Back to Summary</button>
                            `;
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('content').style.display = 'block';
            loadFacultyStats();
            loadFacultyCourses();
        });
    </script>
    <script src="script.js"></script>
</body>
</html>
