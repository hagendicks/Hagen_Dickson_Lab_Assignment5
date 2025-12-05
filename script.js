function validateSignup(event) {
    event.preventDefault();
    console.log('Form submitted!');
    const firstNameInput = document.getElementById("first_name");
    const lastNameInput = document.getElementById("last_name");
    const emailInput = document.getElementById("email");
    const passwordInput = document.getElementById("password");
    const roleInput = document.getElementById("role");
    const dobInput = document.getElementById("dob");
    const firstName = firstNameInput.value.trim();
    const lastName = lastNameInput.value.trim();
    const email = emailInput.value.trim();
    const password = passwordInput.value;
    const role = roleInput ? roleInput.value : 'student';
    const dob = dobInput ? dobInput.value : null;
    console.log('Form data:', { firstName, lastName, email, role });
    if (!firstName || !lastName || !email || !password) {
        showAlert('error', 'All required fields must be filled!');
        return false;
    }
    const namePattern = /^[a-zA-Z\s]{2,}$/;
    if (!namePattern.test(firstName)) {
        showAlert('error', 'First name must contain only letters and spaces (minimum 2 characters)');
        firstNameInput.focus();
        return false;
    }
    if (!namePattern.test(lastName)) {
        showAlert('error', 'Last name must contain only letters and spaces (minimum 2 characters)');
        lastNameInput.focus();
        return false;
    }
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(email)) {
        showAlert('error', 'Please enter a valid email address');
        emailInput.focus();
        return false;
    }
    if (password.length < 6) {
        showAlert('error', 'Password must be at least 6 characters long');
        passwordInput.focus();
        return false;
    }
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Creating Account...';
    const userData = {
        first_name: firstName,
        last_name: lastName,
        email: email,
        password: password,
        role: role
    };
    if (dob) {
        userData.dob = dob;
    }
    console.log('Sending to server:', userData);
    fetch('signup.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(userData)
        })
        .then(response => {
            console.log('Response received:', response);
            console.log('Response status:', response.status);
            console.log('Response ok:', response.ok);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.text();
        })
        .then(text => {
            console.log('Raw response text:', text);
            let data;
            try {
                data = JSON.parse(text);
                console.log('Parsed JSON:', data);
            } catch (e) {
                console.error('JSON Parse Error:', e);
                console.error('Received text was:', text);
                throw new Error('Server returned invalid JSON: ' + text.substring(0, 100));
            }
            if (data.success) {
                showAlert('success', data.message, 'Account Created!');
                firstNameInput.value = '';
                lastNameInput.value = '';
                emailInput.value = '';
                passwordInput.value = '';
                if (roleInput) roleInput.value = 'student';
                if (dobInput) dobInput.value = '';
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            } else {
                showAlert('error', data.message || 'Registration failed');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        })
        .catch(error => {
            console.error('FULL ERROR:', error);
            console.error('Error name:', error.name);
            console.error('Error message:', error.message);
            console.error('Error stack:', error.stack);
            showAlert('error', 'Error: ' + error.message);
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    return false;
}

function validateLogin(event) {
    event.preventDefault();
    const emailInput = document.getElementById("email");
    const passwordInput = document.getElementById("password");
    const rememberMeInput = document.getElementById("remember_me");
    const email = emailInput.value.trim();
    const password = passwordInput.value;
    const remember_me = rememberMeInput ? rememberMeInput.checked : false;

    if (!email || !password) {
        showAlert('error', 'Please fill in all fields');
        return false;
    }
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(email)) {
        showAlert('error', 'Please enter a valid email address');
        emailInput.focus();
        return false;
    }
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Logging In...';
    fetch('login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                email: email,
                password: password,
                remember_me: remember_me
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', `Welcome back, ${data.username}!`, 'Login Successful!');
                sessionStorage.setItem('username', data.username);
                sessionStorage.setItem('user_id', data.user_id);
                setTimeout(() => {
                    window.location.href = 'dashboard.php';
                }, 1500);
            } else {
                showAlert('error', data.message);
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'An error occurred. Please try again.');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    return false;
}

function checkExistingSession() {
    const currentPage = window.location.pathname.split('/').pop();
    if (currentPage === 'login.php' || currentPage === 'signup.php') {
        fetch('check_session.php')
            .then(response => response.json())
            .then(data => {
                if (data.logged_in) {
                    showAlert('info', 'You are already logged in!');
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 1000);
                }
            })
            .catch(error => {
                console.error('Session check error:', error);
            });
    }
}

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        fetch('logout.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    sessionStorage.clear();
                    showAlert('success', 'Logged out successfully!');
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 1000);
                }
            })
            .catch(error => {
                console.error('Logout error:', error);
                window.location.href = 'login.php';
            });
    }
}

function protectPage() {
    const protectedPages = ['dashboard.php', 'courses.php', 'sessions.php',
        'attendance.php', 'profile.php'
    ];
    const currentPage = window.location.pathname.split('/').pop();
    if (protectedPages.includes(currentPage)) {
        fetch('check_session.php')
            .then(response => response.json())
            .then(data => {
                if (!data.logged_in) {
                    showAlert('warning', 'Please login to access this page');
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 1000);
                } else {
                    const userDisplay = document.getElementById('userDisplay');
                    if (userDisplay) {
                        userDisplay.textContent = `Welcome, ${data.username}`;
                    }
                }
            })
            .catch(error => {
                console.error('Session check error:', error);
            });
    }
}

function showAlert(type, message, title = '') {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: type,
            title: title || message,
            text: title ? message : '',
            confirmButtonColor: '#2E2E2E',
            timer: type === 'success' ? 2000 : undefined,
            showConfirmButton: type !== 'success'
        });
    } else {
        alert(message);
    }
}
document.addEventListener('DOMContentLoaded', function() {
    checkExistingSession();
    protectPage();
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', logout);
    }
});function ajaxLogin(email, password, rememberMe = false) {
    return new Promise((resolve, reject) => {
        fetch('login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                email: email,
                password: password,
                remember_me: rememberMe
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                resolve(data);
            } else {
                reject(new Error(data.message || 'Login failed'));
            }
        })
        .catch(error => {
            reject(error);
        });
    });
}

function validateLogin(event) {
    event.preventDefault();
    
    const emailInput = document.getElementById("email");
    const passwordInput = document.getElementById("password");
    const rememberMeInput = document.getElementById("remember_me");
    const email = emailInput.value.trim();
    const password = passwordInput.value;
    const remember_me = rememberMeInput ? rememberMeInput.checked : false;

    if (!email || !password) {
        showAlert('error', 'Please fill in all fields');
        return false;
    }

    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(email)) {
        showAlert('error', 'Please enter a valid email address');
        emailInput.focus();
        return false;
    }

    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Logging In...';

    ajaxLogin(email, password, remember_me)
        .then(data => {
            showAlert('success', `Welcome back, ${data.username}!`, 'Login Successful!');
            
            sessionStorage.setItem('username', data.username);
            sessionStorage.setItem('user_id', data.user_id);
            sessionStorage.setItem('role', data.role);
            sessionStorage.setItem('logged_in', 'true');
            
            setTimeout(() => {
                window.location.href = 'dashboard.php';
            }, 1500);
        })
        .catch(error => {
            showAlert('error', error.message);
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });

    return false;
}

function attemptAutoLogin() {
    const savedEmail = localStorage.getItem('auto_login_email');
    const savedPassword = localStorage.getItem('auto_login_password');
    
    if (savedEmail && savedPassword) {
        ajaxLogin(savedEmail, atob(savedPassword), true)
            .then(data => {
                window.location.href = 'dashboard.php';
            })
            .catch(error => {
                localStorage.removeItem('auto_login_email');
                localStorage.removeItem('auto_login_password');
            });
    }
}

function saveCredentialsForAutoLogin(email, password) {
    if (document.getElementById('remember_me').checked) {
        localStorage.setItem('auto_login_email', email);
        localStorage.setItem('auto_login_password', btoa(password));
    } else {
        localStorage.removeItem('auto_login_email');
        localStorage.removeItem('auto_login_password');
    }
}