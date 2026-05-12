CREATE DATABASE IF NOT EXISTS lv4_films
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE lv4_films;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(30) NOT NULL,
  email VARCHAR(120) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY users_username_unique (username),
  UNIQUE KEY users_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS films (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  external_id INT UNSIGNED DEFAULT NULL,
  title VARCHAR(255) NOT NULL,
  year SMALLINT UNSIGNED NOT NULL,
  genre VARCHAR(120) NOT NULL,
  duration SMALLINT UNSIGNED NOT NULL,
  country VARCHAR(191) NOT NULL,
  directors TEXT NULL,
  actors TEXT NULL,
  rating DECIMAL(3,1) NOT NULL DEFAULT 0.0,
  total_votes INT UNSIGNED NOT NULL DEFAULT 0,
  description TEXT NULL,
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY films_external_id_unique (external_id),
  KEY films_title_index (title),
  KEY films_genre_index (genre),
  KEY films_year_index (year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wanted_films (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  film_id INT UNSIGNED NOT NULL,
  added_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY wanted_films_user_film_unique (user_id, film_id),
  KEY wanted_films_film_index (film_id),
  CONSTRAINT wanted_films_user_fk
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE,
  CONSTRAINT wanted_films_film_fk
    FOREIGN KEY (film_id) REFERENCES films (id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS images (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  filename VARCHAR(255) NOT NULL,
  description VARCHAR(255) NOT NULL DEFAULT '',
  path VARCHAR(255) NOT NULL,
  source ENUM('local', 'upload', 'api') NOT NULL DEFAULT 'local',
  mime_type VARCHAR(50) NOT NULL DEFAULT 'image/jpeg',
  uploaded_by INT UNSIGNED DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY images_filename_unique (filename),
  KEY images_uploaded_by_index (uploaded_by),
  CONSTRAINT images_uploaded_by_fk
    FOREIGN KEY (uploaded_by) REFERENCES users (id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS image_ratings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  image_id INT UNSIGNED NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  comment VARCHAR(500) NOT NULL DEFAULT '',
  rated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY image_ratings_user_image_unique (user_id, image_id),
  KEY image_ratings_image_index (image_id),
  CONSTRAINT image_ratings_user_fk
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE,
  CONSTRAINT image_ratings_image_fk
    FOREIGN KEY (image_id) REFERENCES images (id)
    ON DELETE CASCADE,
  CONSTRAINT image_ratings_rating_check CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (username, email, password_hash, role)
VALUES (
  'admin',
  'admin@example.com',
  '$2y$10$YW7ChQdOXV/acTuT4NfhZuliVHmNoIfQd.NYKJbUshnIFDaaPqTOq',
  'admin'
)
ON DUPLICATE KEY UPDATE
  email = VALUES(email),
  password_hash = VALUES(password_hash),
  role = VALUES(role);
