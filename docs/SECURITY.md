# Politique et Architecture de Sécurité — GMISPF V2

## 1. Principes et Avertissement Préalable

> **Transparence sur la sécurité :** Bien que cette version 2 ait été conçue pour éliminer l'ensemble des vulnérabilités critiques identifiées dans la version 1, **aucun système informatique ne peut prétendre garantir une absence absolue de failles (« zéro faille »)**. La sécurité repose sur une défense en profondeur, des tests rigoureux, une configuration d'hébergement durcie (HTTPS obligatoire) et des audits réguliers.

### Avertissement impératif sur l'historique Git
Dans l'ancienne version V1 du projet, des fichiers contenant des identifiants en clair et des sauvegardes SQL (`gmispf.sql`, `connexion.php`, etc.) ont été committés. Même si ces fichiers ont été retirés de l'arbre de travail actif de la V2, **l'historique Git conserve ces données**.
- **Action requise :** Tous les identifiants, mots de passe et adresses e-mail figurant dans l'ancien historique doivent être considérés comme compromis et immédiatement révoqués ou renouvelés.
- Si le dépôt doit devenir public, nettoyez l'historique avec `git-filter-repo` ou `BFG Repo-Cleaner`, ou recréez un historique propre à partir de la branche V2.

---

## 2. Défense en Profondeur : Mesures Mises en Œuvre

### 2.1 Isolation du système de fichiers (DocumentRoot)
- Seul le dossier `public/` doit être servi par Apache ou Nginx.
- Tous les fichiers applicatifs (`app/`), le schéma SQL (`database/`), la base de données SQLite (`storage/app.sqlite`), les scripts d'administration (`bin/`) et les tests (`tests/`) se trouvent **en dehors du DocumentRoot**.
- Des fichiers `.htaccess` de blocage (`Require all denied`) protègent la racine et le répertoire `storage/` contre les mauvaises configurations serveur.

### 2.2 Authentification et Gestion des Sessions
- **Hachage des mots de passe :** Algorithme `bcrypt` (coût 12) via `password_hash()`. Aucun mot de passe en clair ou faiblement haché (MD5/SHA1).
- **Règles de mot de passe :** Minimum 12 caractères, maximum 72 octets (limite bcrypt), vérification d'encodage UTF-8.
- **Régénération de session :** `session_regenerate_id(true)` systématique lors de la connexion pour empêcher la fixation de session.
- **Cookies de session :**
  - En production (HTTPS) : préfixe `__Host-gmispf`, attributs `Secure`, `HttpOnly`, `SameSite=Lax`.
  - En développement local : cookie `gmispf_dev`, `HttpOnly`, `SameSite=Lax`.
- **Délai d'inactivité et durée maximale :** Expiration après 30 minutes d'inactivité, session invalidée au bout de 8 heures maximum.
- **Révocation centralisée :** Incrément de `auth_version` en base lors d'un changement de mot de passe ou désactivation d'un compte (`active = 0`), invalidant instantanément toutes les sessions actives existantes.

### 2.3 Protection Anti-Force Brute (Rate Limiting)
- Table dédiée `login_limits` nettoyée automatiquement.
- Limitation par compte : 5 échecs consécutifs par adresse e-mail sur une fenêtre glissante de 15 minutes.
- Limitation par adresse IP : 25 échecs par IP sur 15 minutes pour contrer les attaques par pulvérisation (*password spraying*).
- Réponse normalisée : code HTTP `429 Too Many Requests` avec en-tête `Retry-After: 900`. Message d'erreur générique lors des échecs pour éviter l'énumération d'utilisateurs.

### 2.4 Protection contre les Injections SQL
- Utilisation exclusive de requêtes préparées PDO SQLite (`ATTR_EMULATE_PREPARES => false`).
- Aucun paramètre utilisateur n'est concaténé dans les requêtes SQL.
- Les identifiants (`id`, `owner_id`, `version`) sont strictement typés en entiers (`(int)`).
- Les filtres et critères sont validés par des listes blanches strictes d'énumérations (`CATEGORIES`, `PRIORITIES`, `STATUSES`).

### 2.5 Protection contre le Cross-Site Scripting (XSS)
- Fonction d'échappement universelle `e()` appliquée systématiquement à toute sortie utilisateur :
  `htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.
- Tous les scripts JavaScript sont hébergés localement dans `public/assets/app.js` (aucun script inline non contrôlé, aucune dépendance CDN externe).

### 2.6 Protection CSRF (Cross-Site Request Forgery)
- Génération d'un jeton aléatoire cryptographique de 256 bits (`random_bytes(32)`) stocké en session.
- Contrôle obligatoire sur chaque requête HTTP modifiante (`POST`) avec comparaison en temps constant (`hash_equals`).
- Déconnexion gérée obligatoirement par requête POST protégée par CSRF (pas de déconnexion par simple lien `GET`).

### 2.7 Contrôle d'Accès Basé sur les Rôles (RBAC) & Cloisonnement
- **Administrateur (`admin`) :** Supervision globale, création, modification, archivage, export et consultation de l'ensemble des interventions.
- **Agent technique (`agent`) :** Consultation en lecture seule de l'ensemble des interventions, filtres et export CSV. Interdiction formelle de création, modification ou archivage.
- **Prestataire (`prestataire`) :** Création d'interventions, modification **uniquement** des interventions dont il est le propriétaire. Cloisonnement strict au niveau objet : un prestataire ne peut ni lire, ni modifier, ni archiver une intervention appartenant à un autre prestataire (réponse HTTP 404).

### 2.8 Contrôle de Concurrence Optimiste
- Champ `version` incrémenté à chaque mise à jour.
- Si deux utilisateurs tentent de modifier ou d'archiver la même intervention simultanément, le deuxième utilisateur reçoit une réponse HTTP `409 Conflict`, empêchant l'écrasement silencieux de données.

### 2.9 En-têtes HTTP de Sécurité
- `Content-Security-Policy: default-src 'none'; style-src 'self'; script-src 'self'; img-src 'self'; font-src 'self'; connect-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'; object-src 'none'`
- `X-Content-Type-Options: nosniff`
- `Referrer-Policy: same-origin`
- `Permissions-Policy: camera=(), microphone=(), geolocation=()`
- `Strict-Transport-Security: max-age=31536000` (activé en production HTTPS)
- `Cache-Control: no-store, private` sur toutes les pages authentifiées
- Suppression de l'en-tête `X-Powered-By`

### 2.10 Protection des Exports CSV
- Préfixe d'apostrophe (`'`) ajouté automatiquement sur toute cellule débutant par un caractère interprétable comme formule par Excel ou Calc (`=`, `+`, `-`, `@`, tabulation, retour chariot).
- Export limité à 5000 lignes pour éviter la saturation mémoire.

### 2.11 Traçabilité et Audit Trail
- Table `audit` enregistrant chaque création, modification ou archivage d'intervention avec :
  - `report_id`, `user_id`, `action`, `title`, `changes` (diff JSON), `created_at`.
- Transaction atomique : si l'écriture de l'audit échoue, l'opération sur le rapport est annulée (*rollback*).

---

## 3. Bonnes Pratiques pour le Déploiement en Production

1. **Activer HTTPS obligatoirement** avec un certificat valide (Let's Encrypt ou autorité d'entreprise).
2. **Configurer les variables d'environnement dans le serveur Web :**
   - `APP_ENV=production`
   - `APP_HTTPS=1`
   - `APP_DEMO=0`
3. **Restreindre les droits sur le système de fichiers :**
   ```bash
   chmod 755 /var/www/gmispf
   chmod 700 /var/www/gmispf/storage
   chmod 600 /var/www/gmispf/storage/app.sqlite
   chown -R www-data:www-data /var/www/gmispf/storage
   ```
4. **Effectuer des sauvegardes régulières :**
   Utilisez la commande SQLite en ligne pour des sauvegardes cohérentes à chaud :
   ```bash
   sqlite3 /var/www/gmispf/storage/app.sqlite ".backup '/var/backups/gmispf_$(date +%F).sqlite'"
   ```
