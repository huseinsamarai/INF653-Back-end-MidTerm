-- create_quotesdb_mysql.sql
CREATE DATABASE IF NOT EXISTS quotesdb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE quotesdb;

-- authors table
CREATE TABLE IF NOT EXISTS authors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  author VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- categories table
CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- quotes table
CREATE TABLE IF NOT EXISTS quotes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  quote TEXT NOT NULL,
  author_id INT NOT NULL,
  category_id INT NOT NULL,
  FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;