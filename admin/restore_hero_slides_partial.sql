DROP TABLE IF EXISTS `hero_slides`;
CREATE TABLE `hero_slides` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `subtitle` text DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `hero_slides` VALUES
(1,'Power the Grid. Secure the palace','industrial grade ','uploads/slides/slide1_1767010942.jpg','2025-12-29 12:04:09','2025-12-29 12:23:09'),
(2,'Advanced Solar Solutions','','uploads/slides/slide2_1767010943.jpeg','2025-12-29 12:04:09','2025-12-29 12:22:23'),
(3,'Military-Grade Security Systems','','uploads/slides/slide3_1767010943.jpeg','2025-12-29 12:04:09','2025-12-29 12:22:23'),
(4,'Expert Installation & Support','','uploads/slides/slide4_1767010943.jpg','2025-12-29 12:04:09','2025-12-29 12:22:23');
