-- glb9 create sessions table for aleens backend sudo mysql DB_NAME < backend/sessions.sql
CREATE TABLE IF NOT EXISTS sessions (
    sessionId CHAR(64) NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    PRIMARY KEY (sessionId),
    INDEX idx_sessions_user (user_id),
    CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS posts (
   id         INT AUTO_INCREMENT PRIMARY KEY,
   user_id    INT NOT NULL,
   title      VARCHAR(150) NOT NULL,
   body       TEXT NOT NULL,
   created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
   INDEX idx_posts_created (created_at),
   CONSTRAINT fk_posts_user FOREIGN KEY (user_id)
       REFERENCES users(id) ON DELETE CASCADE
);
 
CREATE TABLE IF NOT EXISTS replies (
   id         INT AUTO_INCREMENT PRIMARY KEY,
   post_id    INT NOT NULL,
   user_id    INT NOT NULL,
   body       TEXT NOT NULL,
   created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
   INDEX idx_replies_post (post_id, created_at),
   CONSTRAINT fk_replies_post FOREIGN KEY (post_id)
       REFERENCES posts(id) ON DELETE CASCADE,
   CONSTRAINT fk_replies_user FOREIGN KEY (user_id)
       REFERENCES users(id) ON DELETE CASCADE
);

-- glb9 card search table
CREATE TABLE IF NOT EXISTS cards (
    id INT PRIMARY KEY, -- TCG API card id
    name VARCHAR(255) NOT NULL,
    clean_name VARCHAR(255) NOT NULL,
    set_name VARCHAR(150),
    number VARCHAR(20),
    rarity VARCHAR(50),
    printing VARCHAR(50),
    image_url VARCHAR(500),
    market_price DECIMAL(10,2),
    market_price_as_of DATE, -- date TCGplayer priced it
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cards_clean_name (clean_name)
);

-- glb9 watchlist table
CREATE TABLE IF NOT EXISTS watchlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    card_id INT NOT NULL, -- TCG API card id
    target_price DECIMAL(10,2) NOT NULL,
    alerted_at DATETIME NULL, -- set when alert is sent
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_watchlist_user_card (user_id, card_id),
    CONSTRAINT fk_watchlist_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_watchlist_card FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE
);
