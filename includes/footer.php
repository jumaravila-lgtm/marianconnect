<?php
/**
 * MARIANCONNECT - Main Footer
 */

if (!defined('SITE_NAME')) {
    require_once __DIR__ . '/../config/settings.php';
}
?>

<footer class="main-footer">
<!-- Footer Top -->
    <div class="footer-top section-padding">
        <div class="container">
            <div class="row g-4">
                <!-- About Column -->
                <div class="col-md-5">
                    <div class="footer-widget">
                        <div class="footer-logo">
                            <img src="<?php echo asset('images/logo/logo-white.png'); ?>" alt="<?php echo escapeHtml(SITE_NAME); ?>">
                        </div>
                        <h3 class="footer-title"><?php echo escapeHtml(SITE_NAME); ?></h3>
                        <p class="footer-text">
                            <?php echo escapeHtml(getSiteSetting('footer_about', 'A Catholic educational institution committed to excellence in education, service, and faith formation.')); ?>
                        </p>
                        <div class="footer-social">
                            <?php
                            $facebook = getSiteSetting('facebook_url', FACEBOOK_URL);
                            $twitter = getSiteSetting('twitter_url', TWITTER_URL);
                            $instagram = getSiteSetting('instagram_url', INSTAGRAM_URL);
                            $youtube = getSiteSetting('youtube_url', YOUTUBE_URL);
                            
                            if (!empty($facebook)): ?>
                                <a href="<?php echo escapeUrl($facebook); ?>" target="_blank" rel="noopener" title="Facebook">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                            <?php endif;
                            
                            if (!empty($twitter)): ?>
                                <a href="<?php echo escapeUrl($twitter); ?>" target="_blank" rel="noopener" title="Twitter">
                                    <i class="fab fa-twitter"></i>
                                </a>
                            <?php endif;
                            
                            if (!empty($instagram)): ?>
                                <a href="<?php echo escapeUrl($instagram); ?>" target="_blank" rel="noopener" title="Instagram">
                                    <i class="fab fa-instagram"></i>
                                </a>
                            <?php endif;
                            
                            if (!empty($youtube)): ?>
                                <a href="<?php echo escapeUrl($youtube); ?>" target="_blank" rel="noopener" title="YouTube">
                                    <i class="fab fa-youtube"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Links Column -->
                <div class="col-md-3">
                    <div class="footer-widget">
                        <h3 class="footer-title">Quick Links</h3>
                        <ul class="footer-links">
                            <li><a href="<?php echo url(); ?>">Home</a></li>
                            <li><a href="<?php echo url('pages/about.php'); ?>">About Us</a></li>
                            <li><a href="<?php echo url('pages/programs.php'); ?>">Academic Programs</a></li>
                            <li><a href="<?php echo url('pages/news.php'); ?>">News & Updates</a></li>
                            <li><a href="<?php echo url('pages/events.php'); ?>">Events</a></li>
                            <li><a href="<?php echo url('pages/gallery.php'); ?>">Gallery</a></li>
                            <li><a href="<?php echo url('pages/contact.php'); ?>">Contact Us</a></li>
                            <li><a href="<?php echo url('admin/login.php'); ?>">Admin Login</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Contact Info Column -->
                <div class="col-md-4">
                    <div class="footer-widget">
                        <h3 class="footer-title">Contact Information</h3>
                        <ul class="footer-contact">
                            <li>
                                <i class="fas fa-map-marker-alt"></i>
                                <div>
                                    <strong>Address:</strong>
                                    <p><?php echo escapeHtml(getSiteSetting('contact_address', 'Catbalogan City, Samar, Philippines')); ?></p>
                                </div>
                            </li>
                            <li>
                                <i class="fas fa-phone"></i>
                                <div>
                                    <strong>Phone:</strong>
                                    <p><?php echo escapeHtml(getSiteSetting('contact_phone', '(055) 251-2345')); ?></p>
                                </div>
                            </li>
                            <li>
                                <i class="fas fa-envelope"></i>
                                <div>
                                    <strong>Email:</strong>
                                    <p><a href="mailto:<?php echo getSiteSetting('contact_email', 'info@smcc.edu.ph'); ?>">
                                        <?php echo escapeHtml(getSiteSetting('contact_email', 'info@smcc.edu.ph')); ?>
                                    </a></p>
                                </div>
                            </li>
                            <li>
                                <i class="fas fa-clock"></i>
                                <div>
                                    <strong>Office Hours:</strong>
                                    <p>Monday - Friday: 8:00 AM - 5:00 PM</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Bottom -->
    <div class="footer-bottom">
        <div class="container">
            <div class="footer-bottom-content">
                <div class="copyright">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo escapeHtml(SITE_NAME); ?>. All rights reserved.</p>
                </div>
                <div class="footer-links-bottom">
                    <a href="<?php echo url('pages/privacy-policy.php'); ?>">Privacy Policy</a>
                    <span>|</span>
                    <a href="<?php echo url('pages/terms-of-use.php'); ?>">Terms of Use</a>
                    <span>|</span>
                    <a href="<?php echo url('sitemap.xml'); ?>">Sitemap</a>
                </div>
                <div class="developer-credit">
                    <p>Developed by <a href="#" target="_blank">MARIANCONNECT Team</a></p>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Scroll to Top Button -->
<button id="scrollToTop" class="scroll-to-top" title="Back to top">
    <i class="fas fa-arrow-up"></i>
</button>

<style>
/* Footer Styles */
.main-footer {
    position: relative;
    background: linear-gradient(135deg, var(--color-primary-dark), var(--color-primary));
    color: var(--color-white);
}

.main-footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: url('<?php echo asset('images/school.png'); ?>');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    opacity: 0.15;
    z-index: 0;
}

.footer-top,
.footer-bottom {
    position: relative;
    z-index: 1;
}

.footer-widget {
    margin-bottom: 2rem;
}

.footer-logo img {
    height: 60px;
    width: auto;
    margin-bottom: 1rem;
}

.footer-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    color: var(--color-white);
    position: relative;
    padding-bottom: 0.75rem;
}

.footer-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 50px;
    height: 3px;
    background-color: var(--color-secondary);
    border-radius: 2px;
}

.footer-text {
    line-height: 1.8;
    margin-bottom: 1.5rem;
    opacity: 0.9;
}

/* Footer Social */
.footer-social {
    display: flex;
    gap: 1rem;
}

.footer-social a {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    color: var(--color-white);
    font-size: 1.25rem;
    transition: all var(--transition-base);
}

.footer-social a:hover {
    background-color: var(--color-secondary);
    transform: translateY(-3px);
}

/* Footer Links */
.footer-links {
    list-style: none;
    padding: 0;
}

.footer-links li {
    margin-bottom: 0.75rem;
}

.footer-links a {
    color: var(--color-white);
    opacity: 0.9;
    transition: all var(--transition-base);
    display: inline-block;
}

.footer-links a:hover {
    opacity: 1;
    padding-left: 0.5rem;
    color: var(--color-secondary);
}

/* Footer Contact */
.footer-contact {
    list-style: none;
    padding: 0;
}

.footer-contact li {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.footer-contact i {
    font-size: 1.5rem;
    color: var(--color-secondary);
    min-width: 30px;
}

.footer-contact strong {
    display: block;
    margin-bottom: 0.25rem;
}

.footer-contact p {
    margin: 0;
    opacity: 0.9;
    line-height: 1.6;
}

.footer-contact a {
    color: var(--color-white);
    opacity: 0.9;
    transition: opacity var(--transition-base);
}

.footer-contact a:hover {
    opacity: 1;
    color: var(--color-secondary);
}

/* Footer Bottom */
.footer-bottom {
    background-color: rgba(0, 0, 0, 0.2);
    padding: 1.5rem 0;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.footer-bottom-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.copyright p {
    margin: 0;
    opacity: 0.9;
}

.footer-links-bottom {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.footer-links-bottom a {
    color: var(--color-white);
    opacity: 0.9;
    transition: opacity var(--transition-base);
}

.footer-links-bottom a:hover {
    opacity: 1;
    color: var(--color-secondary);
}

.footer-links-bottom span {
    opacity: 0.5;
}

.developer-credit p {
    margin: 0;
    opacity: 0.8;
}

.developer-credit a {
    color: var(--color-secondary);
    font-weight: 600;
}

/* Scroll to Top Button */
.scroll-to-top {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-light));
    color: var(--color-white);
    border: none;
    border-radius: 50%;
    font-size: 1.5rem;
    cursor: pointer;
    opacity: 0;
    visibility: hidden;
    transition: all var(--transition-base);
    z-index: 999;
    box-shadow: var(--shadow-lg);
}

.scroll-to-top.visible {
    opacity: 1;
    visibility: visible;
}

.scroll-to-top:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-xl);
}

/* Responsive Design */
@media (max-width: 768px) {
    .footer-bottom-content {
        flex-direction: column;
        text-align: center;
    }
    
    .footer-links-bottom {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .scroll-to-top {
        bottom: 20px;
        right: 20px;
        width: 45px;
        height: 45px;
    }
}
</style>

<!-- Swiper.js - For Sliders/Carousels -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<!-- AOS - Animate On Scroll -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

<!-- Custom Scripts -->
<script src="<?php echo url('assets/js/main.js'); ?>"></script>
<script src="<?php echo url('assets/js/navigation.js'); ?>"></script>
<script src="<?php echo url('assets/js/animations.js'); ?>"></script>
<script src="<?php echo url('assets/js/form-validation.js'); ?>"></script>
<script>
// Scroll to Top Button
const scrollToTopBtn = document.getElementById('scrollToTop');

window.addEventListener('scroll', function() {
    if (window.pageYOffset > 300) {
        scrollToTopBtn.classList.add('visible');
    } else {
        scrollToTopBtn.classList.remove('visible');
    }
});

scrollToTopBtn.addEventListener('click', function() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});
</script>

<?php
// Google Analytics (if configured)
$gaId = getSiteSetting('google_analytics_id', GOOGLE_ANALYTICS_ID);
if (!empty($gaId)):
?>
<!-- Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $gaId; ?>"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '<?php echo $gaId; ?>');
</script>
<?php endif; ?>
