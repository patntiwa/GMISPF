PRAGMA foreign_keys = ON;
CREATE TABLE IF NOT EXISTS users (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 name TEXT NOT NULL CHECK(length(name) BETWEEN 2 AND 80),
 email TEXT NOT NULL UNIQUE COLLATE NOCASE,
 password_hash TEXT NOT NULL,
 role TEXT NOT NULL CHECK(role IN ('admin','agent','prestataire')),
 active INTEGER NOT NULL DEFAULT 1 CHECK(active IN (0,1)),
 auth_version INTEGER NOT NULL DEFAULT 1,
 created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS reports (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 title TEXT NOT NULL CHECK(length(title) BETWEEN 3 AND 120),
 location TEXT NOT NULL CHECK(length(location) BETWEEN 2 AND 100),
 category TEXT NOT NULL CHECK(category IN ('Sanitaire','Plomberie','Forage')),
 part TEXT NOT NULL DEFAULT '',
 condition TEXT NOT NULL CHECK(condition IN ('À suivre','Moyen','Critique')),
 priority TEXT NOT NULL CHECK(priority IN ('Faible','Moyenne','Urgente')),
 status TEXT NOT NULL DEFAULT 'À traiter' CHECK(status IN ('À traiter','En cours','Résolue')),
 notes TEXT NOT NULL DEFAULT '',
 owner_id INTEGER NOT NULL REFERENCES users(id),
 version INTEGER NOT NULL DEFAULT 1,
 archived INTEGER NOT NULL DEFAULT 0 CHECK(archived IN (0,1)),
 created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS reports_owner ON reports(owner_id, archived, created_at);
CREATE INDEX IF NOT EXISTS reports_filters ON reports(archived, status, priority, category);
CREATE TABLE IF NOT EXISTS audit (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 user_id INTEGER NOT NULL REFERENCES users(id),
 report_id INTEGER REFERENCES reports(id),
 action TEXT NOT NULL,
 created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS audit_report ON audit(report_id, id);
CREATE TABLE IF NOT EXISTS login_limits (
 key_hash TEXT PRIMARY KEY,
 attempts INTEGER NOT NULL,
 expires_at INTEGER NOT NULL
);
