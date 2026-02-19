<?php
/**
 * MARIANCONNECT - Scholarships Page
 * Information about available scholarships and financial aid
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

$pageTitle = 'Scholarships - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <meta name="description" content="Explore scholarship opportunities and financial aid at St. Mary's College of Catbalogan">
    <meta name="keywords" content="SMCC Scholarships, Financial Aid, Grants, Student Support">
    
    <link rel="icon" type="image/x-icon" href="<?php echo asset('images/logo/favicon.ico'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/main.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/responsive.css'); ?>">
    
    <!-- Component CSS Files -->
    <link rel="stylesheet" href="<?php echo asset('css/components/navbar.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/components/footer.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/components/cards.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/components/forms.css'); ?>">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css">
</head>
<body>
    
    <?php include BASE_PATH . '/includes/header.php'; ?>
    
<!-- Page Header -->
       <section class="page-header" style="background: linear-gradient(135deg, rgba(0, 63, 135, 0.7), rgba(0, 40, 85, 0.9)), url('<?php echo asset("images/school header.jpg"); ?>') center/cover no-repeat;">
        <div class="page-header-overlay"></div>
        <div class="container">
            <div class="page-header-content" data-aos="fade-up">
                <h1 class="page-title">Scholarships & Financial Aid</h1>
                <nav class="breadcrumb">
                    <a href="<?php echo url(); ?>">Home</a>
                    <span class="separator">/</span>
                    <span>Admission</span>
                    <span class="separator">/</span>
                    <span class="current">Scholarships</span>
                </nav>
            </div>
        </div>
    </section>
    
<!-- Introduction -->
    <section class="scholarship-intro section-padding">
        <div class="container">
            <div class="intro-content" data-aos="fade-up">
                <h2>Financial Support for Your Education</h2>
                <p class="lead-text">
                    At St. Mary's College of Catbalogan, we believe that financial constraints should not hinder 
                    anyone from pursuing quality education. We offer various scholarship programs and financial 
                    assistance to support deserving students in achieving their academic goals.
                </p>
            </div>
        </div>
    </section>
    
<!-- Scholarship Categories -->
    <section class="scholarship-categories section-padding bg-light">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <h2>Available Scholarships</h2>
                <p>Explore our scholarship programs designed to support students from various backgrounds</p>
            </div>
            
<!-- External Scholarships -->
            <div class="category-block" data-aos="fade-up">
                <div class="category-header">
                    <h3>External Scholarships</h3>
                    <span class="category-badge">Government & Private</span>
                </div>
                
                <div class="scholarship-grid">
                    <div class="scholarship-card">
                        <h4>Armed Forces of the Philippines Scholarship</h4>
                        <p>Scholarship program for dependents of active military personnel.</p>
                        <div class="card-footer">
                            <span class="type-tag">Government</span>
                        </div>
                    </div>
                    
                    <div class="scholarship-card">
                        <h4>CHED - TESARY Education Subsidy (TES)</h4>
                        <p>Government subsidy for tertiary education students meeting academic requirements.</p>
                        <div class="card-footer">
                            <span class="type-tag">Government</span>
                        </div>
                    </div>
                    
                    <div class="scholarship-card">
                        <h4>CHED - TULONG DUNONG Program (TDP-TES)</h4>
                        <p>Financial assistance program for qualified tertiary education students.</p>
                        <div class="card-footer">
                            <span class="type-tag">Government</span>
                        </div>
                    </div>
                    
                    <div class="scholarship-card">
                        <h4>CHED Scholarship Program</h4>
                        <p>Full merit and half merit scholarship for academically qualified students.</p>
                        <div class="card-footer">
                            <span class="type-tag">Government</span>
                        </div>
                    </div>
                    
                    <div class="scholarship-card">
                        <h4>OWWA Scholarship</h4>
                        <p>Financial support for children of overseas Filipino workers.</p>
                        <div class="card-footer">
                            <span class="type-tag">Government</span>
                        </div>
                    </div>
                    
                    <div class="scholarship-card">
                        <h4>Tanging Yaman Foundation</h4>
                        <p>Foundation scholarship supporting education of deserving youth.</p>
                        <div class="card-footer">
                            <span class="type-tag">Private</span>
                        </div>
                    </div>
                </div>
            </div>
            
<!-- Internal Scholarships -->
            <div class="category-block" data-aos="fade-up">
                <div class="category-header">
                    <h3>Internal Scholarships</h3>
                    <span class="category-badge">SMCC Programs</span>
                </div>
                
                <div class="scholarship-grid">
                    
                    <div class="scholarship-card highlight">
                        <h4>Students Assistant Scholarship Program</h4>
                        <p>Work-study program providing financial assistance through campus employment.</p>
                        <div class="card-footer">
                            <span class="type-tag internal">SMCC</span>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </section>
    
<!-- How to Apply -->
    <section class="application-section section-padding">
        <div class="container">
            <div class="application-content" data-aos="fade-up">
                <h2>How to Apply for Scholarships</h2>
                
                <div class="application-steps">
                    <div class="apply-step">
                        <div class="step-num">1</div>
                        <div class="step-info">
                            <h4>Check Eligibility</h4>
                            <p>Review the specific requirements for each scholarship program you're interested in.</p>
                        </div>
                    </div>
                    
                    <div class="apply-step">
                        <div class="step-num">2</div>
                        <div class="step-info">
                            <h4>Prepare Documents</h4>
                            <p>Gather all required documents including academic records, proof of income, and other supporting documents.</p>
                        </div>
                    </div>
                    
                    <div class="apply-step">
                        <div class="step-num">3</div>
                        <div class="step-info">
                            <h4>Submit Application</h4>
                            <p>Visit the Scholarship Coordinator's office to submit your complete application.</p>
                        </div>
                    </div>
                    
                    <div class="apply-step">
                        <div class="step-num">4</div>
                        <div class="step-info">
                            <h4>Wait for Evaluation</h4>
                            <p>Your application will be reviewed and evaluated based on the scholarship criteria.</p>
                        </div>
                    </div>
                </div>
                
                <div class="contact-box">
                    <h4>For Inquiries</h4>
                    <p><strong>Scholarship Coordinator</strong></p>
                    <p><strong>Email:</strong> info@smcc.edu.ph</p>
                    <p><strong>Office Hours:</strong> Monday - Friday, 8:00 AM - 5:00 PM</p>
                </div>
            </div>
        </div>
    </section>
    

    
    <?php include BASE_PATH . '/includes/footer.php'; ?>
    
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 800, once: true });
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
    align-items: center;
    gap: 0.5rem;
    font-size: 0.95rem;
}

.breadcrumb a {
    color: white;
    opacity: 0.8;
    transition: opacity 0.3s;
    text-decoration: none;
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

/* Introduction */
.scholarship-intro {
    padding: 3rem 0 2rem;
}

.intro-content {
    max-width: 900px;
    margin: 0 auto;
    text-align: center;
}

.intro-content h2 {
    font-size: 2.5rem;
    color: #003f87;
    margin-bottom: 1.5rem;
}

.lead-text {
    font-size: 1.1rem;
    line-height: 1.8;
    color: #495057;
}

/* Scholarship Categories */
.scholarship-categories {
    padding: 4rem 0;
}

.section-header {
    text-align: center;
    margin-bottom: 3rem;
}

.section-header h2 {
    font-size: 2.5rem;
    color: #003f87;
    margin-bottom: 0.75rem;
}

.section-header p {
    font-size: 1.1rem;
    color: #6c757d;
}

.category-block {
    margin-bottom: 4rem;
}

.category-block:last-child {
    margin-bottom: 0;
}

.category-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 3px solid #003f87;
}

.category-header h3 {
    font-size: 2rem;
    color: #003f87;
    margin: 0;
}

.category-badge {
    display: inline-block;
    padding: 0.5rem 1.25rem;
    background: #003f87;
    color: white;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
}

.scholarship-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.scholarship-card {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    transition: all 0.3s;
    border: 2px solid transparent;
}

.scholarship-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    border-color: #003f87;
}

.scholarship-card.highlight {
    background: linear-gradient(135deg, #f0f7ff, #fff);
    border-color: #003f87;
}

.scholarship-card h4 {
    font-size: 1.25rem;
    color: #003f87;
    margin-bottom: 0.75rem;
    line-height: 1.4;
}

.scholarship-card p {
    color: #495057;
    line-height: 1.6;
    margin-bottom: 1rem;
    flex: 1;
}

.card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 0.75rem;
    border-top: 1px solid #e9ecef;
}

.type-tag {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    background: #e9ecef;
    color: #495057;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
}

.type-tag.internal {
    background: #d4edda;
    color: #155724;
}

/* Application Section */
.application-section {
    padding: 4rem 0;
}

.application-content {
    max-width: 900px;
    margin: 0 auto;
}

.application-content h2 {
    font-size: 2.5rem;
    color: #003f87;
    margin-bottom: 2rem;
    text-align: center;
}

.application-steps {
    margin-bottom: 3rem;
}

.apply-step {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}

.step-num {
    flex-shrink: 0;
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #003f87, #0056b3);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.25rem;
}

.step-info h4 {
    font-size: 1.25rem;
    color: #003f87;
    margin-bottom: 0.5rem;
}

.step-info p {
    color: #495057;
    line-height: 1.6;
    margin: 0;
}

.contact-box {
    background: #f8f9fa;
    padding: 2rem;
    border-radius: 12px;
    border-left: 4px solid #003f87;
}

.contact-box h4 {
    font-size: 1.5rem;
    color: #003f87;
    margin-bottom: 1rem;
}

.contact-box p {
    margin-bottom: 0.5rem;
    color: #495057;
}

.contact-box p:last-child {
    margin-bottom: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .page-title {
        font-size: 2rem;
    }
    
    .intro-content h2,
    .section-header h2,
    .application-content h2 {
        font-size: 2rem;
    }
    
    .category-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .category-header h3 {
        font-size: 1.5rem;
    }
    
    .scholarship-grid {
        grid-template-columns: 1fr;
    }
    
    .apply-step {
        flex-direction: column;
    }
}
</style>
