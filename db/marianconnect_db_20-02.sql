-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 20, 2026 at 07:04 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `marianconnect`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_programs`
--

CREATE TABLE `academic_programs` (
  `program_id` int(11) NOT NULL,
  `program_code` varchar(20) NOT NULL,
  `program_name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `level` enum('elementary','junior_high','senior_high','college') NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `description` text NOT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `brochure_pdf` varchar(255) DEFAULT NULL,
  `admission_requirements` text DEFAULT NULL,
  `career_opportunities` text DEFAULT NULL,
  `curriculum_highlights` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `academic_programs`
--

INSERT INTO `academic_programs` (`program_id`, `program_code`, `program_name`, `slug`, `level`, `department`, `description`, `duration`, `featured_image`, `brochure_pdf`, `admission_requirements`, `career_opportunities`, `curriculum_highlights`, `is_active`, `display_order`, `created_at`, `updated_at`) VALUES
(0, 'BSCS', 'Bachelor of Science in Computer Sceince', 'bachelor-of-science-in-computer-sceince', 'college', 'N/A', 'asdasdasd', '4 years', '/assets/uploads/programs/69775f8ad9870_1769430922.png', NULL, '', '', '', 1, 0, '2026-01-26 12:35:22', '2026-01-26 12:35:22');

-- --------------------------------------------------------

--
-- Table structure for table `achievements`
--

CREATE TABLE `achievements` (
  `achievement_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `category` enum('academic','sports','cultural','community_service','research','other') DEFAULT 'other',
  `achievement_date` date NOT NULL,
  `recipient_name` varchar(255) DEFAULT NULL,
  `recipient_type` enum('student','faculty','institution','alumni') DEFAULT 'student',
  `award_level` enum('local','regional','national','international') DEFAULT 'local',
  `featured_image` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `achievements`
--

INSERT INTO `achievements` (`achievement_id`, `title`, `description`, `category`, `achievement_date`, `recipient_name`, `recipient_type`, `award_level`, `featured_image`, `is_featured`, `created_at`) VALUES
(1, 'Yellow', 'SDFsldfl', 'academic', '2025-11-30', 'Jumar S. Avila', 'student', 'local', '/assets/uploads/achievements/6970c33c92272_1768997692.png', 1, '2025-11-28 23:59:38');

-- --------------------------------------------------------

--
-- Table structure for table `active_announcements`
--

CREATE TABLE `active_announcements` (
  `announcement_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `type` enum('general','urgent','academic','event') DEFAULT NULL,
  `priority` enum('low','medium','high') DEFAULT NULL,
  `target_audience` enum('all','students','faculty','parents') DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` bigint(20) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `admin_id`, `action`, `table_name`, `record_id`, `description`, `ip_address`, `created_at`) VALUES
(0, 4, 'login', NULL, NULL, 'Admin logged in successfully', '::1', '2026-01-21 05:10:35'),
(0, 4, 'login', NULL, NULL, 'Admin logged in successfully', '::1', '2026-02-13 01:04:31'),
(0, 4, 'delete', 'academic_programs', 2, 'Deleted program: Junior High School', '::1', '2026-02-13 01:04:43'),
(0, 4, 'delete', 'academic_programs', 3, 'Deleted program: STEM Strand', '::1', '2026-02-13 01:04:46'),
(0, 4, 'delete', 'academic_programs', 4, 'Deleted program: ABM Strand', '::1', '2026-02-13 01:04:48'),
(0, 4, 'delete', 'academic_programs', 1, 'Deleted program: Elementary Education', '::1', '2026-02-13 01:04:52'),
(0, 4, 'delete', 'academic_programs', 6, 'Deleted program: Bachelor of Science in Business Administration', '::1', '2026-02-13 01:04:55'),
(0, 4, 'delete', 'academic_programs', 5, 'Deleted program: Bachelor of Secondary Education', '::1', '2026-02-13 01:04:58');

-- --------------------------------------------------------

--
-- Table structure for table `administration`
--

CREATE TABLE `administration` (
  `admin_member_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `position` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `office_location` varchar(255) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `administration`
--

INSERT INTO `administration` (`admin_member_id`, `name`, `position`, `description`, `featured_image`, `email`, `phone`, `office_location`, `display_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Jumar', 'School President', 'dsfgfdghdfghfdgh', '/assets/uploads/administration/697066c46a66a_1768974020.jpg', 'litsavila97@yahoo.com', '09672203012', 'asdasdfsdf', 0, 1, '2026-01-21 11:43:36', '2026-01-21 11:43:36'),
(2, 'asdasd', 'asfdafsdf', 'asdasffasdf', '/assets/uploads/administration/6970bc0180263_1768995841.jpg', 'litsavila97@yahoo.com', '09672203012', 'asdasdfsdf', 1, 1, '2026-01-21 11:44:01', '2026-01-21 11:45:05'),
(3, 'Karen Adesas', 'School President', 'A lot', '/assets/uploads/administration/697168b9795a2_1769040057.jpg', 'karenadesas@gmail.com', '055-123456', 'Admin Building', 0, 1, '2026-01-22 00:00:57', '2026-01-22 00:00:57');

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('super_admin','admin','editor') DEFAULT 'editor',
  `avatar` varchar(255) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`admin_id`, `username`, `email`, `password_hash`, `full_name`, `role`, `avatar`, `last_login`, `is_active`, `created_at`, `updated_at`) VALUES
(4, 'weqtes', 'jumar.avila@smcc.edu.ph', '$2y$10$Lpj4kQnkgqdfgYNdAyT28epUSCT2blDHFQSn4A8S/NYdjlJY.FWJC', 'Jumar Avila', 'super_admin', '/assets/uploads/avatars/avatar_4_1768972271.jpg', '2026-02-13 09:04:31', 1, '2025-11-27 06:53:14', '2026-02-13 01:04:31');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `type` enum('general','urgent','academic','event') DEFAULT 'general',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `target_audience` enum('all','students','faculty','parents') DEFAULT 'all',
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `title`, `content`, `type`, `priority`, `target_audience`, `start_date`, `end_date`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(0, 'ENROLLMENT NOTICE: MAY 15 - 30, 2026', 'ENROLLMENT ON PROGRESS', 'academic', 'high', 'all', '2026-01-26 00:00:00', '2026-05-30 00:00:00', 1, 4, '2026-01-26 12:25:34', '2026-01-26 12:25:34'),
(2, 'NO CLASSES', 'November 11 to 17, 2025', 'general', 'medium', 'all', '2025-11-28 00:00:00', '2026-01-15 00:00:00', 1, 5, '2025-11-28 04:58:32', '2026-01-06 05:41:38');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `message_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','replied','archived') DEFAULT 'new',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `replied_at` datetime DEFAULT NULL,
  `replied_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`message_id`, `full_name`, `email`, `phone`, `subject`, `message`, `status`, `ip_address`, `user_agent`, `replied_at`, `replied_by`, `created_at`) VALUES
(1, 'Jumar Avila', 'marjumar2002@gmail.com', '09672203012', 'ENROLL', 'Hi', 'read', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', NULL, NULL, '2025-11-28 04:45:55');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `head_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `name`, `image`, `head_name`, `description`, `email`, `phone`, `display_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Academic Affairs', '/assets/uploads/departments/697060cb464ca_1768972491.jpg', 'Jumar Avila', 'Hes cute', 'marjumar2002@gmail.com', '09672203012', 0, 1, '2026-01-21 05:14:51', '2026-01-21 05:14:51');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `location` varchar(255) NOT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `category` enum('academic','sports','cultural','religious','seminar','other') DEFAULT 'other',
  `status` enum('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
  `organizer` varchar(100) DEFAULT NULL,
  `contact_info` varchar(255) DEFAULT NULL,
  `max_participants` int(11) DEFAULT NULL,
  `registration_required` tinyint(1) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `title`, `slug`, `description`, `event_date`, `event_time`, `end_date`, `location`, `featured_image`, `category`, `status`, `organizer`, `contact_info`, `max_participants`, `registration_required`, `is_featured`, `created_by`, `created_at`, `updated_at`) VALUES
(0, 'CHRISTMAS PARTY', 'christmas-party', '<p>ENJOY THE PARTY!</p>', '2026-12-22', '16:00:00', NULL, 'SMCC MOTHER IGNACIA AUDITORIUM', '/assets/uploads/events/6977ec126023a_1769466898.png', 'other', 'upcoming', 'Supreme Student Council', '', NULL, 0, 1, 4, '2026-01-26 22:34:53', '2026-01-26 22:50:41');

-- --------------------------------------------------------

--
-- Table structure for table `facilities`
--

CREATE TABLE `facilities` (
  `facility_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `category` enum('classroom','laboratory','library','sports','chapel','office','other') DEFAULT 'other',
  `capacity` varchar(50) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `facilities`
--

INSERT INTO `facilities` (`facility_id`, `name`, `slug`, `description`, `category`, `capacity`, `location`, `featured_image`, `is_available`, `display_order`, `created_at`, `updated_at`) VALUES
(0, 'CSS laboratory', 'css-laboratory', 'aasdasdsdf', 'laboratory', '50', '2nd Floor', '/assets/uploads/facilities/6971630e5634d_1769038606.png', 1, 0, '2026-01-21 23:36:46', '2026-01-21 23:36:46');

-- --------------------------------------------------------

--
-- Table structure for table `gallery`
--

CREATE TABLE `gallery` (
  `gallery_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `thumbnail_path` varchar(255) DEFAULT NULL,
  `category` enum('campus','events','facilities','students','achievements','other') DEFAULT 'other',
  `event_id` int(11) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `homepage_sliders`
--

CREATE TABLE `homepage_sliders` (
  `slider_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `subtitle` text DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `button_text` varchar(50) DEFAULT NULL,
  `button_link` varchar(255) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `homepage_sliders`
--

INSERT INTO `homepage_sliders` (`slider_id`, `title`, `subtitle`, `image_path`, `button_text`, `button_link`, `display_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Welcome to St. Mary’s College of Catbalogan', 'Forming Minds. Touching Hearts. Transforming Lives.', '/assets/uploads/sliders/697060368412a_1768972342.png', 'Learn More', 'pages/about.php', 2, 1, '2025-11-28 04:47:41', '2026-01-21 23:52:50'),
(2, 'Committed to Excellence in Education', 'Providing holistic, value-oriented, and globally competitive learning.', '/assets/uploads/sliders/69292992c2466_1764305298.jpg', 'View Programs', 'pages/programs.php', 0, 1, '2025-11-28 04:48:18', '2026-01-21 23:52:36'),
(3, 'A Campus Where Students Thrive', 'Explore activities, organizations, and vibrant student communities.', '/assets/uploads/sliders/692929b8c9e47_1764305336.jpg', 'See Activities', 'pages/events.php', 4, 1, '2025-11-28 04:48:56', '2026-01-21 23:52:29'),
(4, 'Our Mission and Vision', 'Guided by faith and service toward academic excellence.', '/assets/uploads/sliders/692929f64c798_1764305398.jpg', 'Read More', 'pages/mission-vision.php', 1, 1, '2025-11-28 04:49:58', '2026-01-21 23:52:50'),
(5, 'Enrollment is Now Open!', 'Join the Marian community—pre-school to college programs available.', '/assets/uploads/sliders/69292a307f1e9_1764305456.jpg', 'Enroll Today', 'pages/programs.php', 5, 1, '2025-11-28 04:50:56', '2026-01-21 23:52:28'),
(6, 'Over 90 Years of Catholic Education', 'A legacy built on service, discipline, and academic excellence.', '/assets/uploads/sliders/69292af3e20cb_1764305651.jpg', 'Our History', 'pages/history.php', 3, 1, '2025-11-28 04:54:11', '2026-01-21 23:52:29'),
(7, 'Meet Our School Leaders', 'Dedicated administrators shaping the future of Marian learners.', '/assets/uploads/sliders/69292b1b4a1ef_1764305691.jpg', 'Administration Page', 'pages/administration.php', 6, 1, '2025-11-28 04:54:51', '2025-11-28 04:54:51');

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `news_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `excerpt` text DEFAULT NULL,
  `content` longtext NOT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `category` enum('academic','sports','events','achievements','general') DEFAULT 'general',
  `author_id` int(11) NOT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `views` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `published_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `news`
--

INSERT INTO `news` (`news_id`, `title`, `slug`, `excerpt`, `content`, `featured_image`, `category`, `author_id`, `status`, `views`, `is_featured`, `published_date`, `created_at`, `updated_at`) VALUES
(12, 'Testing the News', 'testing-the-news', 'Mic Test Keyboard Test', '<p>Test Test Test Test Test</p>', '/assets/uploads/news/69705fdabecbc_1768972250.png', 'academic', 4, 'published', 4, 1, '2026-01-06 14:09:00', '2026-01-06 06:10:20', '2026-01-26 23:19:48'),
(0, 'asdasdsa', 'asdasdsa', 'dfhgghjhjkhj', '<p>asdfgh</p>', '/assets/uploads/news/6977f6d822778_1769469656.png', 'sports', 4, 'published', 3, 1, '2026-01-21 13:28:00', '2026-01-21 05:28:57', '2026-01-28 07:11:24');

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `page_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `page_type` enum('about','mission_vision','history','administration','contact','custom') DEFAULT 'custom',
  `is_published` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pages`
--

INSERT INTO `pages` (`page_id`, `title`, `slug`, `content`, `meta_title`, `meta_description`, `page_type`, `is_published`, `display_order`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'About SMCC', 'about', '<p>Hello!</p>', NULL, NULL, 'about', 1, 0, 4, '2025-11-23 05:37:02', '2025-11-28 04:38:59'),
(2, 'Mission and Vision', 'mission-vision', '<h2>Mission</h2><p>Our mission statement...</p><h2>Vision</h2><p>Our vision statement...</p>', NULL, NULL, 'mission_vision', 1, 0, NULL, '2025-11-23 05:37:02', '2025-11-23 05:37:02'),
(3, 'History', 'history', '<p>Founded in [year], St. Mary\'s College has a rich history...</p>', NULL, NULL, 'history', 1, 0, NULL, '2025-11-23 05:37:02', '2025-11-23 05:37:02'),
(4, 'Contact Us', 'contact', '<p>Get in touch with us...</p>', NULL, NULL, 'contact', 1, 0, NULL, '2025-11-23 05:37:02', '2025-11-23 05:37:02');

-- --------------------------------------------------------

--
-- Table structure for table `popular_news`
--

CREATE TABLE `popular_news` (
  `news_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `excerpt` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `category` enum('academic','sports','events','achievements','general') DEFAULT NULL,
  `author_id` int(11) DEFAULT NULL,
  `status` enum('draft','published','archived') DEFAULT NULL,
  `views` int(11) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT NULL,
  `published_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `recent_news`
-- (See below for the actual view)
--
CREATE TABLE `recent_news` (
`news_id` int(11)
,`title` varchar(255)
,`slug` varchar(255)
,`excerpt` text
,`content` longtext
,`featured_image` varchar(255)
,`category` enum('academic','sports','events','achievements','general')
,`author_id` int(11)
,`status` enum('draft','published','archived')
,`views` int(11)
,`is_featured` tinyint(1)
,`published_date` datetime
,`created_at` timestamp
,`updated_at` timestamp
,`author_name` varchar(100)
);

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('text','textarea','number','boolean','json') DEFAULT 'text',
  `category` varchar(50) DEFAULT 'general',
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`setting_id`, `setting_key`, `setting_value`, `setting_type`, `category`, `description`, `updated_at`) VALUES
(1, 'site_name', 'St. Mary\'s College of Catbalogan', 'text', 'general', 'Institution Name', '2025-11-23 05:37:02'),
(2, 'site_tagline', 'Excellence in Catholic Education', 'text', 'general', 'Site Tagline', '2025-11-23 05:37:02'),
(3, 'contact_email', 'info@smcc.edu.ph', 'text', 'contact', 'Primary Contact Email', '2025-11-23 05:37:02'),
(4, 'contact_phone', '(055) 251-2345', 'text', 'contact', 'Primary Contact Phone', '2025-11-23 05:37:02'),
(5, 'contact_address', 'Catbalogan City, Samar, Philippines', 'text', 'contact', 'Physical Address', '2025-11-23 05:37:02'),
(6, 'facebook_url', 'https://www.facebook.com/smccshc', 'text', 'social', 'Facebook Page URL', '2026-01-26 05:03:37'),
(7, 'twitter_url', '', 'text', 'social', 'Twitter Profile URL', '2025-11-23 05:37:02'),
(8, 'instagram_url', '', 'text', 'social', 'Instagram Profile URL', '2025-11-23 05:37:02'),
(9, 'youtube_url', '', 'text', 'social', 'YouTube Channel URL', '2025-11-23 05:37:02'),
(10, 'google_analytics_id', '', 'text', 'analytics', 'Google Analytics Tracking ID', '2025-11-23 05:37:02'),
(11, 'maintenance_mode', 'false', 'boolean', 'general', 'Site Maintenance Mode', '2026-01-26 12:24:27'),
(12, 'timezone', 'Asia/Manila', 'text', 'general', 'Site Timezone', '2025-11-23 05:37:02');

-- --------------------------------------------------------

--
-- Table structure for table `student_organizations`
--

CREATE TABLE `student_organizations` (
  `org_id` int(11) NOT NULL,
  `org_name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `acronym` varchar(20) DEFAULT NULL,
  `description` text NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `category` enum('academic','sports','cultural','religious','service','special_interest') DEFAULT 'academic',
  `adviser_name` varchar(100) DEFAULT NULL,
  `president_name` varchar(100) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `established_year` year(4) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_organizations`
--

INSERT INTO `student_organizations` (`org_id`, `org_name`, `slug`, `acronym`, `description`, `logo`, `category`, `adviser_name`, `president_name`, `contact_email`, `is_active`, `established_year`, `display_order`, `created_at`, `updated_at`) VALUES
(0, 'Student Council', 'student-council', 'SC', 'YEyooooo', '/assets/uploads/organizations/6970c17d4fe6e_1768997245.png', 'academic', 'Mr. Jumar Avila', 'Mr. Jumar Avila', '', 1, '2022', 0, '2026-01-21 12:07:25', '2026-01-21 12:07:25');

-- --------------------------------------------------------

--
-- Stand-in structure for view `upcoming_events`
-- (See below for the actual view)
--
CREATE TABLE `upcoming_events` (
`event_id` int(11)
,`title` varchar(255)
,`slug` varchar(255)
,`description` text
,`event_date` date
,`event_time` time
,`end_date` date
,`location` varchar(255)
,`featured_image` varchar(255)
,`category` enum('academic','sports','cultural','religious','seminar','other')
,`status` enum('upcoming','ongoing','completed','cancelled')
,`organizer` varchar(100)
,`contact_info` varchar(255)
,`max_participants` int(11)
,`registration_required` tinyint(1)
,`is_featured` tinyint(1)
,`created_by` int(11)
,`created_at` timestamp
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `visitor_analytics`
--

CREATE TABLE `visitor_analytics` (
  `visit_id` bigint(20) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `page_url` varchar(500) DEFAULT NULL,
  `referrer` varchar(500) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `device_type` enum('desktop','tablet','mobile') DEFAULT 'desktop',
  `browser` varchar(50) DEFAULT NULL,
  `os` varchar(50) DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `visit_duration` int(11) DEFAULT 0,
  `visited_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `visitor_analytics`
--

INSERT INTO `visitor_analytics` (`visit_id`, `ip_address`, `user_agent`, `page_url`, `referrer`, `country`, `device_type`, `browser`, `os`, `session_id`, `visit_duration`, `visited_at`) VALUES
(0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '/marianconnect/', '', NULL, 'desktop', NULL, NULL, 'ev2k93n7856f42l1b8k8so9qdq', 0, '2026-02-13 01:01:15'),
(0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '/marianconnect/pages/programs.php', 'http://localhost/marianconnect/', NULL, 'desktop', NULL, NULL, 'ev2k93n7856f42l1b8k8so9qdq', 0, '2026-02-13 01:04:15'),
(0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '/marianconnect/pages/contact.php', 'http://localhost/marianconnect/pages/programs.php', NULL, 'desktop', NULL, NULL, 'ev2k93n7856f42l1b8k8so9qdq', 0, '2026-02-13 01:04:24');

-- --------------------------------------------------------

--
-- Structure for view `recent_news`
--
DROP TABLE IF EXISTS `recent_news`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `recent_news`  AS SELECT `n`.`news_id` AS `news_id`, `n`.`title` AS `title`, `n`.`slug` AS `slug`, `n`.`excerpt` AS `excerpt`, `n`.`content` AS `content`, `n`.`featured_image` AS `featured_image`, `n`.`category` AS `category`, `n`.`author_id` AS `author_id`, `n`.`status` AS `status`, `n`.`views` AS `views`, `n`.`is_featured` AS `is_featured`, `n`.`published_date` AS `published_date`, `n`.`created_at` AS `created_at`, `n`.`updated_at` AS `updated_at`, `a`.`full_name` AS `author_name` FROM (`news` `n` join `admin_users` `a` on(`n`.`author_id` = `a`.`admin_id`)) WHERE `n`.`status` = 'published' ORDER BY `n`.`published_date` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `upcoming_events`
--
DROP TABLE IF EXISTS `upcoming_events`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `upcoming_events`  AS SELECT `events`.`event_id` AS `event_id`, `events`.`title` AS `title`, `events`.`slug` AS `slug`, `events`.`description` AS `description`, `events`.`event_date` AS `event_date`, `events`.`event_time` AS `event_time`, `events`.`end_date` AS `end_date`, `events`.`location` AS `location`, `events`.`featured_image` AS `featured_image`, `events`.`category` AS `category`, `events`.`status` AS `status`, `events`.`organizer` AS `organizer`, `events`.`contact_info` AS `contact_info`, `events`.`max_participants` AS `max_participants`, `events`.`registration_required` AS `registration_required`, `events`.`is_featured` AS `is_featured`, `events`.`created_by` AS `created_by`, `events`.`created_at` AS `created_at`, `events`.`updated_at` AS `updated_at` FROM `events` WHERE `events`.`status` = 'upcoming' AND `events`.`event_date` >= curdate() ORDER BY `events`.`event_date` ASC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_programs`
--
ALTER TABLE `academic_programs`
  ADD PRIMARY KEY (`program_id`),
  ADD UNIQUE KEY `program_code` (`program_code`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_level` (`level`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `achievements`
--
ALTER TABLE `achievements`
  ADD PRIMARY KEY (`achievement_id`),
  ADD KEY `idx_date` (`achievement_date`),
  ADD KEY `idx_category` (`category`);
COMMIT;
