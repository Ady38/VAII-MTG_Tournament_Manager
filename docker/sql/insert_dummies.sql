INSERT INTO role (role_id, role_name) VALUES
    (1, 'admin');

INSERT INTO role (role_id, role_name) VALUES
    (2, 'organizer');

INSERT INTO role (role_id, role_name) VALUES
    (3, 'player');

INSERT INTO app_user (user_id, username, password_hash, email, created_at, role_id) VALUES
    (1, 'dummy_user', 'dummy_hash', 'dummy_user@example.com', CURRENT_TIMESTAMP, 2);

INSERT INTO tournament (tournament_id, name, location, start_date, end_date, status, organizer_id) VALUES
    (1, 'Dummy Tournament', 'Dummy Location', '2025-12-10', '2025-12-15', 'planned', 1);

INSERT INTO tournament (tournament_id, name, location, start_date, end_date, status, organizer_id) VALUES
    (2, 'Second Dummy Tournament', 'Second Dummy Location', '2025-12-20', '2025-12-25', 'planned', 1);
