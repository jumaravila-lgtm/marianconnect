<?php
/**
 * MARIANCONNECT - Contact Page
 * Contact form with database storage
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define the base path
define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/settings.php';
require_once BASE_PATH . '/config/security.php';
require_once BASE_PATH . '/includes/functions.php';

// Track visitor
try {
    trackVisitor();
} catch (Exception $e) {
    error_log("Visitor tracking error: " . $e->getMessage());
}

$db = getDB();
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize form data
    $full_name = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');
    
    // Validate required fields
    $errors = [];
    
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!validateEmail($email)) {
        $errors[] = "Please enter a valid email address";
    }
    
    if (empty($subject)) {
        $errors[] = "Subject is required";
    }
    
    if (empty($message)) {
        $errors[] = "Message is required";
    }
    
    if (empty($errors)) {
        try {
            // Insert into database
            $stmt = $db->prepare("
                INSERT INTO contact_messages 
                (full_name, email, phone, subject, message, ip_address, user_agent, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'new')
            ");
            
            $stmt->execute([
                $full_name,
                $email,
                $phone,
                $subject,
                $message,
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT']
            ]);
            
            $success_message = "Thank you for contacting us! We'll get back to you soon.";
            
            // Clear form data
            $full_name = $email = $phone = $subject = $message = '';
            
        } catch (Exception $e) {
            error_log("Contact form error: " . $e->getMessage());
            $error_message = "Sorry, there was an error sending your message. Please try again later.";
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}

$pageTitle = 'Contact Us - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <meta name="description" content="Get in touch with St. Mary's College of Catbalogan. Visit us, call us, or send us a message.">
    <meta name="keywords" content="Contact SMCC, Location, Phone, Email, Address">
    
    <link rel="icon" type="image/x-icon" href="<?php echo asset('images/logo/favicon.ico'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/main.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/responsive.css'); ?>">
    
    <!-- Component CSS Files -->
    <link rel="stylesheet" href="<?php echo asset('css/components/navbar.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/components/footer.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/components/cards.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/components/forms.css'); ?>">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css">
</head>
<body>
    
    <?php 
    $header_path = BASE_PATH . '/includes/header.php';
    if (file_exists($header_path)) {
        include $header_path;
    }
    ?>
    
    <!-- Page Header -->
       <section class="page-header" style="background: linear-gradient(135deg, rgba(0, 63, 135, 0.7), rgba(0, 40, 85, 0.9)), url('<?php echo asset("images/school header.jpg"); ?>') center/cover no-repeat;">
        <div class="page-header-overlay"></div>
        <div class="container">
            <div class="page-header-content" data-aos="fade-up">
                <h1 class="page-title">Contact Us</h1>
                <nav class="breadcrumb">
                    <a href="<?php echo url(); ?>">Home</a>
                    <span class="separator">/</span>
                    <span class="current">Contact</span>
                </nav>
            </div>
        </div>
    </section>
    
    <!-- Contact Info Section -->
    <section class="contact-info-section section-padding">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up">
                    <div class="contact-info-card">
                        <div class="info-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h3>Visit Us</h3>
                        <p><?php echo htmlspecialchars(getSiteSetting('contact_address', 'Catbalogan City, Samar, Philippines')); ?></p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="contact-info-card">
                        <div class="info-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h3>Call Us</h3>
                        <p><?php echo htmlspecialchars(getSiteSetting('contact_phone', '(055) 251-2345')); ?></p>
                        <p class="text-muted small">Monday - Friday: 8:00 AM - 5:00 PM</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="contact-info-card">
                        <div class="info-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h3>Email Us</h3>
                        <p><a href="mailto:<?php echo getSiteSetting('contact_email', 'info@smcc.edu.ph'); ?>">
                            <?php echo htmlspecialchars(getSiteSetting('contact_email', 'info@smcc.edu.ph')); ?>
                        </a></p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Contact Form & Map Section -->
    <section class="contact-form-section section-padding bg-light">
        <div class="container">
            <div class="row">
                <!-- Contact Form -->
                <div class="col-md-7" data-aos="fade-up">
                    <div class="contact-form-wrapper">
                        <h2>Send Us a Message</h2>
                        <p class="form-subtitle">Have a question or feedback? Fill out the form below and we'll get back to you as soon as possible.</p>
                        
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                <?php echo $success_message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" class="contact-form" id="contactForm">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="full_name">Full Name <span class="required">*</span></label>
                                    <input type="text" id="full_name" name="full_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($full_name ?? ''); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email Address <span class="required">*</span></label>
                                    <input type="email" id="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="tel" id="phone" name="phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="subject">Subject <span class="required">*</span></label>
                                    <input type="text" id="subject" name="subject" class="form-control" 
                                           value="<?php echo htmlspecialchars($subject ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="message">Message <span class="required">*</span></label>
                                <textarea id="message" name="message" class="form-control" rows="6" required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane"></i> Send Message
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Map & Additional Info -->
                <div class="col-md-5" data-aos="fade-up" data-aos-delay="100">
                    <!-- Map -->
                    <div class="map-wrapper">
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3925.8!2d124.88!3d11.77!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMTHCsDQ2JzEyLjAiTiAxMjTCsDUyJzQ4LjAiRQ!5e0!3m2!1sen!2sph!4v1234567890"
                            width="100%" 
                            height="300" 
                            style="border:0; border-radius: 12px;" 
                            allowfullscreen="" 
                            loading="lazy">
                        </iframe>
                    </div>
                    
                    <!-- Office Hours -->
                    <div class="office-hours-card">
                        <h3><i class="fas fa-clock"></i> Office Hours</h3>
                        <ul class="hours-list">
                            <li>
                                <span class="day">Monday - Friday</span>
                                <span class="time">8:00 AM - 5:00 PM</span>
                            </li>
                            <li>
                                <span class="day">Saturday</span>
                                <span class="time">8:00 AM - 12:00 PM</span>
                            </li>
                            <li>
                                <span class="day">Sunday</span>
                                <span class="time">Closed</span>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Social Media -->
                    <div class="social-connect-card">
                        <h3><i class="fas fa-share-alt"></i> Connect With Us</h3>
                        <div class="social-buttons">
                            <?php
                            $facebook = getSiteSetting('facebook_url', FACEBOOK_URL);
                            $twitter = getSiteSetting('twitter_url', TWITTER_URL);
                            $instagram = getSiteSetting('instagram_url', INSTAGRAM_URL);
                            $youtube = getSiteSetting('youtube_url', YOUTUBE_URL);
                            
                            if (!empty($facebook)): ?>
                                <a href="<?php echo htmlspecialchars($facebook); ?>" target="_blank" class="social-btn facebook">
                                    <i class="fab fa-facebook-f"></i> Facebook
                                </a>
                            <?php endif;
                            
                            if (!empty($twitter)): ?>
                                <a href="<?php echo htmlspecialchars($twitter); ?>" target="_blank" class="social-btn twitter">
                                    <i class="fab fa-twitter"></i> Twitter
                                </a>
                            <?php endif;
                            
                            if (!empty($instagram)): ?>
                                <a href="<?php echo htmlspecialchars($instagram); ?>" target="_blank" class="social-btn instagram">
                                    <i class="fab fa-instagram"></i> Instagram
                                </a>
                            <?php endif;
                            
                            if (!empty($youtube)): ?>
                                <a href="<?php echo htmlspecialchars($youtube); ?>" target="_blank" class="social-btn youtube">
                                    <i class="fab fa-youtube"></i> YouTube
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <?php 
    $footer_path = BASE_PATH . '/includes/footer.php';
    if (file_exists($footer_path)) {
        include $footer_path;
    }
    ?>
    
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true
        });
        
        // Form validation
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            const fullName = document.getElementById('full_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const subject = document.getElementById('subject').value.trim();
            const message = document.getElementById('message').value.trim();
            
            if (!fullName || !email || !subject || !message) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return false;
            }
        });
    </script>
</body>
</html>

<style>
    /* Page Header Styles */
.page-header {
    position: relative;
    padding: 5rem 0 3rem;
    color: var(--color-white);
    margin-bottom: 0;
}

.page-header-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: var(--color-primary);
    opacity: 0.1;
}

.page-header-content {
    position: relative;
    z-index: 2;
}

.page-title {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: var(--color-white);
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.95rem;
}

.breadcrumb a {
    color: var(--color-white);
    opacity: 0.8;
    transition: opacity var(--transition-base);
}

.breadcrumb a:hover {
    opacity: 1;
}

.breadcrumb .separator {
    opacity: 0.5;
}

.breadcrumb .current {
    opacity: 1;
    font-weight: 600;
}
/* Contact Info Cards */
.contact-info-section {
    padding-top: 3rem;
    margin-top: 0;
    position: relative;
    z-index: 10;
}

.contact-info-card {
    background: var(--color-white);
    padding: 2rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-xl);
    text-align: center;
    height: 100%;
    transition: all var(--transition-base);
}

.contact-info-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.info-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 1.5rem;
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-light));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: var(--color-white);
}

.contact-info-card h3 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.contact-info-card p {
    color: var(--color-gray);
    margin-bottom: 0.5rem;
}

.contact-info-card a {
    color: var(--color-primary);
    font-weight: 600;
}

/* Contact Form */
.contact-form-wrapper {
    background: var(--color-white);
    padding: 2.5rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
}

.contact-form-wrapper h2 {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.form-subtitle {
    color: var(--color-gray);
    margin-bottom: 2rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--color-dark-gray);
}

.required {
    color: var(--color-danger);
}

.form-control {
    width: 100%;
    padding: 0.875rem 1rem;
    border: 2px solid var(--color-light-gray);
    border-radius: var(--border-radius-md);
    font-size: 1rem;
    transition: all var(--transition-base);
}

.form-control:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(0, 63, 135, 0.1);
}

textarea.form-control {
    resize: vertical;
    min-height: 150px;
}

/* Alerts */
.alert {
    padding: 1rem 1.5rem;
    border-radius: var(--border-radius-md);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.alert i {
    font-size: 1.25rem;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Map */
.map-wrapper {
    margin-bottom: 1.5rem;
    box-shadow: var(--shadow-md);
    border-radius: var(--border-radius-lg);
    overflow: hidden;
}

/* Office Hours Card */
.office-hours-card,
.social-connect-card {
    background: var(--color-white);
    padding: 1.5rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-md);
    margin-bottom: 1.5rem;
}

.office-hours-card h3,
.social-connect-card h3 {
    font-size: 1.25rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.office-hours-card h3 i,
.social-connect-card h3 i {
    color: var(--color-primary);
}

.hours-list {
    list-style: none;
    padding: 0;
}

.hours-list li {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--color-light-gray);
}

.hours-list li:last-child {
    border-bottom: none;
}

.day {
    font-weight: 600;
    color: var(--color-dark-gray);
}

.time {
    color: var(--color-gray);
}

/* Social Buttons */
.social-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.social-btn {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1.25rem;
    border-radius: var(--border-radius-md);
    color: var(--color-white);
    font-weight: 600;
    transition: all var(--transition-base);
}

.social-btn:hover {
    transform: translateX(5px);
    box-shadow: var(--shadow-md);
}

.social-btn.facebook { background-color: #3b5998; }
.social-btn.twitter { background-color: #1da1f2; }
.social-btn.instagram { background: linear-gradient(45deg, #f09433 0%,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888 100%); }
.social-btn.youtube { background-color: #ff0000; }

/* Responsive */
@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .contact-info-section {
        margin-top: 0;
    }
}
</style>
