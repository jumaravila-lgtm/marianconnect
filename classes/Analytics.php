<?php
/**
 * MARIANCONNECT - Analytics Class
 * Handles visitor tracking and analytics operations
 */

class Analytics {
    
    private $db;
    private $table = 'visitor_analytics';
    
    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Track visitor
     * 
     * @param array $data Visitor data (optional, will auto-detect if not provided)
     * @return bool
     */
    public function trackVisit($data = []) {
        // Auto-detect visitor data if not provided
        $visitData = [
            'ip_address' => $data['ip_address'] ?? $_SERVER['REMOTE_ADDR'],
            'user_agent' => $data['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'],
            'page_url' => $data['page_url'] ?? $_SERVER['REQUEST_URI'],
            'referrer' => $data['referrer'] ?? ($_SERVER['HTTP_REFERER'] ?? ''),
            'device_type' => $data['device_type'] ?? $this->detectDeviceType(),
            'browser' => $data['browser'] ?? $this->detectBrowser(),
            'os' => $data['os'] ?? $this->detectOS(),
            'session_id' => $data['session_id'] ?? session_id()
        ];
        
        $sql = "
            INSERT INTO {$this->table} (
                ip_address, user_agent, page_url, referrer, device_type,
                browser, os, session_id
            ) VALUES (
                :ip_address, :user_agent, :page_url, :referrer, :device_type,
                :browser, :os, :session_id
            )
        ";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($visitData);
        } catch (PDOException $e) {
            error_log("Visit tracking error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get total visits
     * 
     * @param array $options Date range and filters
     * @return int
     */
    public function getTotalVisits($options = []) {
        $startDate = $options['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $options['end_date'] ?? date('Y-m-d');
        
        $sql = "
            SELECT COUNT(*) 
            FROM {$this->table}
            WHERE DATE(visited_at) BETWEEN :start_date AND :end_date
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        
        return $stmt->fetchColumn();
    }
    
    /**
     * Get unique visitors
     * 
     * @param array $options Date range and filters
     * @return int
     */
    public function getUniqueVisitors($options = []) {
        $startDate = $options['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $options['end_date'] ?? date('Y-m-d');
        
        $sql = "
            SELECT COUNT(DISTINCT ip_address)
            FROM {$this->table}
            WHERE DATE(visited_at) BETWEEN :start_date AND :end_date
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        
        return $stmt->fetchColumn();
    }
    
    /**
     * Get visits by date (for charts)
     * 
     * @param int $days Number of days
     * @return array
     */
    public function getVisitsByDate($days = 30) {
        $sql = "
            SELECT DATE(visited_at) as date, COUNT(*) as visits
            FROM {$this->table}
            WHERE visited_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            GROUP BY DATE(visited_at)
            ORDER BY date ASC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':days' => $days]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get device breakdown
     * 
     * @param array $options Date range
     * @return array
     */
    public function getDeviceBreakdown($options = []) {
        $startDate = $options['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $options['end_date'] ?? date('Y-m-d');
        
        $sql = "
            SELECT device_type, COUNT(*) as count
            FROM {$this->table}
            WHERE DATE(visited_at) BETWEEN :start_date AND :end_date
            GROUP BY device_type
            ORDER BY count DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get top pages
     * 
     * @param int $limit Number of pages
     * @param array $options Date range
     * @return array
     */
    public function getTopPages($limit = 10, $options = []) {
        $startDate = $options['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $options['end_date'] ?? date('Y-m-d');
        
        $sql = "
            SELECT page_url, COUNT(*) as views
            FROM {$this->table}
            WHERE DATE(visited_at) BETWEEN :start_date AND :end_date
            GROUP BY page_url
            ORDER BY views DESC
            LIMIT :limit
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':start_date', $startDate);
        $stmt->bindValue(':end_date', $endDate);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get top referrers
     * 
     * @param int $limit Number of referrers
     * @param array $options Date range
     * @return array
     */
    public function getTopReferrers($limit = 10, $options = []) {
        $startDate = $options['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $options['end_date'] ?? date('Y-m-d');
        
        $sql = "
            SELECT referrer, COUNT(*) as count
            FROM {$this->table}
            WHERE DATE(visited_at) BETWEEN :start_date AND :end_date
            AND referrer != ''
            GROUP BY referrer
            ORDER BY count DESC
            LIMIT :limit
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':start_date', $startDate);
        $stmt->bindValue(':end_date', $endDate);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get browser statistics
     * 
     * @param array $options Date range
     * @return array
     */
    public function getBrowserStats($options = []) {
        $startDate = $options['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $options['end_date'] ?? date('Y-m-d');
        
        $sql = "
            SELECT browser, COUNT(*) as count
            FROM {$this->table}
            WHERE DATE(visited_at) BETWEEN :start_date AND :end_date
            AND browser IS NOT NULL
            GROUP BY browser
            ORDER BY count DESC
            LIMIT 10
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get OS statistics
     * 
     * @param array $options Date range
     * @return array
     */
    public function getOSStats($options = []) {
        $startDate = $options['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $options['end_date'] ?? date('Y-m-d');
        
        $sql = "
            SELECT os, COUNT(*) as count
            FROM {$this->table}
            WHERE DATE(visited_at) BETWEEN :start_date AND :end_date
            AND os IS NOT NULL
            GROUP BY os
            ORDER BY count DESC
            LIMIT 10
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get hourly traffic pattern
     * 
     * @param array $options Date range
     * @return array
     */
    public function getHourlyTraffic($options = []) {
        $startDate = $options['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
        $endDate = $options['end_date'] ?? date('Y-m-d');
        
        $sql = "
            SELECT HOUR(visited_at) as hour, COUNT(*) as visits
            FROM {$this->table}
            WHERE DATE(visited_at) BETWEEN :start_date AND :end_date
            GROUP BY HOUR(visited_at)
            ORDER BY hour ASC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get comprehensive dashboard stats
     * 
     * @param array $options Date range
     * @return array
     */
    public function getDashboardStats($options = []) {
        $startDate = $options['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $options['end_date'] ?? date('Y-m-d');
        
        return [
            'total_visits' => $this->getTotalVisits($options),
            'unique_visitors' => $this->getUniqueVisitors($options),
            'page_views' => $this->getTotalVisits($options),
            'visits_by_date' => $this->getVisitsByDate(30),
            'device_breakdown' => $this->getDeviceBreakdown($options),
            'top_pages' => $this->getTopPages(10, $options),
            'top_referrers' => $this->getTopReferrers(10, $options)
        ];
    }
    
    /**
     * Detect device type from user agent
     * 
     * @param string $userAgent User agent string
     * @return string
     */
    private function detectDeviceType($userAgent = null) {
        $userAgent = $userAgent ?? $_SERVER['HTTP_USER_AGENT'];
        
        if (preg_match('/mobile/i', $userAgent)) {
            return 'mobile';
        } elseif (preg_match('/tablet|ipad/i', $userAgent)) {
            return 'tablet';
        } else {
            return 'desktop';
        }
    }
    
    /**
     * Detect browser from user agent
     * 
     * @param string $userAgent User agent string
     * @return string|null
     */
    private function detectBrowser($userAgent = null) {
        $userAgent = $userAgent ?? $_SERVER['HTTP_USER_AGENT'];
        
        if (preg_match('/MSIE|Trident/i', $userAgent)) {
            return 'Internet Explorer';
        } elseif (preg_match('/Edge/i', $userAgent)) {
            return 'Edge';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            return 'Firefox';
        } elseif (preg_match('/Chrome/i', $userAgent)) {
            return 'Chrome';
        } elseif (preg_match('/Safari/i', $userAgent)) {
            return 'Safari';
        } elseif (preg_match('/Opera|OPR/i', $userAgent)) {
            return 'Opera';
        }
        
        return null;
    }
    
    /**
     * Detect operating system from user agent
     * 
     * @param string $userAgent User agent string
     * @return string|null
     */
    private function detectOS($userAgent = null) {
        $userAgent = $userAgent ?? $_SERVER['HTTP_USER_AGENT'];
        
        if (preg_match('/windows/i', $userAgent)) {
            return 'Windows';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            return 'Mac OS';
        } elseif (preg_match('/linux/i', $userAgent)) {
            return 'Linux';
        } elseif (preg_match('/android/i', $userAgent)) {
            return 'Android';
        } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
            return 'iOS';
        }
        
        return null;
    }
    
    /**
     * Clean old analytics data
     * 
     * @param int $daysToKeep Number of days to keep (default: 365)
     * @return bool
     */
    public function cleanOldData($daysToKeep = 365) {
        $sql = "
            DELETE FROM {$this->table}
            WHERE visited_at < DATE_SUB(NOW(), INTERVAL :days DAY)
        ";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':days' => $daysToKeep]);
        } catch (PDOException $e) {
            error_log("Analytics cleanup error: " . $e->getMessage());
            return false;
        }
    }
}
?>
