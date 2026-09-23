-- RMUTT Application Database User Initialization
-- Grants application privileges without granting administrative or root privileges

CREATE USER IF NOT EXISTS 'rmutt_app'@'%' IDENTIFIED BY 'rmutt_app_pass_2026!';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, CREATE TEMPORARY TABLES, LOCK TABLES, EXECUTE, CREATE VIEW, SHOW VIEW ON `rmutt`.* TO 'rmutt_app'@'%';
FLUSH PRIVILEGES;
