/*
Navicat MariaDB Data Transfer

Source Server         : localmariadb
Source Server Version : 100108
Source Host           : localhost:3306
Source Database       : bod_core

Target Server Type    : MariaDB
Target Server Version : 100108
File Encoding         : 65001

Date: 2015-12-25 22:14:51
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for active_product_category_locale
-- ----------------------------
DROP TABLE IF EXISTS `active_product_category_locale`;
CREATE TABLE `active_product_category_locale` (
  `category` int(10) unsigned NOT NULL COMMENT 'selected product.',
  `locale` int(5) unsigned NOT NULL COMMENT 'Locale that product will be shown in',
  PRIMARY KEY (`category`,`locale`),
  UNIQUE KEY `idxUActiveLocaleOfProductCategory` (`category`,`locale`) USING BTREE,
  KEY `idxFActiveLanguageOfProductCategory` (`locale`) USING BTREE,
  CONSTRAINT `idxFLocaleOfActiveProductCategoryLocale` FOREIGN KEY (`locale`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `idxFCategoryOfActiveProductCategoryLocale` FOREIGN KEY (`category`) REFERENCES `product_category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- ----------------------------
-- Table structure for active_product_locale
-- ----------------------------
DROP TABLE IF EXISTS `active_product_locale`;
CREATE TABLE `active_product_locale` (
  `product` int(15) unsigned NOT NULL COMMENT 'selected product.',
  `locale` int(5) unsigned NOT NULL COMMENT 'Locale that product will be shown in',
  PRIMARY KEY (`product`,`locale`),
  UNIQUE KEY `idxUActiveLocaleOfProduct` (`product`,`locale`) USING BTREE,
  KEY `idxFActiveLanguageOfProduct` (`locale`) USING BTREE,
  CONSTRAINT `idxFLocaleOfActiveProductLocale` FOREIGN KEY (`locale`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `idxFProductOfActiveProductLocale` FOREIGN KEY (`product`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- ----------------------------
-- Table structure for attributes_of_product
-- ----------------------------
DROP TABLE IF EXISTS `attributes_of_product`;
CREATE TABLE `attributes_of_product` (
  `product` int(10) unsigned NOT NULL COMMENT 'Product that is associated with attribute.',
  `attribute` int(10) unsigned NOT NULL COMMENT 'Attriubute that is associated with product.',
  `sort_order` int(11) NOT NULL DEFAULT '1' COMMENT 'Custom sort order.',
  `date_added` datetime NOT NULL COMMENT 'Date when attribute is attached to product.',
  `price_factor` decimal(5,2) DEFAULT NULL COMMENT 'If attribute changes price, by what percentage?',
  `price_factor_type` char(1) COLLATE utf8_turkish_ci DEFAULT 'a' COMMENT 'a:amount;p:percentage.',
  PRIMARY KEY (`product`,`attribute`),
  UNIQUE KEY `idxUProductOfAttributesOfProduct` (`product`,`attribute`) USING BTREE,
  KEY `idxNAttributeOfAttributesOfProduct` (`attribute`) USING BTREE,
  KEY `idxNProductOfAttributesOfProduct` (`product`) USING BTREE,
  CONSTRAINT `idxFAttributeOfAttributesOfProduct` FOREIGN KEY (`attribute`) REFERENCES `product_attribute` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `idxFProductOfAttributesOfProduct` FOREIGN KEY (`product`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci ROW_FORMAT=COMPACT;

-- ----------------------------
-- Table structure for attributes_of_product_category
-- ----------------------------
DROP TABLE IF EXISTS `attributes_of_product_category`;
CREATE TABLE `attributes_of_product_category` (
  `attribute` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Attribute that is associated with category.',
  `category` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Category that attribute is associated to.',
  `sort_order` int(10) unsigned NOT NULL DEFAULT '1' COMMENT 'Custom sort order.',
  `date_added` datetime NOT NULL COMMENT 'Date when attribute is associated to product category.',
  PRIMARY KEY (`attribute`,`category`),
  UNIQUE KEY `idxUAttributesOfProductCategory` (`attribute`,`category`) USING BTREE,
  KEY `idx_f_attributes_of_product_category_attribute_idx` (`attribute`) USING BTREE,
  KEY `idxFCategoryOfProductAttribute` (`category`) USING BTREE,
  KEY `idxNAttributesOfProductCategoryDateAdded` (`date_added`) USING BTREE,
  CONSTRAINT `idxFAttributeOfAttributesOfProductCategory` FOREIGN KEY (`attribute`) REFERENCES `product_attribute` (`id`),
  CONSTRAINT `idxFCategoryOfAttributesOfProductCategory` FOREIGN KEY (`category`) REFERENCES `product_category` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci ROW_FORMAT=COMPACT;

-- ----------------------------
-- Table structure for categories_of_product
-- ----------------------------
DROP TABLE IF EXISTS `categories_of_product`;
CREATE TABLE `categories_of_product` (
  `product` int(10) unsigned NOT NULL COMMENT 'Product associated with category.',
  `category` int(10) unsigned NOT NULL COMMENT 'Category associated with poduct.',
  `sort_order` int(11) NOT NULL DEFAULT '1' COMMENT 'Custom sort order.',
  `date_added` datetime NOT NULL COMMENT 'Date when product is added to category.',
  PRIMARY KEY (`product`,`category`),
  UNIQUE KEY `idxUCategoriesOfProduct` (`product`,`category`) USING BTREE,
  KEY `idxNCategoriesOfProductDateAdded` (`date_added`) USING BTREE,
  KEY `idxFCategoryOfProduct` (`category`) USING BTREE,
  KEY `idxFProductOfCategory` (`product`) USING BTREE,
  CONSTRAINT `idxFCategoryOfCategoriesOfProduct` FOREIGN KEY (`category`) REFERENCES `product_category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `idxFProductOfCategoriesOfProduct` FOREIGN KEY (`product`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=sjis ROW_FORMAT=COMPACT;

-- ----------------------------
-- Table structure for files_of_product
-- ----------------------------
DROP TABLE IF EXISTS `files_of_product`;
CREATE TABLE `files_of_product` (
  `file` int(10) unsigned NOT NULL COMMENT 'File that is owned by the product.',
  `product` int(15) unsigned NOT NULL COMMENT 'Product that files belong to.',
  `sort_order` int(10) unsigned NOT NULL DEFAULT '1' COMMENT 'Custom sort order.',
  `date_added` datetime NOT NULL COMMENT 'Date when file is adde to product.',
  `type` char(1) COLLATE utf8_turkish_ci NOT NULL DEFAULT 'i' COMMENT 'To easily categorize media by type. v:video;i:image;a:audio;:e:embed',
  PRIMARY KEY (`file`,`product`),
  UNIQUE KEY `idxUFilesOfProduct` (`file`,`product`) USING BTREE,
  KEY `idxFProductOfFile` (`product`) USING BTREE,
  KEY `idxNFilesOfProductDateAdded` (`date_added`) USING BTREE,
  KEY `idxNFileOfProduct` (`file`) USING BTREE,
  CONSTRAINT `idxFProductOfFilesOfProduct` FOREIGN KEY (`product`) REFERENCES `product` (`id`),
  CONSTRAINT `idxFFileOfFilesOfProduct` FOREIGN KEY (`file`) REFERENCES `file` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci ROW_FORMAT=COMPACT;

-- ----------------------------
-- Table structure for product
-- ----------------------------
DROP TABLE IF EXISTS `product`;
CREATE TABLE `product` (
  `id` int(15) unsigned NOT NULL AUTO_INCREMENT COMMENT 'System given id.',
  `quantity` int(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Stock count of the product.',
  `price` decimal(8,2) unsigned NOT NULL DEFAULT '0.00' COMMENT 'Most up-to-date price of the product.',
  `discount_price` decimal(8,2) unsigned DEFAULT NULL COMMENT 'Item most up to date discounted price.',
  `count_view` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'View count.',
  `sku` varchar(155) COLLATE utf8_turkish_ci NOT NULL COMMENT 'Stock keeping unit; stock code.',
  `sort_order` int(10) unsigned NOT NULL DEFAULT '1' COMMENT 'Custom sort order.',
  `date_added` datetime NOT NULL COMMENT 'Date when the product first added.',
  `date_updated` datetime NOT NULL COMMENT 'Date when the product last updated.',
  `date_removed` datetime DEFAULT NULL COMMENT 'Date when the product is removed.',
  `status` char(1) COLLATE utf8_turkish_ci NOT NULL DEFAULT 'a' COMMENT 'a:active;i:inactive;p:pre-order;o:out of stock;v:visible;h:hidden',
  `count_like` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Number of times product has been liked.',
  `type` char(1) COLLATE utf8_turkish_ci NOT NULL DEFAULT 't' COMMENT 't:tangible,i:intangible',
  `is_customizable` char(1) COLLATE utf8_turkish_ci NOT NULL DEFAULT 'n' COMMENT 'y:yes;n:no',
  `site` int(10) unsigned DEFAULT NULL COMMENT 'Primary site that product has been crreate from.',
  `preview_file` int(10) unsigned DEFAULT NULL COMMENT 'Preview file of product.',
  `brand` int(10) unsigned DEFAULT NULL COMMENT 'Brand of product if exists.',
  `supplier` int(10) DEFAULT NULL,
  `extra_info` text COLLATE utf8_turkish_ci COMMENT 'Extra product info will be stored here if needed.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idxUProductId` (`id`) USING BTREE,
  UNIQUE KEY `idxUProductSku` (`sku`,`site`) USING BTREE,
  KEY `idxNProductDateAdded` (`date_added`) USING BTREE,
  KEY `idxNProductDateUpdated` (`date_updated`) USING BTREE,
  KEY `idxNProductDateRemoved` (`date_removed`),
  KEY `idxFSiteOfProduct` (`site`) USING BTREE,
  KEY `idxFPreviewFileOfProduct` (`preview_file`) USING BTREE,
  KEY `idxNProductsOfBrand` (`id`,`brand`) USING BTREE,
  KEY `idxFBrandOfProduct` (`brand`) USING BTREE,
  CONSTRAINT `idxFSiteOfProduct` FOREIGN KEY (`site`) REFERENCES `site` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `idxFBrandOfProduct` FOREIGN KEY (`brand`) REFERENCES `brand` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `idxFPreviewFileOfProduct` FOREIGN KEY (`preview_file`) REFERENCES `file` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci ROW_FORMAT=COMPACT;

-- ----------------------------
-- Table structure for product_attribute
-- ----------------------------
DROP TABLE IF EXISTS `product_attribute`;
CREATE TABLE `product_attribute` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'System given id.',
  `sort_order` int(10) unsigned NOT NULL DEFAULT '1' COMMENT 'Custom sort order.',
  `date_added` datetime NOT NULL COMMENT 'Date when the attribute is added.',
  `site` int(5) unsigned DEFAULT NULL,
  `date_updated` datetime NOT NULL COMMENT 'Date when the entry is last updated.',
  `date_removed` datetime DEFAULT NULL COMMENT 'Date when the entry is marked as removed.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idxUProductAttributeId` (`id`) USING BTREE,
  KEY `idxNSiteOfProductAttribute` (`site`) USING BTREE,
  KEY `idxNProductAttributeDateAdded` (`date_added`) USING BTREE,
  KEY `idxNProductAttributeDateUpdated` (`date_updated`),
  KEY `idxNProductAttributeDateRemoved` (`date_removed`),
  CONSTRAINT `idxFSiteOfProductAttribute` FOREIGN KEY (`site`) REFERENCES `site` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci ROW_FORMAT=COMPACT;

-- ----------------------------
-- Table structure for product_attribute_localization
-- ----------------------------
DROP TABLE IF EXISTS `product_attribute_localization`;
CREATE TABLE `product_attribute_localization` (
  `language` int(10) unsigned NOT NULL COMMENT 'Localization language.',
  `attribute` int(10) unsigned NOT NULL COMMENT 'Localized attribute.',
  `name` varchar(155) COLLATE utf8_turkish_ci DEFAULT NULL COMMENT 'Localized name of product attribute.',
  `url_key` varchar(255) COLLATE utf8_turkish_ci DEFAULT NULL COMMENT 'Localized Url Key of product attribute.',
  PRIMARY KEY (`language`,`attribute`),
  UNIQUE KEY `idxUProductAttributeLocalization` (`language`,`attribute`) USING BTREE,
  UNIQUE KEY `idxNProductAttributeUrlKey` (`language`,`attribute`,`url_key`),
  KEY `idxNProductAttributeLocalizationLanguage` (`language`) USING BTREE,
  KEY `idxNLocalizedProductAttribute` (`attribute`) USING BTREE,
  CONSTRAINT `idxFAttributeOfProductAttributeLocalization` FOREIGN KEY (`attribute`) REFERENCES `product_attribute` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `idxFLanguageOfProductAttributeLocalization` FOREIGN KEY (`language`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci ROW_FORMAT=COMPACT;

-- ----------------------------
-- Table structure for product_attribute_values
-- ----------------------------
DROP TABLE IF EXISTS `product_attribute_values`;
  CREATE TABLE `product_attribute_values` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'System given id.',
  `value` text COLLATE utf8_turkish_ci NOT NULL COMMENT 'Value of attribute.',
  `language` int(10) unsigned NOT NULL COMMENT 'Language of the attribute value.',
  `attribute` int(10) unsigned NOT NULL COMMENT 'Attribute that value belongs to.',
  `product` int(15) unsigned NOT NULL COMMENT 'Product that attribute belongs to.',
  `sort_order` int(10) unsigned NOT NULL DEFAULT '1' COMMENT 'Custom sort order.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idxUProductAttributeValuesId` (`id`) USING BTREE,
  UNIQUE KEY `idxUProductAttributeValues` (`language`,`attribute`,`product`) USING BTREE,
  KEY `idxLanguageOfAttributeValue` (`language`) USING BTREE,
  KEY `idxAttributeOfAttributeValue` (`attribute`) USING BTREE,
  KEY `idxProductOfAttributeValue` (`product`) USING BTREE,
  CONSTRAINT `idxProductOfAttributeValue` FOREIGN KEY (`product`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `idxAttributeOfAttributeValue` FOREIGN KEY (`attribute`) REFERENCES `product_attribute` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `idxLanguageOfAttributeValue` FOREIGN KEY (`language`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci ROW_FORMAT=COMPACT;

-- ----------------------------
-- Table structure for product_category
-- ----------------------------
DROP TABLE IF EXISTS `product_category`;
CREATE TABLE `product_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'System given id.',
  `parent` int(10) unsigned DEFAULT NULL COMMENT 'Parent category id.',
  `level` char(1) COLLATE utf8_turkish_ci NOT NULL DEFAULT 't' COMMENT 't:top;b:bottom;m:middle',
  `count_children` int(10) unsigned NOT NULL COMMENT 'Number of immediate children.',
  `date_added` datetime NOT NULL COMMENT 'Date when the category added.',
  `date_updated` datetime DEFAULT NULL COMMENT 'Date when the product is last updatedç',
  `site` int(10) unsigned DEFAULT NULL COMMENT 'Site that category is defined for.',
  `preview_file` int(10) unsigned DEFAULT NULL,
  `is_featured` char(1) COLLATE utf8_turkish_ci NOT NULL DEFAULT 'n',
  `sort_order` int(10) unsigned DEFAULT NULL,
  `preview_image` varchar(255) COLLATE utf8_turkish_ci DEFAULT NULL,
  `date_removed` datetime DEFAULT NULL COMMENT 'Date when the entry is marked as removed.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idxUProductCategoryId` (`id`) USING BTREE,
  KEY `idxNProductCategoryDate` (`date_added`) USING BTREE,
  KEY `idxNProductCategoryDateUpdated` (`date_updated`) USING BTREE,
  KEY `idxNProductCategoryDateRemoved` (`date_removed`),
  KEY `idxFParentOfProductCategory` (`parent`) USING BTREE,
  KEY `idxFSiteOfProductCategory` (`site`) USING BTREE,
  KEY `idxFPreviewFileOfProductCategory` (`preview_file`) USING BTREE,
  CONSTRAINT `idxFParentOfProductCategory` FOREIGN KEY (`parent`) REFERENCES `product_category` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `idxFSiteOfProductCategory` FOREIGN KEY (`site`) REFERENCES `site` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `idxFPreviewFileOfProductCategory` FOREIGN KEY (`preview_file`) REFERENCES `file` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci ROW_FORMAT=COMPACT;

-- ----------------------------
-- Table structure for product_category_localization
-- ----------------------------
DROP TABLE IF EXISTS `product_category_localization`;
CREATE TABLE `product_category_localization` (
  `language` int(10) unsigned NOT NULL COMMENT 'Localization language.',
  `category` int(10) unsigned NOT NULL COMMENT 'Localized category.',
  `name` varchar(45) COLLATE utf8_turkish_ci NOT NULL COMMENT 'Localized name of category.',
  `url_key` varchar(155) COLLATE utf8_turkish_ci NOT NULL,
  `description` varchar(255) COLLATE utf8_turkish_ci DEFAULT NULL COMMENT 'Localized description of category.',
  `meta_keywords` varchar(155) COLLATE utf8_turkish_ci DEFAULT NULL COMMENT 'Localized meta keywords.',
  `meta_description` varchar(255) COLLATE utf8_turkish_ci DEFAULT NULL COMMENT 'Localized meta description.',
  PRIMARY KEY (`language`,`category`),
  UNIQUE KEY `idxUProductCategoryLocalization` (`language`,`category`) USING BTREE,
  UNIQUE KEY `idxUProductCategoryUrlKey` (`language`,`category`,`url_key`),
  KEY `idxFProductCategoryLocalizationLanguage` (`language`) USING BTREE,
  KEY `idxFLocalizedProductCategory` (`category`) USING BTREE,
  CONSTRAINT `idxFCategoryOfProductCategoryLocalization` FOREIGN KEY (`category`) REFERENCES `product_category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `idxFLanguageOfProductCategoryLocalization` FOREIGN KEY (`language`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci ROW_FORMAT=COMPACT;

-- ----------------------------
-- Table structure for product_localization
-- ----------------------------
DROP TABLE IF EXISTS `product_localization`;
CREATE TABLE `product_localization` (
  `product` int(15) unsigned NOT NULL COMMENT 'Localized product.',
  `language` int(5) unsigned NOT NULL COMMENT 'Language of localization',
  `name` varchar(155) COLLATE utf8_turkish_ci DEFAULT NULL COMMENT 'Name of product.',
  `description` text COLLATE utf8_turkish_ci COMMENT 'Description of product.',
  `meta_keywords` varchar(155) COLLATE utf8_turkish_ci DEFAULT NULL COMMENT 'Meta keywords of product.',
  `meta_description` varchar(155) COLLATE utf8_turkish_ci DEFAULT NULL COMMENT 'Meta description of the product.',
  `url_key` varchar(255) COLLATE utf8_turkish_ci DEFAULT NULL,
  PRIMARY KEY (`product`,`language`),
  UNIQUE KEY `idxUProductLocalization` (`product`,`language`) USING BTREE,
  KEY `idxNLanguageOfProductLocalization` (`language`) USING BTREE,
  KEY `idxNProductOfProductLocalization` (`product`) USING BTREE,
  KEY `idxUProductUrlKey` (`product`,`language`,`url_key`) USING BTREE,
  CONSTRAINT `idxFProductOfProductLocalization` FOREIGN KEY (`product`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `idxFLanguageOfProductLocalization` FOREIGN KEY (`language`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci ROW_FORMAT=COMPACT;

-- ----------------------------
-- Table structure for product_url_key_history
-- ----------------------------
DROP TABLE IF EXISTS `product_url_key_history`;
CREATE TABLE `product_url_key_history` (
  `url_key` varchar(255) NOT NULL COMMENT 'Url key of product.',
  `date_added` datetime NOT NULL COMMENT 'Date when the entry is first added.',
  `date_updated` datetime NOT NULL COMMENT 'Date when the entry is last updated.',
  `date_removed` datetime DEFAULT NULL COMMENT 'Date when the entry is marked as removed.',
  `product` int(10) unsigned NOT NULL COMMENT 'Url key of product.',
  PRIMARY KEY (`url_key`),
  KEY `idxFProductOfUrlKey` (`product`),
  KEY `idxUProductUrlKey` (`url_key`,`date_added`,`product`),
  CONSTRAINT `idxFProductOfUrlKey` FOREIGN KEY (`product`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for products_of_site
-- ----------------------------
DROP TABLE IF EXISTS `products_of_site`;
CREATE TABLE `products_of_site` (
  `product` int(15) unsigned NOT NULL COMMENT 'Product resides in site.',
  `site` int(10) unsigned NOT NULL COMMENT 'Site that owns the product.',
  `date_added` datetime NOT NULL COMMENT 'Date when the product is added.',
  `count_view` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'View count of this product within the site.',
  `count_like` int(11) NOT NULL DEFAULT '0' COMMENT 'Like count of this product within the site.',
  PRIMARY KEY (`product`,`site`),
  UNIQUE KEY `idxUProductsOfSite` (`product`,`site`) USING BTREE,
  KEY `idxNProductsOfSiteDateAdded` (`date_added`) USING BTREE,
  KEY `idxFSiteoFProduct` (`site`) USING BTREE,
  KEY `idxFProductOfSite` (`product`) USING BTREE,
  CONSTRAINT `idxFProductOfProductsOfSite` FOREIGN KEY (`product`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `idxFSiteOfProductsOfSite` FOREIGN KEY (`site`) REFERENCES `site` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci ROW_FORMAT=COMPACT;

-- ----------------------------
-- Table structure for related_product
-- ----------------------------
DROP TABLE IF EXISTS `related_product`;
  CREATE TABLE `related_product` (
  `product` int(10) unsigned NOT NULL,
  `related_product` int(10) unsigned NOT NULL,
  PRIMARY KEY (`product`,`related_product`),
  UNIQUE KEY `idxURelatedProduct` (`product`,`related_product`) USING BTREE,
  KEY `idxFRelatedProduct` (`related_product`) USING BTREE,
  CONSTRAINT `idxFProductOfRelatedProduct` FOREIGN KEY (`product`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `idxFRelatedProductOfRelatedProduct` FOREIGN KEY (`related_product`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Table structure for volume_pricing
-- ----------------------------
DROP TABLE IF EXISTS `volume_pricing`;
CREATE TABLE `volume_pricing` (
  `id` int(15) unsigned NOT NULL AUTO_INCREMENT COMMENT 'System given id.',
  `product` int(15) unsigned NOT NULL COMMENT 'Product',
  `quantity_limit` int(5) unsigned NOT NULL COMMENT 'Quantity limit for pricing to be effective',
  `limit_direction` char(2) COLLATE utf8_turkish_ci NOT NULL DEFAULT 'xm' COMMENT 'Direction of limit. xm:x or more, xl: x or less.',
  `date_added` datetime NOT NULL,
  `date_updated` datetime NOT NULL,
  `price` decimal(8,2) unsigned NOT NULL,
  `discounted_price` decimal(8,2) unsigned DEFAULT NULL,
  `date_removed` datetime DEFAULT NULL COMMENT 'Date when the entry is marked as removed.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idxUVolumePricingId` (`id`) USING BTREE,
  KEY `idxNVolumePricing` (`id`,`product`),
  KEY `idxNVolumePricingDateAdded` (`date_added`) USING BTREE,
  KEY `idxNVolumePricingDateUpdated` (`date_updated`) USING BTREE,
  KEY `idxNVolumePricingDateRemoved` (`date_removed`),
  KEY `idxFProductOfVolumePricing` (`product`),
  CONSTRAINT `idxFProductOfVolumePricing` FOREIGN KEY (`product`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;
