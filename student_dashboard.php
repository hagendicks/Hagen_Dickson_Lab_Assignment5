<?php
require_once 'auth_check.php';
requireStudent();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | Attendance Management</title>
    <link rel="stylesheet" href="stylesheet.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .section {
            display: none;
        }
        .section.active {
            display: block;
        }
        .sidebar li {
            cursor: pointer;
            padding: 10px;
            margin: 5px 0;
            border-radius: 4px;
        }
        .sidebar li:hover {
            background-color: #f0f0f0;
        }
        .sidebar li.active {
            background-color: #003366;
            color: white;
        }
        .loading {
            text-align: center;
            padding: 20px;
            font-style: italic;
            color: #666;
        }
    </style>
</head>
<body>
    <header>
        <h1>Student Dashboard</h1>
        <div>
            Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (Student)
            <button onclick="logout()" style="margin-left: 20px;">Logout</button>
        </div>
    </header>

    <div class="container">
        <nav class="sidebar">
            <ul>
                <li class="active"><a href="#" onclick="loadSection('dashboard')">Dashboard</a></li>
                <li><a href="#" onclick="loadSection('my-courses')">My Courses</a></li>
                <li><a href="#" onclick="loadSection('available-courses')">Available Courses</a></li>
                <li><a href="#" onclick="loadSection('my-requests')">My Requests</a></li>
                <li><a href="#" onclick="loadSection('profile')">My Profile</a></li>
                <li><a href="#" onclick="loadSection('attendance')">Attendance</a></li>
            </ul>
        </nav>
        <main id="main-content">
            <div id="loading" class="loading">
                Loading content...
            </div>
            <div id="content"></div>
        </main>
    </div>

    <script>
        function loadSection(sectionName) {
            document.querySelectorAll('.sidebar li').forEach(item => {
                item.classList.remove('active');
            });
            event.target.parentElement.classList.add('active');
            
            document.getElementById('loading').style.display = 'block';
            document.getElementById('content').innerHTML = '';
            
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `load_section.php?section=${sectionName}`, true);
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    document.getElementById('loading').style.display = 'none';
                    
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                document.getElementById('content').innerHTML = response.content;
                                initializeSection(sectionName);
                            } else {
                                showAlert('error', response.message || 'Failed to load content');
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                            showAlert('error', 'Error loading content');
                        }
                    } else {
                        showAlert('error', `Server error: ${xhr.status}`);
                    }
                }
            };
            
            xhr.onerror = function() {
                document.getElementById('loading').style.display = 'none';
                showAlert('error', 'Network error. Please check your connection.');
            };
            
            xhr.send();
        }
        
        function initializeSection(sectionName) {
            switch(sectionName) {
                case 'dashboard':
                    loadStudentStats();
                    break;
                case 'my-courses':
                    loadMyCourses();
                    break;
                case 'available-courses':
                    loadAvailableCourses();
                    break;
                case 'my-requests':
                    loadMyRequests();
                    break;
                case 'profile':
                    loadProfile();
                    break;
                case 'attendance':
                    loadAttendance();
                    break;
            }
        }
        
        function loadStudentStats() {
            fetch('get_student_stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const statsElement = document.getElementById('student-stats');
                        if (statsElement) {
                            statsElement.innerHTML = `
                                <div class="stats-grid">
                                    <div class="stat-card">
                                        <h4>Enrolled Courses</h4>
                                        <p class="stat-number">${data.enrolled_courses}</p>
                                    </div>
                                    <div class="stat-card">
                                        <h4>Pending Requests</h4>
                                        <p class="stat-number">${data.pending_requests}</p>
                                    </div>
                                    <div class="stat-card">
                                        <h4>Completed Courses</h4>
                                        <p class="stat-number">${data.completed_courses || 0}</p>
                                    </div>
                                    <div class="stat-card">
                                        <h4>Attendance Rate</h4>
                                        <p class="stat-number">${data.attendance_rate || 'N/A'}%</p>
                                    </div>
                                </div>
                            `;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
        
        function loadMyCourses() {
            fetch('get_enrolled_courses.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const coursesList = document.getElementById('enrolled-courses-list');
                        if (coursesList) {
                            if (data.courses.length > 0) {
                                coursesList.innerHTML = `
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Course Code</th>
                                                <th>Course Name</th>
                                                <th>Faculty</th>
                                                <th>Enrollment Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${data.courses.map(course => `
                                                <tr>
                                                    <td>${course.course_code}</td>
                                                    <td>${course.course_name}</td>
                                                    <td>${course.faculty_name}</td>
                                                    <td>${course.approved_at}</td>
                                                    <td>
                                                        <button onclick="viewCourseDetails(${course.course_id})" class="btn-info">View</button>
                                                    </td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                `;
                            } else {
                                coursesList.innerHTML = '<p>No enrolled courses found. Browse available courses to enroll!</p>';
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
        
        function loadAvailableCourses() {
            fetch('get_available_courses.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const coursesList = document.getElementById('available-courses-list');
                        if (coursesList) {
                            if (data.courses.length > 0) {
                                coursesList.innerHTML = `
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Course Code</th>
                                                <th>Course Name</th>
                                                <th>Faculty</th>
                                                <th>Description</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${data.courses.map(course => `
                                                <tr>
                                                    <td>${course.course_code}</td>
                                                    <td>${course.course_name}</td>
                                                    <td>${course.faculty_name}</td>
                                                    <td>${course.description ? course.description.substring(0, 50) + '...' : 'N/A'}</td>
                                                    <td>
                                                        <button onclick="viewCourseDetails(${course.course_id})" class="btn-info">View</button>
                                                        <button onclick="requestEnrollment(${course.course_id})" class="btn-primary">Join</button>
                                                    </td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                `;
                            } else {
                                coursesList.innerHTML = '<p>No available courses found.</p>';
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
        
        function loadMyRequests() {
            fetch('get_my_requests.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const requestsList = document.getElementById('my-requests-list');
                        if (requestsList) {
                            if (data.requests.length > 0) {
                                requestsList.innerHTML = `
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Course</th>
                                                <th>Faculty</th>
                                                <th>Status</th>
                                                <th>Requested Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${data.requests.map(request => `
                                                <tr>
                                                    <td>${request.course_name} (${request.course_code})</td>
                                                    <td>${request.faculty_name}</td>
                                                    <td>
                                                        <span class="status-${request.status}">${request.status.charAt(0).toUpperCase() + request.status.slice(1)}</span>
                                                    </td>
                                                    <td>${request.requested_date}</td>
                                                    <td>
                                                        ${request.status === 'pending' ? 
                                                            `<button onclick="cancelRequest(${request.enrollment_id})" class="btn-danger">Cancel</button>` : 
                                                            ''
                                                        }
                                                    </td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                `;
                            } else {
                                requestsList.innerHTML = '<p>No enrollment requests found.</p>';
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
        
        function loadProfile() {
            fetch('get_profile.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const content = document.getElementById('content');
                        const profileSection = document.getElementById('profile-section') || 
                            (() => {
                                const div = document.createElement('div');
                                div.id = 'profile-section';
                                content.appendChild(div);
                                return div;
                            })();
                        
                        profileSection.innerHTML = `
                            <div class="card">
                                <h2>My Profile</h2>
                                <div class="profile-info">
                                    <p><strong>Name:</strong> ${data.user.first_name} ${data.user.last_name}</p>
                                    <p><strong>Email:</strong> ${data.user.email}</p>
                                    <p><strong>Role:</strong> ${data.user.role}</p>
                                    <p><strong>Date of Birth:</strong> ${data.user.dob || 'Not specified'}</p>
                                    <p><strong>Member Since:</strong> ${data.user.created_at}</p>
                                    <p><strong>Last Login:</strong> ${data.user.last_login || 'Never'}</p>
                                </div>
                                <button onclick="editProfile()" class="btn-primary">Edit Profile</button>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
        
        function loadAttendance() {
            fetch('get_attendance.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const content = document.getElementById('content');
                        const attendanceSection = document.getElementById('attendance-section') || 
                            (() => {
                                const div = document.createElement('div');
                                div.id = 'attendance-section';
                                content.appendChild(div);
                                return div;
                            })();
                        
                        if (data.attendance.length > 0) {
                            attendanceSection.innerHTML = `
                                <div class="card">
                                    <h2>My Attendance</h2>
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Course</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                                <th>Session</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${data.attendance.map(record => `
                                                <tr>
                                                    <td>${record.course_name}</td>
                                                    <td>${record.date}</td>
                                                    <td><span class="status-${record.status.toLowerCase()}">${record.status}</span></td>
                                                    <td>${record.session_name || 'Regular'}</td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                            `;
                        } else {
                            attendanceSection.innerHTML = `
                                <div class="card">
                                    <h2>My Attendance</h2>
                                    <p>No attendance records found.</p>
                                </div>
                            `;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
        
        function editProfile() {
            showAlert('info', 'Profile editing functionality will be added soon!');
        }
        
        function requestEnrollment(courseId) {
            if (confirm('Are you sure you want to request enrollment in this course?')) {
                fetch('request_enrollment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        course_id: courseId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', data.message);
                        loadAvailableCourses();
                        loadMyRequests();
                        loadStudentStats();
                    } else {
                        showAlert('error', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('error', 'An error occurred while requesting enrollment.');
                });
            }
        }
        
        function cancelRequest(enrollmentId) {
            if (confirm('Are you sure you want to cancel this enrollment request?')) {
                fetch('cancel_enrollment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        enrollment_id: enrollmentId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', data.message);
                        loadMyRequests();
                        loadStudentStats();
                    } else {
                        showAlert('error', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('error', 'An error occurred while canceling the request.');
                });
            }
        }
        
        function viewCourseDetails(courseId) {
            fetch(`get_course_details.php?course_id=${courseId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('courseModalContent').innerHTML = `
                            <h4>${data.course.course_name} (${data.course.course_code})</h4>
                            <p><strong>Faculty:</strong> ${data.course.faculty_name}</p>
                            <p><strong>Description:</strong> ${data.course.description || 'N/A'}</p>
                            <p><strong>Created:</strong> ${data.course.created_at}</p>
                            <p><strong>Enrolled Students:</strong> ${data.course.enrolled_count}</p>
                        `;
                        document.getElementById('courseModal').style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
        
        function showAlert(type, message, title = '') {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: type,
                    title: title || message,
                    text: title ? message : '',
                    confirmButtonColor: '#003366',
                    timer: type === 'success' ? 2000 : undefined,
                    showConfirmButton: type !== 'success'
                });
            } else {
                alert(message);
            }
        }

        function loadAttendance() {
    fetch('get_student_attendance_report.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const content = document.getElementById('content');
                const attendanceSection = document.getElementById('attendance-section') || 
                    (() => {
                        const div = document.createElement('div');
                        div.id = 'attendance-section';
                        content.appendChild(div);
                        return div;
                    })();
                
                let html = `
                    <div class="card">
                        <h2>My Attendance</h2>
                        <div style="margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: 5px;">
                            <h3>Mark Today's Attendance</h3>
                            <div class="form-group">
                                <label>Enter Session Code:</label>
                                <input type="text" id="session-code" placeholder="Enter 6-digit code">
                                <button onclick="markAttendanceWithCode()" class="btn-primary" style="margin-top: 10px;">Mark Attendance</button>
                            </div>
                            <div id="attendance-result" style="margin-top: 10px;"></div>
                        </div>
                        
                        <h3>Overall Statistics</h3>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <h4>Total Sessions</h4>
                                <p class="stat-number">${data.stats.total_sessions}</p>
                            </div>
                            <div class="stat-card">
                                <h4>Present</h4>
                                <p class="stat-number">${data.stats.present}</p>
                            </div>
                            <div class="stat-card">
                                <h4>Absent</h4>
                                <p class="stat-number">${data.stats.absent}</p>
                            </div>
                            <div class="stat-card">
                                <h4>Attendance Rate</h4>
                                <p class="stat-number">${data.stats.attendance_rate}%</p>
                            </div>
                        </div>
                `;
                
                if (data.stats.courses && Object.keys(data.stats.courses).length > 0) {
                    html += `
                        <h3>Course-wise Attendance</h3>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Total</th>
                                    <th>Present</th>
                                    <th>Absent</th>
                                    <th>Late</th>
                                    <th>Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${Object.values(data.stats.courses).map(course => `
                                    <tr>
                                        <td>${course.course_code} - ${course.course_name}</td>
                                        <td>${course.total}</td>
                                        <td>${course.present}</td>
                                        <td>${course.absent}</td>
                                        <td>${course.late}</td>
                                        <td>${course.rate}%</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    `;
                }
                
                if (data.attendance.length > 0) {
                    html += `
                        <h3>Recent Attendance Records</h3>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Session</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Check-in Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.attendance.map(record => `
                                    <tr>
                                        <td>${record.course_name}</td>
                                        <td>${record.session_name}</td>
                                        <td>${record.session_date}</td>
                                        <td><span class="status-${record.status}">${record.status}</span></td>
                                        <td>${record.check_in_time || 'N/A'}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    `;
                } else {
                    html += '<p>No attendance records found.</p>';
                }
                
                html += '</div>';
                attendanceSection.innerHTML = html;
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function markAttendanceWithCode() {
    const sessionCode = document.getElementById('session-code').value.trim();
    const resultDiv = document.getElementById('attendance-result');
    
    if (!sessionCode) {
        resultDiv.innerHTML = '<div class="message error">Please enter a session code</div>';
        return;
    }
    
    resultDiv.innerHTML = '<div class="message info">Marking attendance...</div>';
    
    fetch('student_mark_attendance.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({session_code: sessionCode})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = `<div class="message success">Attendance marked successfully! Status: ${data.status}</div>`;
            document.getElementById('session-code').value = '';
            loadAttendance();
        } else {
            resultDiv.innerHTML = `<div class="message error">${data.message}</div>`;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        resultDiv.innerHTML = '<div class="message error">An error occurred</div>';
    });
}
        
        document.addEventListener('DOMContentLoaded', function() {
            loadSection('dashboard');
        });
    </script>
    <script src="script.js"></script>
</body>
</html>