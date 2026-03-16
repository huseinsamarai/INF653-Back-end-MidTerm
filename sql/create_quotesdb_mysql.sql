-- create_quotesdb_postgres.sql

-- Drop and create database (PostgreSQL ignores CHARACTER SET / COLLATE in this syntax)
DROP DATABASE IF EXISTS quotesdb_ptm8;
CREATE DATABASE quotesdb_ptm8;

\c quotesdb_ptm8;

-- Authors table
CREATE TABLE authors (
    id SERIAL PRIMARY KEY,
    author VARCHAR(255) NOT NULL
);

-- Categories table
CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    category VARCHAR(255) NOT NULL
);

-- Quotes table
CREATE TABLE quotes (
    id SERIAL PRIMARY KEY,
    quote TEXT NOT NULL,
    author_id INT NOT NULL REFERENCES authors(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    category_id INT NOT NULL REFERENCES categories(id) ON DELETE RESTRICT ON UPDATE CASCADE
);

-- Insert authors
INSERT INTO authors (author) VALUES
('Albert Einstein'),
('Maya Angelou'),
('Oscar Wilde'),
('Nelson Mandela'),
('Husein Samarai');

-- Insert categories
INSERT INTO categories (category) VALUES
('Life'),
('Motivation'),
('Technology'),
('Philosophy'),
('Humor');

-- Insert quotes
INSERT INTO quotes (quote, author_id, category_id) VALUES
('Life is like riding a bicycle. To keep your balance you must keep moving.', 1, 1),
('If you are always trying to be normal you will never know how amazing you can be.', 2, 1),
('Be yourself; everyone else is already taken.', 3, 2),
('Imagination is more important than knowledge.', 1, 2),
('Try to be a rainbow in someone else''s cloud.', 2, 3),
('To live is the rarest thing in the world. Most people exist, that is all.', 3, 3),
('The greatest glory in living lies not in never falling, but in rising every time we fall.', 4, 2),
('Small joys are the seeds of great happiness.', 1, 5),
('Courage is the most important of all the virtues because without courage, you cannot practice any other virtue consistently.', 4, 1),
('Thoughtful code and clear intent reveal the mind behind the work.', 5, 4),
('The only way to do great work is to love what you do.', 1, 3),
('We may encounter many defeats but we must not be defeated.', 2, 2),
('A little sincerity is a dangerous thing, and a great deal of it is absolutely fatal.', 3, 3),
('Education is the most powerful weapon which you can use to change the world.', 4, 4),
('Happiness often sneaks in through a door you did not know you left open.', 2, 4),
('Technology, when used well, frees people to do more creative work.', 1, 3),
('Life is too important to be taken seriously.', 3, 5),
('Hope is being able to see that there is light despite all of the darkness.', 4, 1),
('Curiosity fuels the best inventions.', 1, 3),
('Philosophy starts with curiosity and ends with action.', 5, 4),
('Do not go where the path may lead, go instead where there is no path and leave a trail.', 3, 1),
('Each time we face our fear, we gain strength, courage and confidence.', 2, 2),
('The power of the human spirit is more important than any tool.', 4, 3),
('A good laugh heals a lot of hurts.', 2, 5),
('Simplicity is the ultimate sophistication.', 3, 5);