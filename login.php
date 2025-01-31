<?php
session_start();
require_once 'db_connect.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'redirect' => ''
];

// Function to log login attempts
function logLoginAttempt($username, $status, $ip, $conn) {
    $stmt = $conn->prepare("INSERT INTO login_attempts (username, status, ip_address, attempt_time) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("sss", $username, $status, $ip);
    $stmt->execute();
}

// Function to check if account is locked
function isAccountLocked($username, $conn) {
    $stmt = $conn->prepare("SELECT COUNT(*) as attempts FROM login_attempts 
                           WHERE username = ? AND status = 'failed' 
                           AND attempt_time > NOW() - INTERVAL 15 MINUTE");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['attempts'] >= 5;
}

try {
    // Check if the request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception('Invalid security token');
    }

    // Validate required fields
    $required_fields = ['username', 'password', 'userType'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            throw new Exception('All fields are required');
        }
    }

    // Sanitize inputs
    $username = filter_var(trim($_POST['username']), FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    $userType = filter_var(trim($_POST['userType']), FILTER_SANITIZE_STRING);
    $rememberMe = isset($_POST['rememberMe']) ? true : false;

    // Get client IP address
    $ip_address = $_SERVER['REMOTE_ADDR'];

    // Check if account is locked
    if (isAccountLocked($username, $conn)) {
        throw new Exception('Account temporarily locked. Please try again later.');
    }

    // Prepare SQL statement based on user type
    $table = ($userType === 'teacher') ? 'teachers' : 'students';
    $stmt = $conn->prepare("SELECT id, username, password, email, status, role FROM $table WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        logLoginAttempt($username, 'failed', $ip_address, $conn);
        throw new Exception('Invalid credentials');
    }

    $user = $result->fetch_assoc();

    // Check if account is active
    if ($user['status'] !== 'active') {
        throw new Exception('Account is not active. Please contact administrator.');
    }

    // Verify password
    if (!password_verify($password, $user['password'])) {
        logLoginAttempt($username, 'failed', $ip_address, $conn);
        throw new Exception('Invalid credentials');
    }

    // Log successful login
    logLoginAttempt($username, 'success', $ip_address, $conn);

    // Generate new session ID to prevent session fixation
    session_regenerate_id(true);

    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['user_type'] = $userType;
    $_SESSION['last_activity'] = time();

    // Set remember me cookie if requested
    if ($rememberMe) {
        $selector = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));
        $token_hash = password_hash($validator, PASSWORD_DEFAULT);
        $expiry = date('Y-m-d H:i:s', time() + 30 * 24 * 60 * 60); // 30 days

        // Store token in database
        $stmt = $conn->prepare("INSERT INTO auth_tokens (user_id, selector, token, expiry) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user['id'], $selector, $token_hash, $expiry);
        $stmt->execute();

        // Set cookie
        setcookie(
            'remember_me',
            $selector . ':' . $validator,
            time() + 30 * 24 * 60 * 60, // 30 days
            '/',
            '',
            true, // Secure
            true  // HttpOnly
        );
    }

    // Set response for successful login
    $response['success'] = true;
    $response['message'] = 'Login successful';
    $response['redirect'] = $userType === 'teacher' ? 'teacher_dashboard.php' : 'student_dashboard.php';

    // Clear any existing login attempt records if login is successful
    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
} finally {
    // Close database connection
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }

    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Example credentials for testing (in real application, these would be in the database)
/*
CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    role ENUM('student') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE teachers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    role ENUM('teacher') DEFAULT 'teacher',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE login_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    status ENUM('success', 'failed') NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE auth_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    selector VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expiry DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample users (passwords are hashed versions of 'password123')
INSERT INTO students (username, password, email, status, role) VALUES 
('student1', '$2y$10$YourHashedPasswordHere', 'student1@example.com', 'active', 'student');

INSERT INTO teachers (username, password, email, status, role) VALUES 
('teacher1', '$2y$10$YourHashedPasswordHere', 'teacher1@example.com', 'active', 'teacher');
*/
