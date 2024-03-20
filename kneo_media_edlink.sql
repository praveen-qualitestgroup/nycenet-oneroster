-- kneo_media.applications definition

CREATE TABLE `applications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `application_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `application_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `application_secret` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- kneo_media.integrations definition

CREATE TABLE `integrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ext_integration_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `application_id` bigint(20) unsigned NOT NULL,
  `access_token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ext_source_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ext_source_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ext_updated_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `integrations_application_id_foreign` (`application_id`),
  CONSTRAINT `integrations_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- kneo_media.districts definition

CREATE TABLE `districts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ext_district_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `integration_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `addr_street` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `addr_unit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `addr_postal_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `addr_city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `addr_state` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `addr_country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `time_zone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ext_updated_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `districts_integration_id_foreign` (`integration_id`),
  CONSTRAINT `districts_integration_id_foreign` FOREIGN KEY (`integration_id`) REFERENCES `integrations` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- kneo_media.schools add new column

ALTER TABLE `schools`
ADD COLUMN `ext_school_id` varchar(255) DEFAULT NULL AFTER `id`,
ADD COLUMN `district_id` bigint(20) DEFAULT NULL AFTER `ext_school_id`,
ADD COLUMN `ext_updated_at` timestamp DEFAULT NULL AFTER `updated_at`,
ADD COLUMN `deleted_at` timestamp DEFAULT NULL AFTER `promo_code_id`;

-- kneo_media.classroom add new column
ALTER TABLE `classrooms`
ADD COLUMN `ext_class_id` varchar(255) DEFAULT NULL AFTER `id`,
ADD COLUMN `ext_updated_at` timestamp DEFAULT NULL AFTER `created_at`,
ADD COLUMN `deleted_at` timestamp DEFAULT NULL AFTER `ext_updated_at`;


-- kneo_media.users add new column

ALTER TABLE `users`
ADD COLUMN `middle_name` varchar(100) DEFAULT NULL AFTER `first_name`,
ADD COLUMN `ext_user_type` text DEFAULT NULL AFTER `user_type`,
ADD COLUMN `ext_user_id` varchar(255) DEFAULT NULL AFTER `id`,
ADD COLUMN `ext_updated_at` timestamp DEFAULT NULL AFTER `visited_welcome_screen`,
ADD COLUMN `deleted_at` timestamp DEFAULT NULL AFTER `ext_updated_at`,
ADD COLUMN `identifiers` json DEFAULT NULL AFTER `deleted_at`;

-- kneo_media.user_classrooms add new column
ALTER TABLE user_classrooms
ADD COLUMN `created_at` timestamp DEFAULT NULL AFTER `user_id`,
ADD COLUMN `updated_at` timestamp DEFAULT NULL AFTER `created_at`,
ADD COLUMN `deleted_at` timestamp DEFAULT NULL AFTER `updated_at`;

-- kneo_media.students add new column
ALTER TABLE `students`
ADD COLUMN `ext_student_id` varchar(255) DEFAULT NULL AFTER `id`,
ADD COLUMN `ext_updated_at` timestamp DEFAULT NULL AFTER `email`,
ADD COLUMN `deleted_at` timestamp DEFAULT NULL AFTER `ext_updated_at`,
ADD COLUMN `identifiers` json DEFAULT NULL AFTER `deleted_at`;

-- kneo_media.student_classrooms add new column
ALTER TABLE student_classrooms
ADD COLUMN `created_at` timestamp DEFAULT NULL AFTER `student_id`,
ADD COLUMN `updated_at` timestamp DEFAULT NULL AFTER `created_at`,
ADD COLUMN `deleted_at` timestamp DEFAULT NULL AFTER `updated_at`;


-- kneo_media.jobs definition

CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- kneo_media.failed_jobs definition

CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


ALTER TABLE applications
ADD COLUMN `app_code` varchar(255) NOT NULL AFTER `application_name`;

--
-- Table structure for table `lrn_activity`
--

DROP TABLE IF EXISTS `lrn_activity`;
CREATE TABLE `lrn_activity` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lrn_reference_id` varchar(255) NOT NULL,
  `lrn_desc` text,
  `lesson_group_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0;


--
-- Table structure for table `pre_post_data`
--

DROP TABLE IF EXISTS `pre_post_data`;
CREATE TABLE `pre_post_data` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lrn_activity_id` varchar(255) NOT NULL,
  `lrn_pre_session_id` varchar(255) DEFAULT NULL,
  `lrn_post_session_id` varchar(255) DEFAULT NULL,
  `student_id` int NOT NULL,
  `teacher_id` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pre_post_data_FK` (`student_id`),
  CONSTRAINT `pre_post_data_FK` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=latin1;

ALTER TABLE pre_post_data DROP FOREIGN KEY pre_post_data_FK;

--
-- Table structure for table `score_range`
--

DROP TABLE IF EXISTS `score_range`;
CREATE TABLE `score_range` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lrn_id` int NOT NULL,
  `min_vale` int NOT NULL,
  `max_value` int NOT NULL,
  `level` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=latin1;

--
-- Inserting data for table `score_range`
--

INSERT INTO `score_range` VALUES (1,1,0,8,'Emerging',NULL,NULL),(2,1,9,16,'Approaching',NULL,NULL),(3,1,17,24,'Mastery',NULL,NULL),(4,1,25,30,'Exceeds Standards',NULL,NULL),(5,2,0,3,'Emerging',NULL,NULL),(6,2,4,6,'Approaching',NULL,NULL),(7,2,7,9,'Mastery',NULL,NULL),(8,2,10,11,'Exceeds Standards',NULL,NULL),(9,3,0,3,'Emerging',NULL,NULL),(10,3,4,6,'Approaching',NULL,NULL),(11,3,7,9,'Mastery',NULL,NULL),(12,3,10,11,'Exceeds Standards',NULL,NULL);

--
-- Table structure for table `token_expiration`
--

DROP TABLE IF EXISTS `token_expiration`;
CREATE TABLE `token_expiration` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `user_type` varchar(20) DEFAULT NULL,
  `jwt_expired_token` varchar(255) DEFAULT NULL,
  `jwt_expiry_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

ALTER TABLE pre_post_data ADD COLUMN `pre_status` int DEFAULT 0 AFTER `updated_by`

ALTER TABLE pre_post_data ADD COLUMN `post_status` int DEFAULT 0 AFTER `pre_status`

ALTER TABLE pre_post_data ADD COLUMN `pre_score` int DEFAULT 0 AFTER `post_status`

ALTER TABLE pre_post_data ADD COLUMN `post_score` int DEFAULT 0 AFTER `pre_score`

DROP TABLE IF EXISTS `pre_post_form_data`;
CREATE TABLE `pre_post_form_data` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `classroom_id` int NOT NULL,
  `lesson_group_id` int DEFAULT NULL,
  `question_id` int DEFAULT NULL,
  `pre_value` varchar(3) DEFAULT NULL,
  `post_value` varchar(3) DEFAULT NULL,
  `pre_time` varchar(50) DEFAULT NULL,
  `post_time` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `pre_post_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pre_post_questions` (
  `question_id` int NOT NULL AUTO_INCREMENT,
  `question` varchar(100) DEFAULT NULL,
  `skill_id` int NOT NULL,
  `range` int NOT NULL,
  PRIMARY KEY (`question_id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

ALTER TABLE pre_post_questions MODIFY COLUMN question TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL;

INSERT INTO `pre_post_questions` VALUES (1,'Does the student hold the book right side up?',1,3),(2,'Does the student turn the pages from right to left?',1,3),(3,'Does the student point to the front cover of the book?',2,4),(4,'Does the student point to the back cover of the book?',2,4),(5,'Does the student point to 1 word?',3,4),(6,'Does the student accurately recognize (not read, but from memorization) 1 word?',3,4),(7,'Does the student respond to the question about how written and spoken words are connected?',3,4),(8,'Does the student accurately clap between the words of the sentence?',4,4),(9,'Does the student point and accurately read some of the letters in the alphabet?',5,4),(10,'Does the student point to and accurately read some of the letters in their name?',6,4),(11,'Does the student point and accurately read some of the sounds of the letters in the alphabet?',7,3),(12,'Does the student recognize the rhyming words?',8,3),(13,'Does the student recognize the letter O?',9,4),(14,'Does the student recognize the letter T?',10,4);

DROP TABLE IF EXISTS `skills`;
CREATE TABLE `skills` (
  `id` int NOT NULL AUTO_INCREMENT,
  `skill` text DEFAULT NULL,
  `lesson_group_id` int DEFAULT NULL,
  `rationale` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8

INSERT INTO `skills` VALUES (1,'Follow words from left to right, top to bottom, and page by page.',331,'Lesson 2: Soha\'s New Book'),(2,'Identify front and back cover of a book.',331,'Lesson 1: Laila\'s Cookbook'),(3,'Recognize that spoken words are represented in written language.',331,'Lesson 3: Words	331'),(4,'Understand that words are separated by spaces in print.',331,'Lesson 4: Pictures & Words	331'),(5,'Begin to recognize some letters in the alphabet.	',343,'Lesson 1: Seashells & Pebbles, \nLesson 2: Letters in the Sand\n, Lesson 3: More Letters in the Sand, \nLesson 4: Letters A and M in the Sand'),(6,'Begin to identify letters in their name.	',343,'Lesson 1: Seashells & Pebbles, \n Lesson 2: Letters in the Sand\n , Lesson 3: More Letters in the Sand, \n Lesson 4: Letters A and M in the Sand'),(7,'Recognize the sounds of some of the letters in the alphabet.	',343,'Lesson 5: More Letters A and M'),(8,'Recognize spoken rhyming words.',361,'Lesson 1: A Rhyming Adventure, \nLesson 2: Rhyme Time in the Kitchen'),(9,'Recognize the letter O.',361,'Lesson 4: Letters O and T\n, Lesson 5: More Letters O and T, \nLesson 6: Playing with Letters'),(10,'Recognize the letter T',361,'Lesson 4: Letters O and T, \nLesson 5: More Letters O and T, \nLesson 6: Playing with Letters');

alter table lrn_activity add column `scale` int after lesson_group_id

--
-- Insert data for table `lrn_activity`
--

INSERT INTO `lrn_activity` VALUES (1,'Sample PreTest','Pre-K Unit 1 Literacy Pre Test',331,30,'2023-08-16 08:17:10',NULL);




