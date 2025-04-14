-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 14, 2025 at 09:38 PM
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
-- Database: `coupon`
--

-- --------------------------------------------------------

--
-- Table structure for table `bxgy_products`
--

CREATE TABLE `bxgy_products` (
  `id` int(11) NOT NULL,
  `coupon_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `type` enum('buy','get') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `bxgy_products`
--

INSERT INTO `bxgy_products` (`id`, `coupon_id`, `product_id`, `type`) VALUES
(6, 2, 101, 'buy'),
(7, 2, 201, 'get'),
(8, 2, 203, 'get');

-- --------------------------------------------------------

--
-- Table structure for table `bxgy_rules`
--

CREATE TABLE `bxgy_rules` (
  `id` int(11) NOT NULL,
  `coupon_id` int(11) NOT NULL,
  `repetition_limit` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `bxgy_rules`
--

INSERT INTO `bxgy_rules` (`id`, `coupon_id`, `repetition_limit`) VALUES
(2, 2, 3);

-- --------------------------------------------------------

--
-- Table structure for table `cart_wise_coupons`
--

CREATE TABLE `cart_wise_coupons` (
  `id` int(11) NOT NULL,
  `coupon_id` int(11) NOT NULL,
  `min_amount` decimal(10,2) NOT NULL COMMENT 'Minimum cart value to apply',
  `discount_type` enum('amount','percentage') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `max_discount` decimal(10,2) DEFAULT NULL COMMENT 'For percentage caps'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `cart_wise_coupons`
--

INSERT INTO `cart_wise_coupons` (`id`, `coupon_id`, `min_amount`, `discount_type`, `discount_value`, `max_discount`) VALUES
(2, 1, 100.00, 'percentage', 10.00, 20.00),
(1, 2, 25.00, 'amount', 5.99, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `type` enum('cart-wise','product-wise','bxgy') NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `max_uses` int(11) DEFAULT NULL,
  `uses_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `coupons`
--

INSERT INTO `coupons` (`id`, `code`, `type`, `description`, `is_active`, `start_date`, `end_date`, `max_uses`, `uses_count`, `created_at`) VALUES
(1, 'SUMMER203', 'cart-wise', 'SUMMERMODADS-MODS-JKDSD', 1, '2025-04-01 00:00:00', '2025-06-30 00:00:00', 8, 0, '2025-04-14 14:52:04'),
(2, 'GROUPBON5', 'bxgy', 'Buy from group 1, get items from group 2 free', 1, '2025-04-13 00:00:00', '2025-05-01 23:59:59', 200, 0, '2025-04-14 14:53:04'),
(5, '30OFFSHOES', 'product-wise', '30% off all shoes', 1, '2025-04-07 00:00:00', '2025-12-17 00:00:00', 1000, 0, '2025-04-14 14:53:45');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_wise_coupons`
--

CREATE TABLE `product_wise_coupons` (
  `id` int(11) NOT NULL,
  `coupon_id` int(11) NOT NULL,
  `discount_type` enum('amount','percentage') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `products` text NOT NULL,
  `max_uses_per_product` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `product_wise_coupons`
--

INSERT INTO `product_wise_coupons` (`id`, `coupon_id`, `discount_type`, `discount_value`, `products`, `max_uses_per_product`) VALUES
(1, 5, 'percentage', 30.00, '[123,456,789]', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bxgy_products`
--
ALTER TABLE `bxgy_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `coupon_id` (`coupon_id`);

--
-- Indexes for table `bxgy_rules`
--
ALTER TABLE `bxgy_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_coupon_buy` (`coupon_id`),
  ADD KEY `idx_coupon_get` (`coupon_id`);

--
-- Indexes for table `cart_wise_coupons`
--
ALTER TABLE `cart_wise_coupons`
  ADD PRIMARY KEY (`coupon_id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_active_dates` (`is_active`,`start_date`,`end_date`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_wise_coupons`
--
ALTER TABLE `product_wise_coupons`
  ADD PRIMARY KEY (`coupon_id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bxgy_products`
--
ALTER TABLE `bxgy_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `bxgy_rules`
--
ALTER TABLE `bxgy_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cart_wise_coupons`
--
ALTER TABLE `cart_wise_coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_wise_coupons`
--
ALTER TABLE `product_wise_coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bxgy_products`
--
ALTER TABLE `bxgy_products`
  ADD CONSTRAINT `bxgy_products_ibfk_1` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bxgy_rules`
--
ALTER TABLE `bxgy_rules`
  ADD CONSTRAINT `bxgy_rules_ibfk_1` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_wise_coupons`
--
ALTER TABLE `cart_wise_coupons`
  ADD CONSTRAINT `cart_wise_coupons_ibfk_1` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_wise_coupons`
--
ALTER TABLE `product_wise_coupons`
  ADD CONSTRAINT `product_wise_coupons_ibfk_1` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
