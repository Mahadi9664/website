-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 23, 2025 at 10:57 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `aamm`
--

-- --------------------------------------------------------

--
-- Table structure for table `auditlog`
--

CREATE TABLE `auditlog` (
  `LogID` int(11) NOT NULL,
  `TableName` varchar(50) NOT NULL,
  `RecordID` int(11) NOT NULL,
  `ActionType` enum('INSERT','UPDATE','DELETE') NOT NULL,
  `ActionTimestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `UserID` int(11) DEFAULT NULL,
  `OldData` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`OldData`)),
  `NewData` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`NewData`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `auditlog`
--

INSERT INTO `auditlog` (`LogID`, `TableName`, `RecordID`, `ActionType`, `ActionTimestamp`, `UserID`, `OldData`, `NewData`) VALUES
(1, 'Restaurant', 1, 'UPDATE', '2025-03-16 10:34:13', 3, '{\"Name\": \"Pizza Palace\", \"Location\": \"POINT(10 20)\"}', '{\"Name\": \"Pizza Palace\", \"Location\": \"POINT(10 20)\"}'),
(2, 'Restaurant', 1, 'UPDATE', '2025-03-16 10:34:13', 3, '{\"Name\": \"Pizza Palace\", \"Location\": \"POINT(10 20)\"}', '{\"Name\": \"Pizza Palace\", \"Location\": \"POINT(10 20)\"}'),
(3, 'Restaurant', 2, 'UPDATE', '2025-03-16 10:34:13', 3, '{\"Name\": \"Burger Barn\", \"Location\": \"POINT(15 25)\"}', '{\"Name\": \"Burger Barn\", \"Location\": \"POINT(15 25)\"}'),
(4, 'Restaurant', 3, 'UPDATE', '2025-03-16 10:34:13', 3, '{\"Name\": \"Sushi Spot\", \"Location\": \"POINT(20 30)\"}', '{\"Name\": \"Sushi Spot\", \"Location\": \"POINT(20 30)\"}'),
(5, 'Restaurant', 1, 'UPDATE', '2025-03-16 10:34:13', 3, '{\"Name\": \"Pizza Palace\"}', '{\"Name\": \"Pizza Haven\"}'),
(6, 'UserProfile', 1, 'UPDATE', '2025-03-16 10:34:13', 1, '{\"Email\": \"john@example.com\"}', '{\"Email\": \"john.doe@example.com\"}');

-- --------------------------------------------------------

--
-- Table structure for table `commentmetadata`
--

CREATE TABLE `commentmetadata` (
  `CommentID` int(11) NOT NULL,
  `ReviewID` int(11) DEFAULT NULL,
  `UserID` int(11) DEFAULT NULL,
  `CommentDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `CommentText` text DEFAULT NULL,
  `IsDeleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `commentmetadata`
--

INSERT INTO `commentmetadata` (`CommentID`, `ReviewID`, `UserID`, `CommentDate`, `CommentText`, `IsDeleted`) VALUES
(1, 1, 2, '2025-03-16 10:34:13', 'I agree, the pizza is amazing!', 0),
(2, 2, 1, '2025-03-16 10:34:13', 'The service was indeed great.', 0),
(3, 4, 1, '2025-03-16 10:34:13', 'I love their sushi too!', 0),
(4, 5, 4, '2025-04-23 05:35:26', 'nice', 0),
(5, 7, 4, '2025-04-23 05:57:45', 'ik', 0);

-- --------------------------------------------------------

--
-- Table structure for table `cuisine`
--

CREATE TABLE `cuisine` (
  `CuisineID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cuisine`
--

INSERT INTO `cuisine` (`CuisineID`, `Name`) VALUES
(2, 'American'),
(5, 'Chinese'),
(7, 'Indian'),
(1, 'Italian'),
(3, 'Japanese'),
(4, 'Mexican'),
(8, 'Middle Eastern'),
(6, 'Pakistani');

-- --------------------------------------------------------

--
-- Table structure for table `food`
--

CREATE TABLE `food` (
  `FoodID` int(11) NOT NULL,
  `RestaurantID` int(11) DEFAULT NULL,
  `Name` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `Price` decimal(10,2) DEFAULT NULL,
  `CuisineID` int(11) DEFAULT NULL,
  `IsDeleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `food`
--

INSERT INTO `food` (`FoodID`, `RestaurantID`, `Name`, `Description`, `Price`, `CuisineID`, `IsDeleted`) VALUES
(1, 1, 'Margherita Pizza', 'Classic pizza with tomato, mozzarella, and basil', 10.99, 1, 0),
(2, 1, 'Pepperoni Pizza', 'Pizza with pepperoni and cheese', 12.99, 1, 0),
(3, 2, 'Cheeseburger', 'Classic cheeseburger with lettuce, tomato, and onion', 8.99, 2, 0),
(4, 2, 'Bacon Burger', 'Burger with bacon and cheese', 10.99, 2, 0),
(5, 3, 'Sushi Roll', 'Assorted sushi rolls', 15.99, 3, 0),
(6, 3, 'Sashimi Platter', 'Fresh sashimi slices', 20.99, 3, 0),
(7, 4, 'Sayma\'s nehari', 'nehari nehari', 6.70, 6, 0),
(8, 4, 'asif\'s butter chicken', 'makhan makhan', 6.00, 6, 0),
(9, 4, 'Mahina\'s nachos', 'nacho nacho ', 6.80, 6, 0),
(10, 4, 'Lamia\'s buritto', 'boruto', 7.80, 6, 0);

-- --------------------------------------------------------

--
-- Table structure for table `likedby`
--

CREATE TABLE `likedby` (
  `LikeID` int(11) NOT NULL,
  `ReviewID` int(11) DEFAULT NULL,
  `UserID` int(11) DEFAULT NULL,
  `LikeDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `IsDeleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `likedby`
--

INSERT INTO `likedby` (`LikeID`, `ReviewID`, `UserID`, `LikeDate`, `IsDeleted`) VALUES
(1, 1, 2, '2025-03-16 10:34:13', 0),
(2, 2, 1, '2025-03-16 10:34:13', 0),
(3, 4, 1, '2025-03-16 10:34:13', 0),
(4, 5, 4, '2025-04-23 05:36:10', 0),
(5, 4, 4, '2025-04-23 05:38:26', 0),
(6, 7, 4, '2025-04-23 05:54:27', 0),
(7, 1, 4, '2025-04-23 08:53:16', 0);

-- --------------------------------------------------------

--
-- Table structure for table `restaurant`
--

CREATE TABLE `restaurant` (
  `RestaurantID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Location` text DEFAULT NULL,
  `OpeningHours` varchar(100) DEFAULT NULL,
  `CuisineType` varchar(100) DEFAULT NULL,
  `AverageRating` decimal(3,2) DEFAULT 0.00,
  `AdminID` int(11) DEFAULT NULL,
  `IsDeleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `restaurant`
--

INSERT INTO `restaurant` (`RestaurantID`, `Name`, `Location`, `OpeningHours`, `CuisineType`, `AverageRating`, `AdminID`, `IsDeleted`) VALUES
(1, 'Pizza Palace', 'uttara section 11', '9 AM - 10 PM', 'Italian', 4.50, 3, 0),
(2, 'Burger Barn', 'banani', '10 AM - 11 PM', 'American', 3.80, 3, 0),
(3, 'Sushi Spot', 'gulshan 2', '11 AM - 9 PM', 'Japanese', 4.50, 3, 0),
(4, 'Pookie Hotel', 'pallabi, mirpur', '6AM - 10PM', 'Pakistani', 5.00, 6, 0);

-- --------------------------------------------------------

--
-- Table structure for table `restaurantnumber`
--

CREATE TABLE `restaurantnumber` (
  `NumberID` int(11) NOT NULL,
  `RestaurantID` int(11) DEFAULT NULL,
  `PhoneNumber` varchar(15) DEFAULT NULL,
  `IsDeleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `restaurantnumber`
--

INSERT INTO `restaurantnumber` (`NumberID`, `RestaurantID`, `PhoneNumber`, `IsDeleted`) VALUES
(1, 1, '123-456-7890', 0),
(2, 2, '987-654-3210', 0),
(3, 3, '555-555-5555', 0),
(5, 4, '01122345651', 0);

-- --------------------------------------------------------

--
-- Table structure for table `reviewmetabase`
--

CREATE TABLE `reviewmetabase` (
  `ReviewID` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `RestaurantID` int(11) DEFAULT NULL,
  `ReviewDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `Rating` int(11) DEFAULT NULL CHECK (`Rating` between 1 and 5),
  `ReviewText` text DEFAULT NULL,
  `IsDeleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviewmetabase`
--

INSERT INTO `reviewmetabase` (`ReviewID`, `UserID`, `RestaurantID`, `ReviewDate`, `Rating`, `ReviewText`, `IsDeleted`) VALUES
(1, 1, 1, '2025-03-16 10:34:13', 5, 'Amazing pizza!', 0),
(2, 2, 1, '2025-03-16 10:34:13', 4, 'Great service and delicious food.', 0),
(3, 1, 2, '2025-03-16 10:34:13', 3, 'Good burgers, but a bit pricey.', 0),
(4, 2, 3, '2025-03-16 10:34:13', 5, 'Best sushi in town!', 0),
(5, 4, 2, '2025-04-09 09:22:38', 5, 'great place.', 0),
(6, 4, 4, '2025-04-11 04:20:25', 5, 'really great place', 0),
(7, 4, 3, '2025-04-23 05:54:15', 4, 'nice spot', 0),
(8, 4, 2, '2025-04-23 08:36:03', 4, 'nice', 0),
(9, 4, 2, '2025-04-23 08:36:28', 3, 'f', 0),
(10, 4, 2, '2025-04-23 08:39:54', 4, 'ff', 0),
(11, 4, 4, '2025-04-23 08:45:57', 5, 'perfect', 0);

--
-- Triggers `reviewmetabase`
--
DELIMITER $$
CREATE TRIGGER `UpdateAverageRating` AFTER INSERT ON `reviewmetabase` FOR EACH ROW BEGIN
    DECLARE avgRating DECIMAL(3, 2);

    -- Calculate the new average rating
    SELECT AVG(Rating) INTO avgRating
    FROM ReviewMetabase
    WHERE RestaurantID = NEW.RestaurantID;

    -- Update the Restaurant table
    UPDATE Restaurant
    SET AverageRating = avgRating
    WHERE RestaurantID = NEW.RestaurantID;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `review_photos`
--

CREATE TABLE `review_photos` (
  `PhotoID` int(11) NOT NULL,
  `ReviewID` int(11) NOT NULL,
  `FilePath` varchar(255) NOT NULL,
  `UploadDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `IsDeleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `review_photos`
--

INSERT INTO `review_photos` (`PhotoID`, `ReviewID`, `FilePath`, `UploadDate`, `IsDeleted`) VALUES
(1, 10, 'uploads/review_images/6808a75a159b5_434042268_1357082074956056_1429951481969090495_n (2).jpg', '2025-04-23 08:39:54', 0),
(2, 11, 'uploads/review_images/6808a8c517f17_image (2).jpg', '2025-04-23 08:45:57', 0);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `RoleID` int(11) NOT NULL,
  `RoleName` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`RoleID`, `RoleName`) VALUES
(1, 'Admin'),
(3, 'Restaurant Owner'),
(2, 'User');

-- --------------------------------------------------------

--
-- Table structure for table `usercredentials`
--

CREATE TABLE `usercredentials` (
  `UserID` int(11) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `IsDeleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `usercredentials`
--

INSERT INTO `usercredentials` (`UserID`, `Username`, `PasswordHash`, `CreatedAt`, `IsDeleted`) VALUES
(1, 'john_doe', 'hashed_password_123', '2025-03-16 10:34:13', 0),
(2, 'jane_smith', 'hashed_password_456', '2025-03-16 10:34:13', 0),
(3, 'admin_user', 'hashed_password_admin', '2025-03-16 10:34:13', 0),
(4, 'mahadi', '$2y$10$9YgOh6/NIw7OSxJDWHYTB.OxLaS8yO6TZl6AIwgEpc5Myqo/QMzWe', '2025-04-09 09:21:53', 0),
(5, 'PookieHotel', '$2y$10$zP2GlbJ1zFBniWtCr1Adle/aJI9uhiUOF19mBsWz6iyzB5o5VL0ze', '2025-04-09 09:25:04', 0),
(6, 'Pookie', '$2y$10$pFLxA36y7TvBw9MbQlN3ge.RXD0BUk3rekgprvRQbIovVhSlvFh9y', '2025-04-09 09:32:30', 0),
(7, 'jonny', '$2y$10$ovxoQsj5lgwh.ZvnDVOPGO3w17iNTAX0mSDVE0gtN1GS0pfuCvlu2', '2025-04-11 08:53:32', 0);

-- --------------------------------------------------------

--
-- Table structure for table `userprofile`
--

CREATE TABLE `userprofile` (
  `ProfileID` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `FullName` varchar(100) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `ProfilePictureURL` varchar(255) DEFAULT NULL,
  `IsDeleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `userprofile`
--

INSERT INTO `userprofile` (`ProfileID`, `UserID`, `FullName`, `Email`, `ProfilePictureURL`, `IsDeleted`) VALUES
(1, 1, 'John Doe', 'john@example.com', 'https://example.com/john.jpg', 0),
(2, 2, 'Jane Smith', 'jane@example.com', 'https://example.com/jane.jpg', 0),
(3, 3, 'Admin User', 'admin@example.com', 'https://example.com/admin.jpg', 0),
(4, 4, 'Moniruzzaman Mahadi', 'moniruzzamanmahadi911@gmail.com', 'uploads/profile_pics/user_4_1745385205.jpg', 0),
(5, 5, 'Pookie Hotel', 'pookiehotel@gmail.com', NULL, 0),
(7, 7, 'Jonny sakib', 'jonnysakib@gmail.com', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `userroles`
--

CREATE TABLE `userroles` (
  `UserID` int(11) NOT NULL,
  `RoleID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `userroles`
--

INSERT INTO `userroles` (`UserID`, `RoleID`) VALUES
(1, 2),
(2, 2),
(3, 1),
(4, 2),
(5, 3),
(6, 3),
(7, 2);

--
-- Triggers `userroles`
--
DELIMITER $$
CREATE TRIGGER `PreventLastAdminDeletion` BEFORE DELETE ON `userroles` FOR EACH ROW BEGIN
    DECLARE adminCount INT;

    -- Count the number of admins for the restaurant
    SELECT COUNT(*) INTO adminCount
    FROM UserRoles
    WHERE RoleID = (SELECT RoleID FROM Roles WHERE RoleName = 'Admin')
      AND UserID IN (SELECT AdminID FROM Restaurant WHERE AdminID = OLD.UserID);

    -- If this is the last admin, prevent deletion
    IF adminCount <= 1 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot delete the last admin of a restaurant';
    END IF;
END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `auditlog`
--
ALTER TABLE `auditlog`
  ADD PRIMARY KEY (`LogID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `commentmetadata`
--
ALTER TABLE `commentmetadata`
  ADD PRIMARY KEY (`CommentID`),
  ADD KEY `idx_comment_reviewid` (`ReviewID`),
  ADD KEY `idx_comment_userid` (`UserID`);

--
-- Indexes for table `cuisine`
--
ALTER TABLE `cuisine`
  ADD PRIMARY KEY (`CuisineID`),
  ADD UNIQUE KEY `Name` (`Name`);

--
-- Indexes for table `food`
--
ALTER TABLE `food`
  ADD PRIMARY KEY (`FoodID`),
  ADD KEY `idx_food_restaurantid` (`RestaurantID`),
  ADD KEY `idx_food_cuisineid` (`CuisineID`);

--
-- Indexes for table `likedby`
--
ALTER TABLE `likedby`
  ADD PRIMARY KEY (`LikeID`),
  ADD UNIQUE KEY `ReviewID` (`ReviewID`,`UserID`),
  ADD KEY `idx_likedby_reviewid` (`ReviewID`),
  ADD KEY `idx_likedby_userid` (`UserID`);

--
-- Indexes for table `restaurant`
--
ALTER TABLE `restaurant`
  ADD PRIMARY KEY (`RestaurantID`),
  ADD KEY `idx_restaurant_adminid` (`AdminID`);

--
-- Indexes for table `restaurantnumber`
--
ALTER TABLE `restaurantnumber`
  ADD PRIMARY KEY (`NumberID`),
  ADD KEY `idx_restaurantnumber_restaurantid` (`RestaurantID`);

--
-- Indexes for table `reviewmetabase`
--
ALTER TABLE `reviewmetabase`
  ADD PRIMARY KEY (`ReviewID`),
  ADD KEY `idx_review_userid` (`UserID`),
  ADD KEY `idx_review_restaurantid` (`RestaurantID`);

--
-- Indexes for table `review_photos`
--
ALTER TABLE `review_photos`
  ADD PRIMARY KEY (`PhotoID`),
  ADD KEY `ReviewID` (`ReviewID`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`RoleID`),
  ADD UNIQUE KEY `RoleName` (`RoleName`);

--
-- Indexes for table `usercredentials`
--
ALTER TABLE `usercredentials`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Username` (`Username`);

--
-- Indexes for table `userprofile`
--
ALTER TABLE `userprofile`
  ADD PRIMARY KEY (`ProfileID`),
  ADD UNIQUE KEY `UserID` (`UserID`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD KEY `idx_user_profile_userid` (`UserID`);

--
-- Indexes for table `userroles`
--
ALTER TABLE `userroles`
  ADD PRIMARY KEY (`UserID`,`RoleID`),
  ADD KEY `RoleID` (`RoleID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `auditlog`
--
ALTER TABLE `auditlog`
  MODIFY `LogID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `commentmetadata`
--
ALTER TABLE `commentmetadata`
  MODIFY `CommentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `cuisine`
--
ALTER TABLE `cuisine`
  MODIFY `CuisineID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `food`
--
ALTER TABLE `food`
  MODIFY `FoodID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `likedby`
--
ALTER TABLE `likedby`
  MODIFY `LikeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `restaurant`
--
ALTER TABLE `restaurant`
  MODIFY `RestaurantID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `restaurantnumber`
--
ALTER TABLE `restaurantnumber`
  MODIFY `NumberID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reviewmetabase`
--
ALTER TABLE `reviewmetabase`
  MODIFY `ReviewID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `review_photos`
--
ALTER TABLE `review_photos`
  MODIFY `PhotoID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `RoleID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `usercredentials`
--
ALTER TABLE `usercredentials`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `userprofile`
--
ALTER TABLE `userprofile`
  MODIFY `ProfileID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `auditlog`
--
ALTER TABLE `auditlog`
  ADD CONSTRAINT `auditlog_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `usercredentials` (`UserID`) ON DELETE SET NULL;

--
-- Constraints for table `commentmetadata`
--
ALTER TABLE `commentmetadata`
  ADD CONSTRAINT `commentmetadata_ibfk_1` FOREIGN KEY (`ReviewID`) REFERENCES `reviewmetabase` (`ReviewID`) ON DELETE CASCADE,
  ADD CONSTRAINT `commentmetadata_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `usercredentials` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `food`
--
ALTER TABLE `food`
  ADD CONSTRAINT `food_ibfk_1` FOREIGN KEY (`RestaurantID`) REFERENCES `restaurant` (`RestaurantID`) ON DELETE CASCADE,
  ADD CONSTRAINT `food_ibfk_2` FOREIGN KEY (`CuisineID`) REFERENCES `cuisine` (`CuisineID`) ON DELETE SET NULL;

--
-- Constraints for table `likedby`
--
ALTER TABLE `likedby`
  ADD CONSTRAINT `likedby_ibfk_1` FOREIGN KEY (`ReviewID`) REFERENCES `reviewmetabase` (`ReviewID`) ON DELETE CASCADE,
  ADD CONSTRAINT `likedby_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `usercredentials` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `restaurant`
--
ALTER TABLE `restaurant`
  ADD CONSTRAINT `restaurant_ibfk_1` FOREIGN KEY (`AdminID`) REFERENCES `usercredentials` (`UserID`) ON DELETE SET NULL;

--
-- Constraints for table `restaurantnumber`
--
ALTER TABLE `restaurantnumber`
  ADD CONSTRAINT `restaurantnumber_ibfk_1` FOREIGN KEY (`RestaurantID`) REFERENCES `restaurant` (`RestaurantID`) ON DELETE CASCADE;

--
-- Constraints for table `reviewmetabase`
--
ALTER TABLE `reviewmetabase`
  ADD CONSTRAINT `reviewmetabase_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `usercredentials` (`UserID`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviewmetabase_ibfk_2` FOREIGN KEY (`RestaurantID`) REFERENCES `restaurant` (`RestaurantID`) ON DELETE CASCADE;

--
-- Constraints for table `review_photos`
--
ALTER TABLE `review_photos`
  ADD CONSTRAINT `review_photos_ibfk_1` FOREIGN KEY (`ReviewID`) REFERENCES `reviewmetabase` (`ReviewID`);

--
-- Constraints for table `userprofile`
--
ALTER TABLE `userprofile`
  ADD CONSTRAINT `userprofile_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `usercredentials` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `userroles`
--
ALTER TABLE `userroles`
  ADD CONSTRAINT `userroles_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `usercredentials` (`UserID`) ON DELETE CASCADE,
  ADD CONSTRAINT `userroles_ibfk_2` FOREIGN KEY (`RoleID`) REFERENCES `roles` (`RoleID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
