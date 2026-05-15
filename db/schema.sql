CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nickname VARCHAR(50) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(30) NOT NULL DEFAULT 'custom',
    invite_code VARCHAR(20) NOT NULL UNIQUE,
    owner_user_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_groups_owner FOREIGN KEY (owner_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE group_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('owner', 'member') NOT NULL DEFAULT 'member',
    joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_group_user (group_id, user_id),
    CONSTRAINT fk_group_members_group FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    CONSTRAINT fk_group_members_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE restaurants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kakao_place_id VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    category_name VARCHAR(150) DEFAULT NULL,
    address_name VARCHAR(255) DEFAULT NULL,
    road_address_name VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    place_url VARCHAR(255) DEFAULT NULL,
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE saved_places (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    restaurant_id INT NOT NULL,
    status ENUM('want', 'visited', 'disliked') NOT NULL DEFAULT 'want',
    memo TEXT,
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_saved_place (group_id, restaurant_id),
    CONSTRAINT fk_saved_places_group FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    CONSTRAINT fk_saved_places_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
    CONSTRAINT fk_saved_places_user FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    restaurant_id INT NOT NULL,
    user_id INT NOT NULL,
    taste_score TINYINT NOT NULL,
    price_score TINYINT NOT NULL,
    mood_score TINYINT NOT NULL,
    access_score TINYINT NOT NULL,
    revisit_score TINYINT NOT NULL,
    comment TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_review_user_place (group_id, restaurant_id, user_id),
    CONSTRAINT fk_reviews_group FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CHECK (taste_score BETWEEN 1 AND 5),
    CHECK (price_score BETWEEN 1 AND 5),
    CHECK (mood_score BETWEEN 1 AND 5),
    CHECK (access_score BETWEEN 1 AND 5),
    CHECK (revisit_score BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    restaurant_id INT NOT NULL,
    visit_date DATE NOT NULL,
    companions VARCHAR(255) DEFAULT NULL,
    total_price INT DEFAULT NULL,
    note TEXT,
    will_revisit TINYINT NOT NULL DEFAULT 1,
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_visits_group FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    CONSTRAINT fk_visits_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
    CONSTRAINT fk_visits_user FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE visit_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visit_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_visit_photos_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    type ENUM('situation', 'mood', 'food') NOT NULL DEFAULT 'situation'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE restaurant_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    restaurant_id INT NOT NULL,
    tag_id INT NOT NULL,
    UNIQUE KEY uniq_restaurant_tag (group_id, restaurant_id, tag_id),
    CONSTRAINT fk_restaurant_tags_group FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    CONSTRAINT fk_restaurant_tags_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
    CONSTRAINT fk_restaurant_tags_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO tags (name, type) VALUES
('약속', 'situation'),
('함께 외식', 'situation'),
('가성비', 'situation'),
('조용한 곳', 'mood'),
('가까운 곳', 'situation'),
('카페', 'food'),
('특별한 날', 'situation');
