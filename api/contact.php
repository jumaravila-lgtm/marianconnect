<?php
/**
 * MARIANCONNECT - Contact Form API
 */

header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/functions.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([
        'success' => false,
        'error' => [
            'code' => 'METHOD_NOT_ALLOWED',
            'message' => 'Only POST requests are allowed'
        ]
    ], 405);
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // If JSON decode failed, try regular POST
    if ($input === null) {
        $input = $_POST;
    }
    
    // Extract and sanitize inputs
    $fullName = sanitize($input['full_name'] ?? '');
    $email = sanitize($input['email'] ?? '');
    $phone = sanitize($input['phone'] ?? '');
    $subject = sanitize($input['subject'] ?? '');
    $message = sanitize($input['message'] ?? '');
    
    // Validation
    $errors = [];
    
    // Required fields
    if (empty($fullName)) {
        $errors[] = 'Full name is required';
    } elseif (strlen($fullName) < 2) {
        $errors[] = 'Full name must be at least 2 characters';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!validateEmail($email)) {
        $errors[] = 'Invalid email format';
    }
    
    if (empty($subject)) {
        $errors[] = 'Subject is required';
    } elseif (strlen($subject) < 5) {
        $errors[] = 'Subject must be at least 5 characters';
    }
    
    if (empty($message)) {
        $errors[] = 'Message is required';
    } elseif (strlen($message) < 10) {
        $errors[] = 'Message must be at least 10 characters';
    }
    
    // Phone validation (optional)
    if (!empty($phone)) {
        $phone = preg_replace('/[^0-9+\-() ]/', '', $phone);
        if (strlen($phone) < 10) {
            $errors[] = 'Invalid phone number';
        }
    }
    
    // Return validation errors
    if (!empty($errors)) {
        jsonResponse([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'Validation failed',
                'details' => $errors
            ]
        ], 400);
    }
    
    // Rate limiting - check if IP has sent too many messages recently
    $db = getDB();
    $clientIP = getClientIP();
    
    $rateLimitStmt = $db->prepare("
        SELECT COUNT(*) as message_count 
        FROM contact_messages 
        WHERE ip_address = ? 
        AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $rateLimitStmt->execute([$clientIP]);
    $rateLimitResult = $rateLimitStmt->fetch();
    
    if ($rateLimitResult['message_count'] >= 5) {
        jsonResponse([
            'success' => false,
            'error' => [
                'code' => 'RATE_LIMIT_EXCEEDED',
                'message' => 'Too many messages sent. Please try again later.'
            ]
        ], 429);
    }
    
    // Insert message into database
    $stmt = $db->prepare("
        INSERT INTO contact_messages 
        (full_name, email, phone, subject, message, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $fullName,
        $email,
        $phone,
        $subject,
        $message,
        $clientIP,
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    if ($result) {
        // Optional: Send email notification to admin
        // sendEmailNotification($fullName, $email, $subject, $message);
        
        jsonResponse([
            'success' => true,
            'message' => 'Thank you for contacting us! Your message has been sent successfully. We will respond to you shortly.',
            'data' => [
                'message_id' => $db->lastInsertId(),
                'submitted_at' => date('Y-m-d H:i:s')
            ]
        ], 201);
    } else {
        throw new Exception('Failed to save message');
    }
    
} catch (PDOException $e) {
    error_log("Contact form database error: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => [
            'code' => 'DATABASE_ERROR',
            'message' => 'An error occurred while processing your message. Please try again later.'
        ]
    ], 500);
    
} catch (Exception $e) {
    error_log("Contact form error: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => [
            'code' => 'SERVER_ERROR',
            'message' => 'An unexpected error occurred. Please try again later.'
        ]
    ], 500);
}

/**
 * Send email notification to admin (optional)
 */
function sendEmailNotification($name, $email, $subject, $message) {
    $adminEmail = getSiteSetting('contact_email', 'info@smcc.edu.ph');
    $siteName = getSiteSetting('site_name', 'SMCC');
    
    $emailSubject = "[{$siteName}] New Contact Message: {$subject}";
    
    $emailBody = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #003f87; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f8f9fa; }
            .field { margin-bottom: 15px; }
            .label { font-weight: bold; color: #003f87; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>New Contact Message</h2>
            </div>
            <div class='content'>
                <div class='field'>
                    <div class='label'>Name:</div>
                    <div>{$name}</div>
                </div>
                <div class='field'>
                    <div class='label'>Email:</div>
                    <div>{$email}</div>
                </div>
                <div class='field'>
                    <div class='label'>Subject:</div>
                    <div>{$subject}</div>
                </div>
                <div class='field'>
                    <div class='label'>Message:</div>
                    <div>" . nl2br(htmlspecialchars($message)) . "</div>
                </div>
            </div>
            <div class='footer'>
                <p>This message was sent via the {$siteName} contact form.</p>
                <p>Reply directly to: {$email}</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=utf-8\r\n";
    $headers .= "From: {$siteName} <noreply@smcc.edu.ph>\r\n";
    $headers .= "Reply-To: {$email}\r\n";
    
    return mail($adminEmail, $emailSubject, $emailBody, $headers);
}
?>
