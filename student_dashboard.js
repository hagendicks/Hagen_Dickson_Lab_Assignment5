let currentSection = 'dashboard';

function showSection(sectionName) {
    document.querySelectorAll('.section').forEach(section => {
        section.classList.remove('active');
    });
    document.querySelectorAll('.sidebar li').forEach(item => {
        item.classList.remove('active');
    });

    document.getElementById(sectionName).classList.add('active');
    event.target.parentElement.classList.add('active');
    currentSection = sectionName;

    if (sectionName === 'my-courses') {
        loadMyCourses();
    } else if (sectionName === 'available-courses') {
        loadAvailableCourses();
    } else if (sectionName === 'my-requests') {
        loadMyRequests();
    } else if (sectionName === 'dashboard') {
        loadStudentStats();
    }
}

function loadStudentStats() {
    fetch('get_student_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('student-stats').innerHTML = `
                    <p>Enrolled Courses: <strong>${data.enrolled_courses}</strong></p>
                    <p>Pending Requests: <strong>${data.pending_requests}</strong></p>
                    <p>Completed Courses: <strong>${data.completed_courses || 0}</strong></p>
                `;
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
        })
        .catch(error => {
            console.error('Error:', error);
        });
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

document.querySelector('.close').addEventListener('click', function() {
    document.getElementById('courseModal').style.display = 'none';
});

window.addEventListener('click', function(event) {
    const modal = document.getElementById('courseModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
});

document.addEventListener('DOMContentLoaded', function() {
    loadStudentStats();
    loadMyCourses();
    loadMyRequests();
});