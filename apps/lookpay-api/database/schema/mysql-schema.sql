/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `establishments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `establishments` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT uuid(),
  `password` char(97) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` char(26) COLLATE utf8mb4_unicode_ci NOT NULL,
  `iugu_token_live` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_number` char(11) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fees` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`fees`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `establishments_token_unique` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `financial_statements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `financial_statements` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT uuid(),
  `establishment_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` int(11) NOT NULL,
  `type` enum('ADD_CREDIT') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_synced_with_mobilestock` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `financial_statements_establishment_id_foreign` (`establishment_id`),
  CONSTRAINT `financial_statements_establishment_id_foreign` FOREIGN KEY (`establishment_id`) REFERENCES `establishments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoices` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT uuid(),
  `establishment_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_method` enum('CREDIT_CARD') COLLATE utf8mb4_unicode_ci NOT NULL,
  `installments` tinyint(2) DEFAULT NULL,
  `amount` int(11) NOT NULL,
  `fee` int(11) NOT NULL,
  `payment_provider_invoice_id` char(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `establishment_order_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('PAID') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_establishment_order_id_unique` (`establishment_order_id`),
  KEY `invoices_establishment_id_foreign` (`establishment_id`),
  CONSTRAINT `invoices_establishment_id_foreign` FOREIGN KEY (`establishment_id`) REFERENCES `establishments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoices_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoices_items` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT uuid(),
  `invoice_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('ADD_CREDIT') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `invoices_items_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `invoices_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoices_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoices_logs` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT uuid(),
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `iugu_credit_card_error_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `iugu_credit_card_error_messages` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT uuid(),
  `lr_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recommended_action` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mobilestock_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mobilestock_users` (
  `establishment_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contributor_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`establishment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2024_01_17_180046_create_establishments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2024_01_17_180047_create_invoices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2024_01_17_180844_create_invoices_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2024_01_17_181731_create_financial_statements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2024_01_18_090116_create_invoices_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2024_01_18_090919_create_mobilestock_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2024_03_04_092050_iugu_credit_card_error_messages',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2024_04_02_094331_update_establishments_table',1);
