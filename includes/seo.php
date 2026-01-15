<?php
/**
 * MARIANCONNECT - SEO Meta Tags Generator
 * Handles all SEO meta tags, Open Graph, Twitter Cards, and Schema.org markup
 */

// Default site information
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'St. Mary\'s College of Catbalogan');
}
if (!defined('SITE_TAGLINE')) {
    define('SITE_TAGLINE', 'Excellence in Catholic Education');
}

/**
 * Generate SEO meta tags
 * 
 * @param array $params SEO parameters
 * - title: Page title
 * - description: Meta description
 * - keywords: Meta keywords
 * - image: Featured image URL
 * - url: Canonical URL
 * - type: Page type (website, article, etc.)
 * - author: Content author
 * - published_time: Publication date
 * - modified_time: Last modified date
 */
function generateSEO($params = []) {
    // Set defaults
    $defaults = [
        'title' => SITE_NAME . ' - ' . SITE_TAGLINE,
        'description' => 'St. Mary\'s College of Catbalogan is a Catholic educational institution committed to excellence in education, service, and faith formation.',
        'keywords' => 'SMCC, St. Mary\'s College, Catbalogan, Catholic School, Education, Samar, Philippines',
        'image' => getBaseUrl() . 'assets/images/logo/logo-main.png',
        'url' => getCurrentUrl(),
        'type' => 'website',
        'author' => SITE_NAME,
        'published_time' => null,
        'modified_time' => null,
        'locale' => 'en_US',
        'site_name' => SITE_NAME
    ];
    
    $seo = array_merge($defaults, $params);
    
    // Clean and escape values
    $seo['title'] = escapeHtml($seo['title']);
    $seo['description'] = escapeHtml(truncateText($seo['description'], 160));
    $seo['keywords'] = escapeHtml($seo['keywords']);
    $seo['image'] = escapeUrl($seo['image']);
    $seo['url'] = escapeUrl($seo['url']);
    
    return $seo;
}

/**
 * Output SEO meta tags
 */
function outputSEO($params = []) {
    $seo = generateSEO($params);
    ?>
    
    <!-- Primary Meta Tags -->
    <title><?php echo $seo['title']; ?></title>
    <meta name="title" content="<?php echo $seo['title']; ?>">
    <meta name="description" content="<?php echo $seo['description']; ?>">
    <meta name="keywords" content="<?php echo $seo['keywords']; ?>">
    <meta name="author" content="<?php echo $seo['author']; ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?php echo $seo['url']; ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="<?php echo $seo['type']; ?>">
    <meta property="og:url" content="<?php echo $seo['url']; ?>">
    <meta property="og:title" content="<?php echo $seo['title']; ?>">
    <meta property="og:description" content="<?php echo $seo['description']; ?>">
    <meta property="og:image" content="<?php echo $seo['image']; ?>">
    <meta property="og:site_name" content="<?php echo $seo['site_name']; ?>">
    <meta property="og:locale" content="<?php echo $seo['locale']; ?>">
    <?php if ($seo['published_time']): ?>
    <meta property="article:published_time" content="<?php echo $seo['published_time']; ?>">
    <?php endif; ?>
    <?php if ($seo['modified_time']): ?>
    <meta property="article:modified_time" content="<?php echo $seo['modified_time']; ?>">
    <?php endif; ?>
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?php echo $seo['url']; ?>">
    <meta name="twitter:title" content="<?php echo $seo['title']; ?>">
    <meta name="twitter:description" content="<?php echo $seo['description']; ?>">
    <meta name="twitter:image" content="<?php echo $seo['image']; ?>">
    
    <?php
}

/**
 * Get current page URL
 */
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    return $protocol . '://' . $host . $uri;
}

/**
 * Get base URL
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = dirname($_SERVER['SCRIPT_NAME']);
    $baseUrl = $protocol . '://' . $host . $script;
    return rtrim($baseUrl, '/') . '/';
}

/**
 * Truncate text to specified length
 */
function truncateText($text, $length = 160) {
    $text = strip_tags($text);
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

/**
 * Escape HTML output
 */
function escapeHtml($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Escape URL
 */
function escapeUrl($url) {
    return filter_var($url, FILTER_SANITIZE_URL);
}

/**
 * Generate JSON-LD Schema.org markup
 */
function outputSchemaOrg($type = 'Organization', $data = []) {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => $type
    ];
    
    if ($type === 'Organization') {
        $schema = array_merge($schema, [
            'name' => SITE_NAME,
            'url' => getBaseUrl(),
            'logo' => getBaseUrl() . 'assets/images/logo/logo-main.png',
            'description' => SITE_TAGLINE,
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => 'Catbalogan City',
                'addressRegion' => 'Samar',
                'addressCountry' => 'PH'
            ],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'telephone' => getSiteSetting('contact_phone', '(055) 251-2345'),
                'contactType' => 'Customer Service',
                'email' => getSiteSetting('contact_email', 'info@smcc.edu.ph')
            ],
            'sameAs' => [
                getSiteSetting('facebook_url', ''),
                getSiteSetting('twitter_url', ''),
                getSiteSetting('instagram_url', ''),
                getSiteSetting('youtube_url', '')
            ]
        ]);
    } elseif ($type === 'Article') {
        $schema = array_merge($schema, $data);
    } elseif ($type === 'Event') {
        $schema = array_merge($schema, $data);
    }
    
    // Remove empty values
    $schema = array_filter($schema, function($value) {
        return !empty($value);
    });
    
    echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Output breadcrumb JSON-LD
 */
function outputBreadcrumbs($items = []) {
    if (empty($items)) {
        return;
    }
    
    $listItems = [];
    $position = 1;
    
    foreach ($items as $item) {
        $listItems[] = [
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => $item['name'],
            'item' => $item['url']
        ];
    }
    
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $listItems
    ];
    
    echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate Article Schema for News
 */
function generateNewsSchema($news) {
    return [
        'headline' => $news['title'],
        'image' => !empty($news['featured_image']) ? getBaseUrl() . ltrim($news['featured_image'], '/') : getBaseUrl() . 'assets/images/logo/logo-main.png',
        'datePublished' => date('c', strtotime($news['published_date'])),
        'dateModified' => date('c', strtotime($news['updated_at'])),
        'author' => [
            '@type' => 'Person',
            'name' => $news['author_name'] ?? SITE_NAME
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => SITE_NAME,
            'logo' => [
                '@type' => 'ImageObject',
                'url' => getBaseUrl() . 'assets/images/logo/logo-main.png'
            ]
        ],
        'description' => $news['excerpt'] ?? truncateText($news['content'], 160),
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id' => getCurrentUrl()
        ]
    ];
}

/**
 * Generate Event Schema
 */
function generateEventSchema($event) {
    $schema = [
        'name' => $event['title'],
        'startDate' => date('c', strtotime($event['event_date'] . ' ' . ($event['event_time'] ?? '00:00:00'))),
        'location' => [
            '@type' => 'Place',
            'name' => $event['location'],
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => 'Catbalogan City',
                'addressRegion' => 'Samar',
                'addressCountry' => 'PH'
            ]
        ],
        'description' => $event['description'],
        'image' => !empty($event['featured_image']) ? getBaseUrl() . ltrim($event['featured_image'], '/') : getBaseUrl() . 'assets/images/logo/logo-main.png',
        'organizer' => [
            '@type' => 'Organization',
            'name' => $event['organizer'] ?? SITE_NAME,
            'url' => getBaseUrl()
        ]
    ];
    
    if (!empty($event['end_date'])) {
        $schema['endDate'] = date('c', strtotime($event['end_date'] . ' ' . ($event['event_time'] ?? '23:59:59')));
    }
    
    if (!empty($event['max_participants'])) {
        $schema['maximumAttendeeCapacity'] = $event['max_participants'];
    }
    
    return $schema;
}
?>
