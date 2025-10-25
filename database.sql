-- Database schema for Notes HQ

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'editor', 'viewer') NOT NULL DEFAULT 'viewer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    body MEDIUMTEXT NOT NULL,
    is_pinned TINYINT(1) DEFAULT 0,
    is_archived TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_notes_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS note_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    note_id INT NOT NULL,
    tag VARCHAR(40) NOT NULL,
    CONSTRAINT fk_tags_note FOREIGN KEY (note_id) REFERENCES notes(id) ON DELETE CASCADE,
    INDEX idx_note_tags_tag (tag)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS note_shares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    note_id INT NOT NULL,
    shared_with_user_id INT NOT NULL,
    shared_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_shares_note FOREIGN KEY (note_id) REFERENCES notes(id) ON DELETE CASCADE,
    CONSTRAINT fk_shares_user FOREIGN KEY (shared_with_user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uk_note_user (note_id, shared_with_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS note_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    note_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_replies_note FOREIGN KEY (note_id) REFERENCES notes(id) ON DELETE CASCADE,
    CONSTRAINT fk_replies_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (username, email, password_hash, role) VALUES
    ('admin', 'admin@example.com', '$2y$12$wCx5WI1uZiAYaBdtQlmSB.XSHQpdQdeVy9p8gJCJCEwsM/wuMZ5aG', 'admin'),
    ('manager', 'manager@example.com', '$2y$12$Frz0ZDr3J6gd23oQhk9Vk.smAb3KovsPSyz22ek80tWtKvCX/RsTO', 'manager'),
    ('editor', 'editor@example.com', '$2y$12$rKTkr7DsXwMn9Yfmy686Q.125VRnWpqbpg2p/W0hvO6EbE1evy9rm', 'editor'),
    ('viewer', 'viewer@example.com', '$2y$12$7inlNbBMrC400tmYLovVOOmYlrZVuHzPj6ENs3skUI76I9oz9mtnS', 'viewer');

INSERT INTO notes (owner_id, title, body, is_pinned, is_archived)
VALUES
    (1, 'Product strategy', 'Outline quarterly OKRs, success metrics, and dependencies.', 1, 0),
    (2, 'Team stand-up summary', 'Notes from the latest stand-up with action items and blockers.', 0, 0),
    (3, 'Content calendar', 'Upcoming blog topics, owners, and publish dates.', 0, 0);

INSERT INTO note_tags (note_id, tag) VALUES
    (1, 'planning'),
    (1, 'strategy'),
    (2, 'team'),
    (3, 'marketing'),
    (3, 'content');

INSERT INTO note_shares (note_id, shared_with_user_id) VALUES
    (1, 2),
    (1, 3),
    (2, 3),
    (2, 4);

INSERT INTO note_replies (note_id, user_id, content) VALUES
    (1, 2, 'Added a comment about cross-team dependencies.'),
    (2, 3, 'I can take the follow-up on the customer request.'),
    (2, 4, 'Reviewed and everything looks good.');
