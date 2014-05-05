SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

-- -----------------------------------------------------
-- Schema cloudxxx
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `cloudxxx` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
USE `cloudxxx` ;

-- -----------------------------------------------------
-- Table `cloudxxx`.`company`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cloudxxx`.`company` ;

CREATE TABLE IF NOT EXISTS `cloudxxx`.`company` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NULL,
  `contact_name` VARCHAR(255) NULL,
  `contact_email` VARCHAR(255) NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `cloudxxx`.`user`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cloudxxx`.`user` ;

CREATE TABLE IF NOT EXISTS `cloudxxx`.`user` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `company_id` INT NULL,
  `username` VARCHAR(255) NULL,
  `password` VARCHAR(255) NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `username_UNIQUE` (`username` ASC),
  INDEX `fk_user_company_idx` (`company_id` ASC),
  CONSTRAINT `fk_user_company`
    FOREIGN KEY (`company_id`)
    REFERENCES `cloudxxx`.`company` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `cloudxxx`.`app`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cloudxxx`.`app` ;

CREATE TABLE IF NOT EXISTS `cloudxxx`.`app` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `title` INT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `cloudxxx`.`role`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cloudxxx`.`role` ;

CREATE TABLE IF NOT EXISTS `cloudxxx`.`role` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NULL,
  `app_id` INT NULL,
  `level` ENUM('GUEST','UPLOADER','ADMIN') NULL DEFAULT 'GUEST',
  PRIMARY KEY (`id`),
  INDEX `fk_role_app_idx` (`app_id` ASC),
  INDEX `fk_role_user_idx` (`user_id` ASC),
  CONSTRAINT `fk_role_app`
    FOREIGN KEY (`app_id`)
    REFERENCES `cloudxxx`.`app` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_role_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `cloudxxx`.`user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `cloudxxx`.`video`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cloudxxx`.`video` ;

CREATE TABLE IF NOT EXISTS `cloudxxx`.`video` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '				',
  `user_id` INT NOT NULL,
  `created_at` DATETIME NULL DEFAULT now(),
  `updated_at` DATETIME NULL DEFAULT now(),
  `path` VARCHAR(255) NULL,
  `title` VARCHAR(255) NULL,
  `description` VARCHAR(255) NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_video_user_idx` (`user_id` ASC),
  CONSTRAINT `fk_video_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `cloudxxx`.`user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `cloudxxx`.`site`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cloudxxx`.`site` ;

CREATE TABLE IF NOT EXISTS `cloudxxx`.`site` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `upload_url` VARCHAR(255) NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `cloudxxx`.`publish`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cloudxxx`.`publish` ;

CREATE TABLE IF NOT EXISTS `cloudxxx`.`publish` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '	',
  `video_id` INT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `site_id` INT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_publish_video_idx` (`video_id` ASC),
  INDEX `fk_publish_site_idx` (`site_id` ASC),
  CONSTRAINT `fk_publish_video`
    FOREIGN KEY (`video_id`)
    REFERENCES `cloudxxx`.`video` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_publish_site`
    FOREIGN KEY (`site_id`)
    REFERENCES `cloudxxx`.`site` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `cloudxxx`.`tag`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cloudxxx`.`tag` ;

CREATE TABLE IF NOT EXISTS `cloudxxx`.`tag` (
  `id` INT NOT NULL AUTO_INCREMENT COMMENT '		',
  `title` VARCHAR(255) NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `cloudxxx`.`tag_video`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cloudxxx`.`tag_video` ;

CREATE TABLE IF NOT EXISTS `cloudxxx`.`tag_video` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `tag_id` INT NULL,
  `video_id` INT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_tag_idx` (`tag_id` ASC),
  INDEX `fk_video_idx` (`video_id` ASC),
  CONSTRAINT `fk_tag`
    FOREIGN KEY (`tag_id`)
    REFERENCES `cloudxxx`.`tag` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_video`
    FOREIGN KEY (`video_id`)
    REFERENCES `cloudxxx`.`video` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
