<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure proper response type
header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Unknown error occurred.'];

try {
    // Ensure POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    // Sanitize and validate inputs
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($phone) || empty($message)) {
        throw new Exception('All fields are required.');
    }

    // Database connection
    $conn = new mysqli('127.0.0.1', 'root', '', 'ecolit');
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    // Prepare and execute query
    $stmt = $conn->prepare('INSERT INTO contact_form (name, email, phone, message) VALUES (?, ?, ?, ?)');
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('ssss', $name, $email, $phone, $message);
    if (!$stmt->execute()) {
        throw new Exception('Database insert failed: ' . $stmt->error);
    }

    // Send email using PHPMailer
    $mail = new PHPMailer(true);
    try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com'; // Your Gmail
        $mail->Password = 'your-app-password'; // Use App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Email content
        $mail->setFrom($email, $name);
        $mail->addAddress('chandanr551997@gmail.com');
        $mail->Subject = 'New Contact Form Submission';
        $mail->Body = "Name: $name\nEmail: $email\nPhone: $phone\nMessage:\n$message";

        $mail->send();
    } catch (Exception $e) {
        throw new Exception('Email sending failed: ' . $mail->ErrorInfo);
    }

    $response = ['status' => 'success', 'message' => 'Your message has been sent. Thank you!'];
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Ensure no extra output
if (ob_get_length()) ob_end_clean();
echo json_encode($response);
exit;
?>