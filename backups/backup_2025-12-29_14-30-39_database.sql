-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: ddnew_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `role` varchar(10) NOT NULL DEFAULT 'admin',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'admin','$2y$10$n0icLiR6Oas/A60meqJDnuOsQZnDcrrSe3nLmDwa.VWfQ4IyyHroy','2025-11-20 14:22:34','super');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blog_posts`
--

DROP TABLE IF EXISTS `blog_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blog_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_posts`
--

LOCK TABLES `blog_posts` WRITE;
/*!40000 ALTER TABLE `blog_posts` DISABLE KEYS */;
/*!40000 ALTER TABLE `blog_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (2,'CCTV Camera'),(1,'Solar');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gallery_items`
--

DROP TABLE IF EXISTS `gallery_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gallery_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `image_path_1` varchar(255) DEFAULT NULL,
  `image_path_2` varchar(255) DEFAULT NULL,
  `image_path_3` varchar(255) DEFAULT NULL,
  `image_path_4` varchar(255) DEFAULT NULL,
  `image_path_5` varchar(255) DEFAULT NULL,
  `youtube_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gallery_items`
--

LOCK TABLES `gallery_items` WRITE;
/*!40000 ALTER TABLE `gallery_items` DISABLE KEYS */;
INSERT INTO `gallery_items` VALUES (1,'Solar Street Light Installation','We have successfully installed solar street lights for various clients, including homeowners, construction sites, and commercial properties. Our solutions ensure 24/7 illumination, enhancing security and visibility in dark areas.','2025-12-08 12:10:26','uploads/gallery_6936c0328c207.jpg','uploads/gallery_6936c03290980.jpg','uploads/gallery_6936c03291e13.jpg','uploads/gallery_6936c03292df9.jpg','',''),(3,'Solar Power borehole','Barrister Chike approached us to upgrade his water pumping system. We replaced his traditional Sumo pump with a 1HP DC solar pump powered by six 250W solar panels. The new system eliminated the need for fuel-powered generators, providing a clean, cost-effective water solution.','2025-12-08 12:20:37','uploads/gallery_6936c295e3a52.jpg','uploads/gallery_6936c295e8a8a.jpg','uploads/gallery_6936c295ee24f.jpg','','','');
/*!40000 ALTER TABLE `gallery_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hero_slides`
--

DROP TABLE IF EXISTS `hero_slides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hero_slides` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `subtitle` text DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hero_slides`
--

LOCK TABLES `hero_slides` WRITE;
/*!40000 ALTER TABLE `hero_slides` DISABLE KEYS */;
INSERT INTO `hero_slides` VALUES (1,'Power the Grid. Secure the palace','industrial grade ','uploads/slides/slide1_1767010942.jpg','2025-12-29 12:04:09','2025-12-29 12:23:09'),(2,'Advanced Solar Solutions','','uploads/slides/slide2_1767010943.jpeg','2025-12-29 12:04:09','2025-12-29 12:22:23'),(3,'Military-Grade Security Systems','','uploads/slides/slide3_1767010943.jpeg','2025-12-29 12:04:09','2025-12-29 12:22:23'),(4,'Expert Installation & Support','','uploads/slides/slide4_1767010943.jpg','2025-12-29 12:04:09','2025-12-29 12:22:23');
/*!40000 ALTER TABLE `hero_slides` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ip_time` (`ip_address`,`attempt_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `reference` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'NGN',
  `payment_status` varchar(50) NOT NULL DEFAULT 'pending',
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_phone` varchar(50) DEFAULT NULL,
  `customer_address` text DEFAULT NULL,
  `products_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`products_json`)),
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_main` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `specifications` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specifications`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `image_1` varchar(255) DEFAULT NULL,
  `image_2` varchar(255) DEFAULT NULL,
  `image_3` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (11,'11kva 48v TAICO Veil.Max GD11048MH Hybrid Solar Inverter – High-Performance Off-Grid Power Solution','Solar',500000.00,'uploads/image_main_69368b506326b8.31462691.jpg','The TAICO Veil.Max GD11048MH is a powerful 11kW hybrid solar inverter designed for residential, commercial, and industrial use. With support for up to 11,000W, 150A charging, and a wide solar input range (90–500VDC), it delivers reliable off-grid or backup power with high efficiency and smart AC/solar integration.',NULL,NULL,'2025-12-08 08:25:39','uploads/image_1_69368b506455d6.99673659.jpg',NULL,NULL,1),(12,'Minimum Solar Protection Set ; Essential Safety. Maximum Peace of Mind.','Solar',35000.00,'uploads/image_main_69382a45303ef6.38832257.png','Protect your solar investment with our Minimum Solar Protection Set, designed to keep your system running safely and efficiently.\r\n\r\nThis all-in-one safety solution guards against electrical faults, overloads, and voltage surges—so your power stays clean, reliable, and uninterrupted.\r\n\r\n \r\n\r\n📦 Package Includes:\r\n– 4-Way Enclosure Box – Compact, durable, and ready to house all protective gear with ease.\r\n– 32A AC Input Breaker (1P)– Provides overload and short-circuit protection on the AC side.\r\n– Volt and Amp Protector – Monitors and defends against harmful voltage/current fluctuations.\r\n– 60A DC Breaker (1P)– Protects the DC side from overcurrent and electrical hazards.\r\n\r\n—\r\n\r\nWhy It Matters:\r\n– Shields your solar inverter and batteries from damage\r\n– Ensures longer lifespan for your entire solar system\r\n– Easy to install and integrate into existing setups\r\n– Ideal for homes, small businesses, and off-grid installations\r\n\r\n—\r\n\r\nPrice: ₦35,000\r\n\r\n📍 Installation support available on request\r\n\r\n—\r\n\r\nStay safe. Stay solar-powered.\r\n\r\n📞 Call us today or visit [www.ddbuildingtech.com](http://www.ddbuildingtech.com)\r\n\r\n#SolarSafetyFirst #DDTechProtects #SmartPowerSolutions\r\n\r\n—',NULL,NULL,'2025-12-09 13:55:17',NULL,NULL,NULL,0),(13,'Medium Solar Protection Set ;Shield Your Solar System with Confidence','Solar',50000.00,'uploads/image_main_693829e6ab9ae9.22126645.png','Ensure the safety and longevity of your solar investment with the Medium Solar Protection Set—a robust assembly of essential components engineered to deliver maximum protection and peace of mind. Designed for medium-scale solar installations, this set balances performance, affordability, and ease of integration.\r\n\r\n—\r\n\r\nPackage Includes:\r\n– 6-Way Enclosure Box– Neatly organizes and secures all internal protective devices\r\n– 32A AC Input Breaker (1P)– Protects AC line from overload and short circuit\r\n– 60A DC Breaker (1P) – Secures DC circuits against overcurrent\r\n– Volt & Amp Protector (2P)– Monitors and guards your system from unstable voltage or current\r\n– DC SPD (Surge Protection Device)– Absorbs and deflects damaging voltage spikes from lightning and power surges\r\n\r\n—\r\n\r\n✅ Why It’s a Must-Have:\r\n– Prevents fire hazards and equipment damage\r\n– Extends the lifespan of inverters, batteries, and panels\r\n– Ensures safer energy distribution for homes and small businesses\r\n– Easy to install—compatible with most inverter systems\r\n\r\n—\r\n\r\n💰 Price: ₦50,000\r\n🛠️ Professional installation and guidance available from DD Building Tech Solutions\r\n\r\n—\r\n\r\nReady to secure your solar system?\r\n📞 Call: 08066113394 | 09161212301\r\n🌐 Visit: [www.ddbuildingtech.com](http://www.ddbuildingtech.com)\r\n\r\n#MediumProtectionSet #SolarDefense #DDTechSecures #SmartPowerNG',NULL,NULL,'2025-12-09 13:53:42',NULL,NULL,NULL,1),(14,'FAMILY PRIDE PACKAGE–Your Home. Fully Powered. Stress-Free.','Solar',3080000.00,'uploads/image_main_69382a2dbeea94.81837489.png','Take control of your energy with the Family Pride Package—a complete solar power solution designed for comfort, reliability, and affordability.\r\n\r\nWhether you’re powering a busy household or a growing small business, this system is designed to handle it all while reducing electricity costs and promoting a greener environment.\r\n\r\n🌞 What You Get\r\n– 6 Mono Crystalline Solar Panels\r\nHigh-efficiency panels built to harness more energy, even in low-light conditions, maximizing your power generation all year round.\r\n\r\n– 4 Durable Tubular Batteries\r\nEngineered for deep cycles and extended life, these batteries store enough energy to keep your appliances running when you need them most.\r\n\r\n– 5KVA Inverter\r\nDelivers stable, clean electricity to support multiple high-power appliances at once, protecting your home and electronics from power surges or grid failure.\r\n\r\n🔌 Powering Your Everyday Life\r\nRun all your essentials without stress, including:\r\n– Solar fan & lighting systems\r\n– Laptops & smartphones\r\n– Televisions & refrigerators\r\n– Washing machine\r\n– 1HP Air Conditioner\r\n– Microwave or blender\r\n\r\nNo noise. No fuel. Just uninterrupted power.\r\n\r\n🎉 Special Offer – 30% Off!\r\n– Old Price: ₦4,400,000\r\n– Now: ₦3,080,000\r\n\r\nGet premium components, expert installation, and lifetime value—all at a huge discount.\r\n\r\nEvery package is installed and supported by certified professionals from DD Building Technology Solutions, ensuring the safety and performance you can trust.\r\n\r\nWhy Choose Us?\r\nDD Tech stands at the forefront of Nigeria’s clean energy transition—bringing you smart, reliable, and customer-focused solar solutions that last.\r\n\r\n📞 Call Now: 08066113394 | 09161212301\r\n🌐 Visit: [www.ddbuildingtech.com](http://www.ddbuildingtech.com)',NULL,NULL,'2025-12-09 13:54:53',NULL,NULL,NULL,0),(15,'Complete 1hp Solar Borehole Installation Kit','Solar',956000.00,'uploads/image_main_6938299cd6c2b9.04470984.jpeg','Complete 1hp Solar Borehole Installation Kit \r\nPower your water needs sustainably with our Complete 1hp Solar Borehole Installation Kit, a fully integrated solar water pumping solution ideal for homes, farms, schools, estates, and remote areas without access to reliable electricity.\r\n\r\nPackage Includes:\r\n✅ 1hp DC Submersible Pump – Efficient and durable, ideal for deep well water extraction.\r\n\r\n✅ 6 × 250W Solar Panels – High-efficiency monocrystalline panels to power your pump throughout the day.\r\n\r\n✅ Borehole Charge Controller – Intelligent MPPT controller for safe and optimized solar power usage.\r\n\r\n✅ 10 yards (1.5mm) 3-Core Solar Borehole Cable – Designed for deep installations, resistant to water and corrosion.\r\n\r\n✅ 20 yards (4mm) 2-Core PV Cable – Connects your solar panels to the controller for seamless power transmission.\r\n\r\nKey Features:\r\n💧 Reliable water pumping with no monthly electricity bills\r\n\r\n🌞 Operates entirely on solar energy – perfect for off-grid locations\r\n\r\n🔧 Easy to install and maintain\r\n\r\n🛡️ 1-Year Warranty on all components\r\n\r\n💰 Cost-effective and environmentally friendly solution\r\n\r\nWhether you need it for irrigation, domestic water supply, or livestock watering, this kit provides a dependable and long-term water solution using clean solar energy.',NULL,NULL,'2025-12-09 13:52:28',NULL,NULL,NULL,0),(16,'SOLAR BENEFIT PACKAGE-Empowering Homes & Businesses with Clean, Reliable Energy','Solar',1880000.00,'uploads/image_main_693829caccd3a4.64044357.png','Say goodbye to power outages and rising electricity bills with our Solar Benefits Package—a complete, expertly engineered solution by DD Building Technology Solutions.\r\n\r\nWhether you’re looking to secure energy independence for your home or elevate your business with sustainable power, this package delivers unmatched efficiency, durability, and performance.\r\n\r\nWhat’s Inside the Package\r\n– 4 Mono Crystalline Solar Panels\r\n\r\nHarness the sun with high-efficiency mono panels built to convert maximum sunlight into clean, usable energy—even in low-light conditions.\r\n\r\n– 2 Tubular Batteries\r\nGet powerful, long-lasting energy storage designed for deep cycles and minimal maintenance, ensuring power when you need it most.\r\n\r\n– 5KVA Inverter\r\nOur premium inverter offers stable power conversion, protecting your appliances and supporting seamless electricity delivery throughout the day.\r\n\r\nPower Capacity\r\nThis robust package is capable of running:\r\n– ✅ Solar fan\r\n– ✅ Lighting systems\r\n– ✅ Laptop & mobile phone charging\r\n– ✅ Television\r\n– ✅ Refrigerator\r\n– ✅ Washing machine\r\n– ✅ 1HP air conditioner\r\n\r\nIdeal for households and small businesses looking to run daily essentials and more, with peace of mind and zero noise.\r\n\r\nLimited Time Offer – 30% Discount!\r\n– Original Price: ₦2,685,800\r\n– Now: ₦1,880,000\r\n\r\nInstallation handled by our certified professionals ensures safety, reliability, and optimal system performance—no hassle, just clean energy.\r\n\r\n \r\n\r\nWhy Choose DD Tech?\r\nWith  a proven track record, we deliver tailored energy solutions backed by quality components, professional installations, and ongoing support.\r\n\r\nOur clients trust us because we don’t just sell solar—we build energy independence.\r\n\r\n📞 Call Now: 08066113394 | 09161212301\r\n🌐 Visit: [www.ddbuildingtechnology.com](http://www.ddbuildingtech.com)',NULL,NULL,'2025-12-09 13:53:14',NULL,NULL,NULL,1),(17,'Motorola 399 walkie talkie','',50000.00,'uploads/image_main_69382a146a7909.69230539.png','',NULL,NULL,'2025-12-09 13:54:28',NULL,NULL,NULL,0),(18,'AiSolar Camera + Lighting: Illuminate, Observe, and Secure – All in One!','Solar',150000.00,'uploads/image_main_69382a615314b4.16965042.png','',NULL,NULL,'2025-12-09 13:55:45',NULL,NULL,NULL,1);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_settings`
--

DROP TABLE IF EXISTS `site_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_settings`
--

LOCK TABLES `site_settings` WRITE;
/*!40000 ALTER TABLE `site_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `site_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_activity_log`
--

DROP TABLE IF EXISTS `user_activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_activity_log`
--

LOCK TABLES `user_activity_log` WRITE;
/*!40000 ALTER TABLE `user_activity_log` DISABLE KEYS */;
INSERT INTO `user_activity_log` VALUES (1,1,'view_manage_users','2025-12-08 09:28:38'),(2,1,'add_user: 17','2025-12-08 09:29:01'),(3,1,'view_dashboard','2025-12-08 09:29:05'),(4,1,'view_dashboard','2025-12-08 09:36:42'),(5,1,'view_dashboard','2025-12-08 10:40:34'),(6,1,'view_products','2025-12-08 10:48:28'),(7,1,'view_dashboard','2025-12-08 10:49:03'),(8,1,'logout','2025-12-08 10:49:14'),(9,1,'admin_login','2025-12-09 08:27:10'),(10,1,'view_dashboard','2025-12-09 08:27:10'),(11,1,'view_dashboard','2025-12-09 08:27:24'),(12,1,'view_dashboard','2025-12-09 08:27:31'),(13,1,'view_dashboard','2025-12-09 08:27:50'),(14,1,'view_products','2025-12-09 08:27:58'),(15,1,'view_dashboard','2025-12-09 09:05:37'),(16,1,'view_dashboard','2025-12-09 09:33:30'),(17,1,'admin_login','2025-12-09 13:51:34'),(18,1,'view_dashboard','2025-12-09 13:51:35'),(19,1,'view_dashboard','2025-12-09 13:51:59'),(20,1,'view_products','2025-12-09 13:52:05'),(21,1,'admin_login','2025-12-14 09:14:21'),(22,1,'view_dashboard','2025-12-14 09:14:22'),(23,1,'admin_login','2025-12-18 14:07:49'),(24,1,'view_dashboard','2025-12-18 14:07:50'),(25,1,'view_manage_users','2025-12-18 14:08:06'),(26,1,'edit_user: 17','2025-12-18 14:08:30'),(27,1,'edit_user: 17','2025-12-18 14:09:21'),(28,1,'view_manage_users','2025-12-18 14:09:26'),(29,1,'logout','2025-12-18 14:09:30'),(30,17,'user_login','2025-12-18 14:09:43'),(31,17,'view_dashboard','2025-12-18 14:09:43'),(32,17,'user_login','2025-12-18 14:12:41'),(33,17,'view_dashboard','2025-12-18 14:12:46'),(34,17,'view_products','2025-12-18 14:19:43'),(35,17,'view_products','2025-12-18 14:19:43'),(36,17,'user_login','2025-12-18 14:28:21'),(37,17,'view_dashboard','2025-12-18 14:28:22'),(38,17,'view_products','2025-12-18 14:28:39'),(39,17,'view_dashboard','2025-12-18 14:28:42'),(40,17,'view_dashboard','2025-12-18 14:28:47'),(41,17,'view_products','2025-12-18 14:30:52'),(42,17,'view_products','2025-12-18 14:31:10'),(43,17,'view_products','2025-12-18 14:31:14'),(44,17,'view_products','2025-12-18 14:31:40'),(45,17,'view_dashboard','2025-12-18 14:35:24'),(46,17,'view_products','2025-12-18 14:35:44'),(47,2147483647,'add_to_cart','2025-12-19 10:16:53'),(48,1,'admin_login','2025-12-29 12:10:23'),(49,1,'view_dashboard','2025-12-29 12:10:23'),(50,1,'update_hero_slides','2025-12-29 12:10:52'),(51,1,'update_hero_slides','2025-12-29 12:11:07'),(52,1,'update_hero_slides','2025-12-29 12:15:16'),(53,1,'update_hero_slides','2025-12-29 12:16:35'),(54,1,'update_hero_slides','2025-12-29 12:22:23'),(55,1,'update_hero_slides','2025-12-29 12:23:09'),(56,1,'update_hero_slides','2025-12-29 12:33:07'),(57,1,'update_hero_slides','2025-12-29 13:05:18'),(58,1,'view_backup_restore','2025-12-29 13:05:22'),(59,1,'view_order_management','2025-12-29 13:06:12'),(60,1,'view_order_management','2025-12-29 13:07:54'),(61,1,'view_order_management','2025-12-29 13:08:15'),(62,1,'view_dashboard','2025-12-29 13:08:22'),(63,1,'view_products','2025-12-29 13:08:41'),(64,1,'view_manage_users','2025-12-29 13:09:40'),(65,1,'view_dashboard','2025-12-29 13:09:52'),(66,1,'view_products','2025-12-29 13:09:57'),(67,1,'view_dashboard','2025-12-29 13:09:59'),(68,1,'view_dashboard','2025-12-29 13:11:42'),(69,1,'view_products','2025-12-29 13:11:44'),(70,1,'view_order_management','2025-12-29 13:12:02'),(71,1,'view_manage_users','2025-12-29 13:12:08'),(72,1,'view_backup_restore','2025-12-29 13:12:12'),(73,1,'view_dashboard','2025-12-29 13:25:49'),(74,1,'view_backup_restore','2025-12-29 13:25:56'),(75,1,'view_backup_restore','2025-12-29 13:26:00'),(76,1,'view_backup_restore','2025-12-29 13:28:32'),(77,1,'view_backup_restore','2025-12-29 13:28:36'),(78,1,'view_backup_restore','2025-12-29 13:30:39');
/*!40000 ALTER TABLE `user_activity_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` varchar(10) NOT NULL DEFAULT 'admin',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (2,'Roland','$2y$10$OqJEXxk3Qk1w5glTOX0B5erPDGv.7p9XZ2slAjRyepopiCcre.RPC','rolandfelix70@gmail.com','2025-11-20 14:57:24','admin'),(15,'RolandHaynes','$2y$10$kYa8aVEop7GNVBR1JA0xBOo6vd2s0tLmlhxpnZK1got/FHj67YTZm','efsgls@gmail.com','2025-11-24 09:08:48','admin'),(17,'chinelo','$2y$10$HQoCedcXFoX4DJaSTyIABeXw.Oaq5605IaepHUqbbdrxKYoN7ZrlG','chinelo@ddbuildingtech.com','2025-12-08 09:29:01','admin');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-29 14:30:40
