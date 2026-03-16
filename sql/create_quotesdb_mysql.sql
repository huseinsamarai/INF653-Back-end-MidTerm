DROP DATABASE IF EXISTS quotesdb;
CREATE DATABASE quotesdb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE quotesdb;

CREATE TABLE authors (
  id INT NOT NULL PRIMARY KEY,
  author VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE categories (
  id INT NOT NULL PRIMARY KEY,
  category VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE quotes (
  id INT NOT NULL PRIMARY KEY,
  quote TEXT NOT NULL,
  author_id INT NOT NULL,
  category_id INT NOT NULL,
  FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Insert authors (ids 1..5)
INSERT INTO authors (id, author) VALUES
(1,'Albert Einstein'),
(2,'Maya Angelou'),
(3,'Oscar Wilde'),
(4,'Nelson Mandela'),
(5,'Husein Samarai');

-- Insert categories (ids 1..5)
INSERT INTO categories (id, category) VALUES
(1,'Life'),
(2,'Motivation'),
(3,'Technology'),
(4,'Philosophy'),
(5,'Humor');

-- Insert 25 quotes (explicit ids 1..25).
-- Note: quotes 10 and 20 are by Husein (author_id = 5) in category_id = 4 (Philosophy).
INSERT INTO quotes (id, quote, author_id, category_id) VALUES
(1, 'Life is like riding a bicycle. To keep your balance you must keep moving.', 1, 1),
(2, 'If you are always trying to be normal you will never know how amazing you can be.', 2, 1),
(3, 'Be yourself; everyone else is already taken.', 3, 2),
(4, 'Imagination is more important than knowledge.', 1, 2),
(5, 'Try to be a rainbow in someone else''s cloud.', 2, 3),
(6, 'To live is the rarest thing in the world. Most people exist, that is all.', 3, 3),
(7, 'The greatest glory in living lies not in never falling, but in rising every time we fall.', 4, 2),
(8, 'Small joys are the seeds of great happiness.', 1, 5),
(9, 'Courage is the most important of all the virtues because without courage, you cannot practice any other virtue consistently.', 4, 1),
(10,'Thoughtful code and clear intent reveal the mind behind the work.', 5, 4),
(11,'The only way to do great work is to love what you do.', 1, 3),
(12,'We may encounter many defeats but we must not be defeated.', 2, 2),
(13,'A little sincerity is a dangerous thing, and a great deal of it is absolutely fatal.', 3, 3),
(14,'Education is the most powerful weapon which you can use to change the world.', 4, 4),
(15,'Happiness often sneaks in through a door you did not know you left open.', 2, 4),
(16,'Technology, when used well, frees people to do more creative work.', 1, 3),
(17,'Life is too important to be taken seriously.', 3, 5),
(18,'Hope is being able to see that there is light despite all of the darkness.', 4, 1),
(19,'Curiosity fuels the best inventions.', 1, 3),
(20,'Philosophy starts with curiosity and ends with action.', 5, 4),
(21,'Do not go where the path may lead, go instead where there is no path and leave a trail.', 3, 1),
(22,'Each time we face our fear, we gain strength, courage and confidence.', 2, 2),
(23,'The power of the human spirit is more important than any tool.', 4, 3),
(24,'A good laugh heals a lot of hurts.', 2, 5),
(25,'Simplicity is the ultimate sophistication.', 3, 5);

-- reset AUTO_INCREMENT in case someone inserts later without id
ALTER TABLE authors AUTO_INCREMENT = 6;
ALTER TABLE categories AUTO_INCREMENT = 6;
ALTER TABLE quotes AUTO_INCREMENT = 26;  