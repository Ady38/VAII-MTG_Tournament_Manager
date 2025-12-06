DROP TABLE IF EXISTS decklist;
DROP TABLE IF EXISTS tournament_player;
DROP TABLE IF EXISTS match_;
DROP TABLE IF EXISTS tournament;
DROP TABLE IF EXISTS app_user;
DROP TABLE IF EXISTS role;


-- ROLE
CREATE TABLE role (
                      role_id      INT AUTO_INCREMENT PRIMARY KEY,
                      role_name    VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Definícia rolí používateľov';

-- POUŽÍVATEĽ
CREATE TABLE app_user (
                          user_id        INT AUTO_INCREMENT PRIMARY KEY,
                          username       VARCHAR(100) NOT NULL UNIQUE,
                          password_hash  VARCHAR(255) NOT NULL,
                          email          VARCHAR(150) NOT NULL UNIQUE,
                          created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
                          role_id        INT NOT NULL,
                          CONSTRAINT fk_user_role FOREIGN KEY (role_id)
                              REFERENCES role(role_id)
                              ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Účty používateľov systému';

CREATE INDEX idx_app_user_role ON app_user(role_id);

-- TURNAJ
CREATE TABLE tournament (
                            tournament_id  INT AUTO_INCREMENT PRIMARY KEY,
                            name           VARCHAR(150) NOT NULL,
                            location       VARCHAR(150),
                            start_date     DATETIME,
                            end_date       DATETIME,
                            status         VARCHAR(50) DEFAULT 'planned',
                            organizer_id   INT NOT NULL,
                            CONSTRAINT fk_tournament_organizer FOREIGN KEY (organizer_id)
                                REFERENCES app_user(user_id)
                                ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Informácie o turnajoch';

CREATE INDEX idx_tournament_status ON tournament(status);

-- ZÁPAS
CREATE TABLE match_ (
                        match_id       INT AUTO_INCREMENT PRIMARY KEY,
                        tournament_id  INT NOT NULL,
                        round_number   INT NOT NULL DEFAULT 1,
                        player1_id     INT NOT NULL,
                        player2_id     INT NOT NULL,
                        result         VARCHAR(50),
                        played_at      TIMESTAMP NULL,
                        CONSTRAINT fk_match_tournament FOREIGN KEY (tournament_id)
                            REFERENCES tournament(tournament_id)
                            ON UPDATE CASCADE ON DELETE CASCADE,
                        CONSTRAINT fk_match_p1 FOREIGN KEY (player1_id)
                            REFERENCES app_user(user_id)
                            ON UPDATE CASCADE ON DELETE RESTRICT,
                        CONSTRAINT fk_match_p2 FOREIGN KEY (player2_id)
                            REFERENCES app_user(user_id)
                            ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Zápasy a výsledky v rámci turnajov';

CREATE INDEX idx_match_tournament ON match_(tournament_id);
CREATE INDEX idx_match_players ON match_(player1_id, player2_id);

-- HRÁČ V TURNAJI
CREATE TABLE tournament_player (
                                   tournament_id  INT NOT NULL,
                                   user_id        INT NOT NULL,
                                   points         INT DEFAULT 0,
                                   rank_position  INT,
                                   PRIMARY KEY (tournament_id, user_id),
                                   CONSTRAINT fk_tp_tournament FOREIGN KEY (tournament_id)
                                       REFERENCES tournament(tournament_id)
                                       ON UPDATE CASCADE ON DELETE CASCADE,
                                   CONSTRAINT fk_tp_user FOREIGN KEY (user_id)
                                       REFERENCES app_user(user_id)
                                       ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_tp_user ON tournament_player(user_id);

-- DECKLIST
CREATE TABLE decklist (
                          decklist_id    INT AUTO_INCREMENT PRIMARY KEY,
                          user_id        INT NOT NULL,
                          tournament_id  INT NOT NULL,
                          file_path      VARCHAR(255),
                          uploaded_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                          approved       CHAR(1) DEFAULT 'N',
                          CONSTRAINT fk_deck_user FOREIGN KEY (user_id)
                              REFERENCES app_user(user_id)
                              ON UPDATE CASCADE ON DELETE CASCADE,
                          CONSTRAINT fk_deck_tournament FOREIGN KEY (tournament_id)
                              REFERENCES tournament(tournament_id)
                              ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_deck_user ON decklist(user_id);
CREATE INDEX idx_deck_tournament ON decklist(tournament_id);



