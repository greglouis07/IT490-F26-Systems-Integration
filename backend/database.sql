-- glb9 adding sessions table for aleens backend sudo mysql DB_NAME < backend/sessions.sql
CREATE TABLE sessions (
    sessionId CHAR(64) NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    PRIMARY KEY (sessionId),
    INDEX idx_sessions_user (user_id),
    CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE TABLE posts (
   id         INT AUTO_INCREMENT PRIMARY KEY,
   user_id    INT NOT NULL,
   title      VARCHAR(150) NOT NULL,
   body       TEXT NOT NULL,
   created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
   INDEX idx_posts_created (created_at),
   CONSTRAINT fk_posts_user FOREIGN KEY (user_id)
       REFERENCES users(id) ON DELETE CASCADE
);
 
CREATE TABLE replies (
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

