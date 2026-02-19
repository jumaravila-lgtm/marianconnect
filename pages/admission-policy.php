<?php
/**
 * MARIANCONNECT - Admission Policy Page
 * Simple, clean design like SPSPS
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/settings.php';
require_once BASE_PATH . '/config/security.php';
require_once BASE_PATH . '/includes/functions.php';

try {
    trackVisitor();
} catch (Exception $e) {
    error_log("Visitor tracking error: " . $e->getMessage());
}

$pageTitle = 'Admission Policy - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <meta name="description" content="Learn about admission requirements and policies at St. Mary's College of Catbalogan">
    <meta name="keywords" content="SMCC Admission, Requirements, Policy, Enrollment">
    
    <link rel="icon" type="image/x-icon" href="<?php echo asset('images/logo/favicon.ico'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/main.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/responsive.css'); ?>">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css">
</head>
<body>
    
    <?php include BASE_PATH . '/includes/header.php'; ?>
    
    <!-- Page Header -->
       <section class="page-header" style="background: linear-gradient(135deg, rgba(0, 63, 135, 0.7), rgba(0, 40, 85, 0.9)), url('<?php echo asset("images/school header.jpg"); ?>') center/cover no-repeat;">
        <div class="container">
            <div class="page-header-content" data-aos="fade-up">
                <h1 class="page-title">Admission Policy</h1>
                <nav class="breadcrumb">
                    <a href="<?php echo url(); ?>">Home</a>
                    <span class="separator">/</span>
                    <a href="#">Admission</a>
                    <span class="separator">/</span>
                    <span class="current">Admission Policy</span>
                </nav>
            </div>
        </div>
    </section>
    
    <!-- Main Content -->
    <section class="content-section section-padding">
        <div class="container">
            <div class="content-wrapper" data-aos="fade-up">
                
                <!-- Introduction -->
                <div class="intro-text">
                    <p>
                        Lorem ipsum dolor sit amet consectetur adipisicing elit. In sapiente deserunt ab quidem repudiandae omnis officia repellendus numquam. Alias voluptas id labore ex possimus tempore repellendus vel quasi reiciendis, molestiae nemo quia magni enim similique dolore rem, saepe ab. At, enim. Repudiandae eius suscipit possimus vitae saepe consectetur nisi velit magni adipisci est aspernatur enim veniam beatae, quam temporibus harum facere non! Ipsam dolor aperiam autem consectetur amet sed numquam. Nisi, labore corporis? Quis reiciendis optio quos atque dicta unde, nulla, consequuntur, ducimus itaque possimus deleniti? Totam iure, sit ut autem consectetur libero incidunt consequatur cupiditate facere possimus perspiciatis itaque et. Nam ratione animi porro repellat architecto, totam ipsam minus laboriosam distinctio. Consequuntur rerum consequatur asperiores quod doloremque impedit enim eius illum a?
                    </p>
                </div>
                
                <!-- Admission Requirements -->
                <div class="section-block">
                    <h2 class="section-heading">Admission Requirements</h2>
                    <!-- Grade School -->
                    <div class="requirement-section">
                        <h3 class="requirement-title">Grade School</h3>
                        <ol class="requirement-list">

                        </ol>
                    </div>
                     <!-- Junior School -->
                    <div class="requirement-section">
                        <h3 class="requirement-title">Junior High School</h3>
                        <ol class="requirement-list">

                        </ol>
                    </div>
                    <!-- Senior High School -->
                    <div class="requirement-section">
                        <h3 class="requirement-title">Senior High School</h3>
                        <ol class="requirement-list">

                        </ol>
                    </div>
                    
                    <!-- Undergraduate -->
                    <div class="requirement-section">
                        <h3 class="requirement-title">Undergraduate</h3>
                        
                        <h4 class="subsection-title">A. Freshmen</h4>
                        <ol class="requirement-list">

                        </ol>
                        
                        <h4 class="subsection-title">B. Transferees</h4>
                        <ol class="requirement-list">

                        </ol>
                        
                        <h4 class="subsection-title">C. Returnees</h4>
                        <ol class="requirement-list">

                        </ol>
                    </div>
                    
                <!-- Admission Process -->
                <div class="section-block">
                    <h2 class="section-heading">Admission Process</h2>
                    <ol class="process-list">
                        <li>After payment of the entrance fee, the student shall submit scanned copies of the corresponding admission credentials, together with proof of payment to the Registrar's Office.</li>
                        <li>Original copies of the required admission credentials shall be sent by the student to the Office of the School Registrar, SMCC.</li>
                        <li>Original copies shall be sent to SMCC via courier, addressed to: <strong>The School Registrar, St. Mary's College of Catbalogan, Catbalogan City, Samar</strong>.</li>
                        <li>A student with lacking major admission credentials (i.e., Form 138-A, Certificate of Good Moral Character, Honorable Dismissal) are enlisted in the probationary enrollment maintained by the Registrar's office.</li>
                        <li>An e-copy of Deed of Undertaking will be sent to students for completion and return to the Registrar's office.</li>
                        <li>Queries related to enrollment and admission can be addressed through:
                            <ul class="contact-list">
                                <li>Email: <a href="mailto:registrar@smcc.edu.ph">registrar@smcc.edu.ph</a></li>
                                <li>Phone: [Contact Number]</li>
                                <li>Facebook: [FB Page Link]</li>
                            </ul>
                        </li>
                    </ol>
                </div>
                
            </div>
        </div>
    </section>
    
    <?php include BASE_PATH . '/includes/footer.php'; ?>
    
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ 
            duration: 800, 
            once: true
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
    margin-bottom: 3rem;
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
    justify-content: flex-start;
    gap: 0.5rem;
    font-size: 0.95rem;
}

.breadcrumb a {
    color: rgba(255, 255, 255, 0.9);
    text-decoration: none;
}

.breadcrumb a:hover {
    color: white;
}

.breadcrumb .separator {
    color: rgba(255, 255, 255, 0.6);
}

/* Content Section */
.content-section {
    padding: 4rem 0;
    background: #f8f9fa;
}

.content-wrapper {
    max-width: 900px;
    margin: 0 auto;
    background: white;
    padding: 3rem;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

/* Introduction */
.intro-text {
    margin-bottom: 3rem;
}

.intro-text p {
    font-size: 1.05rem;
    line-height: 1.8;
    color: #495057;
    margin-bottom: 1.25rem;
    text-align: justify;
}

.intro-text p:last-child {
    margin-bottom: 0;
}

/* Section Blocks */
.section-block {
    margin-bottom: 3rem;
}

.section-block:last-child {
    margin-bottom: 0;
}

.section-heading {
    font-size: 1.75rem;
    font-weight: 700;
    color: #003f87;
    margin-bottom: 2rem;
    padding-bottom: 0.75rem;
    border-bottom: 3px solid #003f87;
}

/* Requirement Sections */
.requirement-section {
    margin-bottom: 2.5rem;
}

.requirement-section:last-child {
    margin-bottom: 0;
}

.requirement-title {
    font-size: 1.4rem;
    font-weight: 600;
    color: #212529;
    margin-bottom: 1rem;
}

.subsection-title {
    font-size: 1.15rem;
    font-weight: 600;
    color: #495057;
    margin-top: 1.5rem;
    margin-bottom: 1rem;
}

/* Lists */
.requirement-list,
.process-list {
    margin: 0;
    padding-left: 2rem;
}

.requirement-list li,
.process-list li {
    font-size: 1rem;
    line-height: 1.8;
    color: #495057;
    margin-bottom: 0.75rem;
    padding-left: 0.5rem;
}

.requirement-list li:last-child,
.process-list li:last-child {
    margin-bottom: 0;
}

/* Contact List */
.contact-list {
    list-style: none;
    margin-top: 0.75rem;
    padding-left: 1.5rem;
}

.contact-list li {
    font-size: 0.95rem;
    line-height: 1.8;
    color: #495057;
    margin-bottom: 0.5rem;
}

.contact-list a {
    color: #003f87;
    text-decoration: none;
    font-weight: 500;
}

.contact-list a:hover {
    text-decoration: underline;
}

/* Responsive */
@media (max-width: 768px) {
    .page-title {
        font-size: 2rem;
    }
    
    .content-wrapper {
        padding: 2rem 1.5rem;
    }
    
    .intro-text p {
        font-size: 1rem;
        text-align: left;
    }
    
    .section-heading {
        font-size: 1.5rem;
    }
    
    .requirement-title {
        font-size: 1.25rem;
    }
    
    .subsection-title {
        font-size: 1.1rem;
    }
    
    .requirement-list,
    .process-list {
        padding-left: 1.5rem;
    }
}
</style>
