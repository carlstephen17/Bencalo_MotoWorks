-- Create missing tables without deleting existing application data.
CREATE TABLE IF NOT EXISTS `user` (
  `userId` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `userFirstName` VARCHAR(50) NOT NULL,
  `userLastName` VARCHAR(50) NOT NULL,
  `userEmail` VARCHAR(100) NOT NULL UNIQUE,
  `userPhone` VARCHAR(20) DEFAULT NULL,
  `userAddress` TEXT DEFAULT NULL,
  `userPassword` VARCHAR(255) NOT NULL,
  `userRole` ENUM('admin', 'staff', 'customer') NOT NULL DEFAULT 'customer',
  `userRememberToken` VARCHAR(255) DEFAULT NULL,
  `userResetToken` VARCHAR(255) DEFAULT NULL,
  `userResetExpiry` DATETIME DEFAULT NULL,
  `userAvatar` VARCHAR(255) DEFAULT NULL,
  `userCreatedAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- PRODUCT TABLE
CREATE TABLE IF NOT EXISTS `product` (
  `productId` INT(11) NOT NULL AUTO_INCREMENT,
  `productName` VARCHAR(255) NOT NULL,
  `productPrice` DECIMAL(10,2) NOT NULL,
  `productStock` INT(11) NOT NULL DEFAULT 0,
  `productImage` VARCHAR(255) DEFAULT NULL,
  `productActive` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`productId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CUSTOMER ORDER HEADER (ONE ROW PER CHECKOUT)
CREATE TABLE IF NOT EXISTS `customer_order` (
  `orderGroupId` INT(11) NOT NULL AUTO_INCREMENT,
  `userId` INT(11) NOT NULL,
  `orderDeliveryAddress` TEXT DEFAULT NULL,
  `orderPaymentMethod` VARCHAR(50) DEFAULT NULL,
  `orderPaymentReference` VARCHAR(32) DEFAULT NULL,
  `orderPaymentStatus` VARCHAR(32) NOT NULL DEFAULT 'Unverified',
  `orderStatus` VARCHAR(50) NOT NULL DEFAULT 'Pending',
  `orderCancellationReason` TEXT DEFAULT NULL,
  `orderCreatedByStaffId` INT(11) DEFAULT NULL,
  `orderCreatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`orderGroupId`),
  KEY `idx_customer_order_user` (`userId`),
  KEY `idx_customer_order_status` (`orderStatus`),
  KEY `idx_customer_order_date` (`orderCreatedAt`),
  KEY `idx_customer_order_staff_creator` (`orderCreatedByStaffId`),
  CONSTRAINT `fk_customer_order_user` FOREIGN KEY (`userId`) REFERENCES `user` (`userId`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_customer_order_staff_creator` FOREIGN KEY (`orderCreatedByStaffId`) REFERENCES `user` (`userId`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SERVICE TABLE
CREATE TABLE IF NOT EXISTS `service` (
  `serviceId` INT(11) NOT NULL AUTO_INCREMENT,
  `serviceName` VARCHAR(255) NOT NULL,
  `serviceImage` VARCHAR(255) DEFAULT NULL,
  `serviceDescription` TEXT DEFAULT NULL,
  `servicePrice` DECIMAL(10,2) NOT NULL,
  `serviceStatus` VARCHAR(50) DEFAULT 'Available',
  `serviceActive` TINYINT(1) DEFAULT 1,
  `serviceFeatured` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`serviceId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CUSTOMER MOTORCYCLE REGISTRY
CREATE TABLE IF NOT EXISTS `motorcycle` (
  `motorcycleId` INT(11) NOT NULL AUTO_INCREMENT,
  `userId` INT(11) NOT NULL,
  `motorcycleModel` VARCHAR(255) NOT NULL,
  `motorcyclePlateNumber` VARCHAR(32) DEFAULT NULL,
  `motorcycleCreatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`motorcycleId`),
  UNIQUE KEY `uq_motorcycle_plate_number` (`motorcyclePlateNumber`),
  KEY `idx_motorcycle_user` (`userId`),
  CONSTRAINT `fk_motorcycle_user` FOREIGN KEY (`userId`) REFERENCES `user` (`userId`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ORDER TABLE
CREATE TABLE IF NOT EXISTS `order` (
  `orderId` INT(11) NOT NULL AUTO_INCREMENT,
  `userId` INT(11) NOT NULL,
  `productId` INT(11) NOT NULL,
  `orderQuantity` INT(11) NOT NULL DEFAULT 1,
  `orderTotalAmount` DECIMAL(10,2) NOT NULL,
  `orderPaymentMethod` VARCHAR(50) DEFAULT NULL,
  `orderPaymentReference` VARCHAR(32) DEFAULT NULL,
  `orderStatus` VARCHAR(50) DEFAULT 'Pending',
  `orderCreatedAt` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `orderGroupId` INT(11) DEFAULT NULL,
  `orderUnitPrice` DECIMAL(10,2) DEFAULT NULL,
  PRIMARY KEY (`orderId`),
  KEY `fk_orders_user` (`userId`),
  KEY `fk_orders_product` (`productId`),
  KEY `idx_order_group` (`orderGroupId`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`userId`) REFERENCES `user` (`userId`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_orders_product` FOREIGN KEY (`productId`) REFERENCES `product` (`productId`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_order_group` FOREIGN KEY (`orderGroupId`) REFERENCES `customer_order` (`orderGroupId`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SERVICE BOOKING TABLE
CREATE TABLE IF NOT EXISTS `service_booking` (
  `bookingId` INT(11) NOT NULL AUTO_INCREMENT,
  `userId` INT(11) NOT NULL,
  `serviceId` INT(11) NOT NULL,
  `bookingVehicleModel` VARCHAR(255) DEFAULT NULL,
  `bookingDate` DATE NOT NULL,
  `bookingTime` TIME NOT NULL,
  `bookingBundleSlug` VARCHAR(100) DEFAULT NULL,
  `bookingBundleOption` VARCHAR(255) DEFAULT NULL,
  `bookingStatus` VARCHAR(50) DEFAULT 'Pending',
  `bookingNotes` TEXT DEFAULT NULL,
  `bookingRejectionReason` TEXT DEFAULT NULL,
  `bookingSource` VARCHAR(32) NOT NULL DEFAULT 'customer_online',
  `bookingMotorcycleId` INT(11) DEFAULT NULL,
  `createdByStaffId` INT(11) DEFAULT NULL,
  `bookingCreatedAt` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`bookingId`),
  KEY `fk_bookings_user` (`userId`),
  KEY `fk_bookings_service` (`serviceId`),
  KEY `fk_booking_staff_creator` (`createdByStaffId`),
  KEY `idx_booking_motorcycle` (`bookingMotorcycleId`),
  CONSTRAINT `fk_bookings_user` FOREIGN KEY (`userId`) REFERENCES `user` (`userId`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bookings_service` FOREIGN KEY (`serviceId`) REFERENCES `service` (`serviceId`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_booking_staff_creator` FOREIGN KEY (`createdByStaffId`) REFERENCES `user` (`userId`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_booking_motorcycle` FOREIGN KEY (`bookingMotorcycleId`) REFERENCES `motorcycle` (`motorcycleId`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- STAFF-CREATED SERVICE RECORDS (SEPARATE FROM APPOINTMENTS)
CREATE TABLE IF NOT EXISTS `service_record` (
  `recordId` INT(11) NOT NULL AUTO_INCREMENT,
  `customerId` INT(11) NOT NULL,
  `motorcycleId` INT(11) DEFAULT NULL,
  `serviceId` INT(11) NOT NULL,
  `createdByStaffId` INT(11) NOT NULL,
  `recordMotorcycleModel` VARCHAR(255) NOT NULL,
  `recordPlateNumber` VARCHAR(32) DEFAULT NULL,
  `recordServiceDate` DATE NOT NULL,
  `recordWorkDescription` TEXT NOT NULL,
  `recordPartsUsed` TEXT DEFAULT NULL,
  `recordCost` DECIMAL(10,2) DEFAULT NULL,
  `recordStatus` VARCHAR(50) NOT NULL DEFAULT 'In Progress',
  `recordNotes` TEXT DEFAULT NULL,
  `recordCreatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `recordUpdatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`recordId`),
  KEY `idx_service_record_customer` (`customerId`),
  KEY `idx_service_record_motorcycle` (`motorcycleId`),
  KEY `idx_service_record_service` (`serviceId`),
  KEY `idx_service_record_staff` (`createdByStaffId`),
  KEY `idx_service_record_status` (`recordStatus`),
  CONSTRAINT `fk_service_record_customer` FOREIGN KEY (`customerId`) REFERENCES `user` (`userId`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_service_record_motorcycle` FOREIGN KEY (`motorcycleId`) REFERENCES `motorcycle` (`motorcycleId`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_service_record_service` FOREIGN KEY (`serviceId`) REFERENCES `service` (`serviceId`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_service_record_staff` FOREIGN KEY (`createdByStaffId`) REFERENCES `user` (`userId`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- TESTIMONIAL TABLE
CREATE TABLE IF NOT EXISTS `testimonial` (
  `testimonialId` INT(11) NOT NULL AUTO_INCREMENT,
  `testimonialName` VARCHAR(255) NOT NULL,
  `testimonialEmail` VARCHAR(100) NOT NULL,
  `testimonialSubject` VARCHAR(255) NOT NULL,
  `testimonialMessage` TEXT NOT NULL,
  `testimonialStatus` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `testimonialImage` VARCHAR(255) DEFAULT NULL,
  `testimonialCreatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`testimonialId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- PROMO CLAIM TABLE
CREATE TABLE IF NOT EXISTS `promo_claim` (
  `claimId` INT(11) NOT NULL AUTO_INCREMENT,
  `userId` INT(11) DEFAULT NULL,
  `claimFullName` VARCHAR(255) NOT NULL,
  `claimPhone` VARCHAR(50) NOT NULL,
  `claimBundleSlug` VARCHAR(100) NOT NULL,
  `claimSelectedOption` VARCHAR(255) NOT NULL,
  `claimAppointmentDate` DATE NOT NULL,
  `claimAppointmentTime` TIME NOT NULL,
  `claimStatus` VARCHAR(50) DEFAULT 'Pending',
  `claimCreatedByStaffId` INT(11) DEFAULT NULL,
  `claimCreatedAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`claimId`),
  KEY `fk_promo_user` (`userId`),
  KEY `idx_promo_claim_staff_creator` (`claimCreatedByStaffId`),
  CONSTRAINT `fk_promo_user` FOREIGN KEY (`userId`) REFERENCES `user` (`userId`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_promo_claim_staff_creator` FOREIGN KEY (`claimCreatedByStaffId`) REFERENCES `user` (`userId`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- PROMO CATALOG (kept separate from customer promo_claim history)
CREATE TABLE IF NOT EXISTS `promo` (
  `promoId` INT(11) NOT NULL AUTO_INCREMENT,
  `promoSlug` VARCHAR(100) NOT NULL,
  `promoTitle` VARCHAR(255) NOT NULL,
  `promoBadge` VARCHAR(50) NOT NULL,
  `promoDescription` TEXT NOT NULL,
  `promoItemLabel` VARCHAR(255) NOT NULL,
  `promoItemOptions` TEXT NOT NULL,
  `promoPerks` TEXT DEFAULT NULL,
  `promoProductImage` VARCHAR(255) DEFAULT NULL,
  `promoServiceImage` VARCHAR(255) DEFAULT NULL,
  `promoProductIcon` VARCHAR(100) DEFAULT NULL,
  `promoServiceIcon` VARCHAR(100) DEFAULT NULL,
  `promoActive` TINYINT(1) NOT NULL DEFAULT 1,
  `promoCreatedByStaffId` INT(11) DEFAULT NULL,
  `promoCreatedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`promoId`),
  UNIQUE KEY `uq_promo_slug` (`promoSlug`),
  KEY `idx_promo_created_by_staff` (`promoCreatedByStaffId`),
  CONSTRAINT `fk_promo_created_by_staff` FOREIGN KEY (`promoCreatedByStaffId`)
    REFERENCES `user` (`userId`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `promo`
  (`promoId`, `promoSlug`, `promoTitle`, `promoBadge`, `promoDescription`, `promoItemLabel`,
   `promoItemOptions`, `promoPerks`, `promoProductImage`, `promoServiceImage`,
   `promoProductIcon`, `promoServiceIcon`, `promoActive`)
VALUES
  (1, 'tire_service', 'Tire + Mounting Service Bundle', '15% OFF',
   'Purchase any eligible motorcycle tire and pair it with a professional mounting, balancing, and alignment service at the same time.',
   'Select Tire Model / Size',
   'Michelin Pilot Street 2 (90/80-14 Tubeless)\nPirelli Diablo Rosso Sport (100/80-14 Tubeless)\nIRC NR73 Tubeless Tire (80/90-17)',
   'Eligible on all standard and tubeless tires\nIncludes labor and wheel balancing adjustment',
   'images/featured_products/tire.webp', 'images/featured_services/wheel_and_tire_service.png',
   'fa-solid fa-circle-dot', 'fa-solid fa-screwdriver-wrench', 1),
  (2, 'oil_tuneup', 'Synthetic Oil Change + Tune-Up', '20% OFF',
   'Keep your engine running smoothly with high-grade synthetic oil paired with a complete carb/FI diagnostic tune-up service.',
   'Select Oil Viscosity & Type',
   'Motul 7100 4T 10W-40 Synthetic (1L)\nShell Advance Ultra 10W-40 Fully Synthetic (1L)\nCastrol Power1 Ultimate 10W-50 (1L)',
   'Includes 1L high-performance synthetic oil\nFull engine diagnostic & idle tuning',
   'images/featured_products/synthetic_oil.png', 'images/featured_services/premium_oil_and_change_service.png',
   'fa-solid fa-oil-can', 'fa-solid fa-gauge-high', 1),
  (3, 'chain_sprocket', 'Heavy Duty Chain Kit + Install', '10% OFF',
   'Upgrade your drive train with a heavy-duty chain and sprocket set bundled together with precision professional installation.',
   'Select Chain & Sprocket Set',
   'SSS Heavy Duty Chain & Sprocket Set (428H x 14T/38T)\nDID O-Ring Heavy Duty Chain Combo (520V)\nRK Racing Gold Chain & Steel Sprocket Kit',
   'O-ring or X-ring heavy duty durability\nIncludes chain cleaning and tension adjustment',
   'images/products/chain.png', 'images/services/chain.png',
   'fa-solid fa-link', 'fa-solid fa-gear', 1),
  (4, 'brake_overhaul', 'Brake Pad Set + Fluid Bleeding', '15% OFF',
   'Ensure maximum stopping power by bundling front/rear ceramic brake pads with a complete hydraulic fluid flush and bleed service.',
   'Select Brake Pad Compound',
   'Brembo Ceramic Front/Rear Brake Pads\nEBC FA Series Sintered Brake Pads\nBendix Heavy Duty Street Moto Pads',
   'High-friction ceramic compound pads\nComplete DOT-4 brake fluid replacement',
   'images/products/brake.png', 'images/services/brake.png',
   'fa-solid fa-compact-disc', 'fa-solid fa-droplet', 1);