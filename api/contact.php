<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define constant for includes
define('ALLOW_INCLUDE', true);

// Include config
require_once __DIR__ . '/../includes/config.php';

// Set JSON header
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// CSRF check
$csrf_token = $_POST['csrf_token'] ?? '';
if (!verifyCSRFToken($csrf_token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

// Get form data and sanitize
$first_name = isset($_POST['first_name']) ? sanitize($_POST['first_name']) : '';
$last_name = isset($_POST['last_name']) ? sanitize($_POST['last_name']) : '';
$email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
$phone = isset($_POST['phone']) ? sanitize($_POST['phone']) : '';
$subject = isset($_POST['subject']) ? sanitize($_POST['subject']) : '';
$message = isset($_POST['message']) ? sanitize($_POST['message']) : '';

// Additional email header injection protection
$email = str_replace(["\r", "\n", "%0a", "%0d"], '', $email);
$subject = str_replace(["\r", "\n", "%0a", "%0d"], '', $subject);

// Validation
$errors = [];

if (empty($first_name)) {
    $errors[] = 'First name is required';
}

if (empty($last_name)) {
    $errors[] = 'Last name is required';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Valid email is required';
}

if (empty($subject)) {
    $errors[] = 'Subject is required';
}

if (empty($message)) {
    $errors[] = 'Message is required';
}

// Return errors if any
if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => implode(', ', $errors)
    ]);
    exit;
}

// Try to save to database
try {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        INSERT INTO contact_messages 
        (first_name, last_name, email, phone, subject, message, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([$first_name, $last_name, $email, $phone, $subject, $message]);
    
    // Send email notification to admin
    $to = ADMIN_EMAIL;
    $email_subject = "New Contact Form Submission: " . $subject;
    $email_message = "
        New contact form submission from Q-Bab Burger website:
        
        Name: $first_name $last_name
        Email: $email
        Phone: $phone
        Subject: $subject
        
        Message:
        $message
        
        ---
        This is an automated message from Q-Bab Burger contact form.
    ";
    
    // Secure email headers - prevent injection
    $safe_email = filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : ADMIN_EMAIL;
    $headers = "From: noreply@q-bab.de\r\n";
    $headers .= "Reply-To: " . $safe_email . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    @mail($to, $email_subject, $email_message, $headers);
    
    echo json_encode([
        'success' => true,
        'message' => 'Message sent successfully!'
    ]);
    
} catch (Exception $e) {
    error_log("Contact form error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again later.'
    ]);
}
?>
