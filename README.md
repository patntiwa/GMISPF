# GMISPF 2.0 — Gestion de la Maintenance des Installations Sanitaires, Plomberie et Forage

**Application métier de suivi des interventions techniques pour la Direction Nationale de la BEAC à Bafoussam.**

---

## 1. Présentation du Projet

**GMISPF V2** est une refonte intégrale de l'application originale de gestion technique. Elle transforme une application PHP procédurale vulnérable en une plateforme web moderne, sécurisée, performante et accessible, conçue selon les meilleures pratiques d'ingénierie logicielle.

### Problématiques résolues par rapport à la V1 :
- **Sécurité critique :** Élimination complète des injections SQL, des vulnérabilités XSS, de l'absence de CSRF, des suppressions arbitraires par URL `GET`, et des mots de passe stockés en clair.
- **Expérience Utilisateur (UX/UI) :** Remplacement de l'interface vieillissante par un tableau de bord épuré, réactif et accessible, respectant les standards **WCAG 2.1 AA** et parfaitement adapté aux écrans mobiles et ordinateurs.
- **Architecture logicielle :** Cloisonnement strict des accès, isolation du répertoire public (`DocumentRoot`), base de données SQLite performante avec journalisation d'audit transactionnelle, et mode démonstration interactif sans base de données.

---

## 2. Tableau Comparatif V1 vs V2

| Fonctionnalité / Critère | Version 1 (Historique) | Version 2 (Nouvelle Version) |
| :--- | :--- | :--- |
| **PHP Supporté** | PHP 5.6 / obsolète | **PHP 8.2+ moderne avec typage strict (`declare(strict_types=1)`)** |
| **Sécurité des Mots de Passe** | En clair dans la base (`mdp`) | **Hachage adaptatif `bcrypt` (coût 12)** |
| **Protection Base de Données** | Concaténations SQL vulnérables | **Requêtes 100% préparées PDO (émulation désactivée)** |
| **Contrôle d'Accès** | Pages accessibles sans authentification | **RBAC strict (`admin`, `agent`, `prestataire`) vérifié côté serveur** |
| **Cloisonnement Prestataires** | Tous voient et modifient tout | **Cloisonnement au niveau objet : un prestataire ne voit que ses rapports** |
| **Protection CSRF** | Aucune protection | **Jetons cryptographiques 256 bits systématiques sur tous les `POST`** |
| **Suppression / Cycle de vie** | Suppression physique directe sans confirmation | **Archivage logique avec contrôle de concurrence optimiste (`HTTP 409`)** |
| **Journal d'Audit** | Simple copie de ligne dans une table | **Audit trail immuable avec diffs JSON et rollback atomique** |
| **Structure Web** | Fichiers PHP et dumps SQL exposés à la racine | **Seul `public/` est accessible par le serveur web** |
| **En-têtes HTTP & Cookies** | Aucun en-tête de sécurité | **CSP stricte, HSTS, `X-Content-Type-Options`, cookie `__Host-gmispf`** |
| **Accessibilité & Mobile** | Non responsive, contrastes insuffisants | **Responsive 390px-1440px+, validé WCAG 2.1 AA (0 violation Axe)** |
| **Mode Démonstration** | Inexistant | **Mode démo interactif autonome en lecture seule (sans base de données)** |

---

## 3. Architecture Technique

Le projet est conçu pour être **ultra-léger, sans dépendance externe en production** (aucun framework lourd, aucun appel à un CDN distant).

```
GMISPF/
├── .github/workflows/ci.yml       # Intégration continue (Lint, Tests unitaires, Tests HTTP)
├── .env.example                  # Modèle de variables d'environnement
├── app/                          # Logique métier & cœur applicatif (hors DocumentRoot)
│   ├── bootstrap.php             # Initialisation PDO, session, CSRF, utilitaires de sécurité
│   ├── auth.php                  # Gestion des utilisateurs, rôles, sessions et rate-limiting
│   ├── reports.php               # CRUD des interventions, validation, statistiques, audit
│   ├── ui.php                    # Helpers de rendu (badges, icônes SVG locales, formats de dates)
│   └── demo.php                  # Données fictives dynamiques pour le mode démonstration
├── bin/                          # Outils d'administration en ligne de commande
│   ├── console.php               # Initialisation, création d'utilisateurs, gestion des mots de passe
│   └── migrate_v1.php            # Script de migration depuis les dumps MySQL V1
├── database/
│   └── schema.sql                # Schéma relationnel SQLite (tables, index, clés étrangères)
├── docs/
│   ├── FONT-LICENSE.txt          # Licence de la police Inter
│   ├── MIGRATION.md              # Procédure détaillée de migration depuis la V1
│   └── SECURITY.md               # Architecture de sécurité et modèle de menaces
├── public/                       # Seul répertoire exposé au serveur Web (DocumentRoot)
│   ├── index.php                 # Contrôleur frontal unique
│   ├── router.php                # Routeur pour le serveur de développement local
│   ├── .htaccess                 # Règles de réécriture et restrictions d'accès Apache
│   └── assets/                   # Ressources statiques (CSS, JS, police Inter WOFF2, Favicon)
├── storage/                      # Données persistantes locales (hors DocumentRoot, chmod 700)
│   └── app.sqlite                # Base de données SQLite opérationnelle
├── tests/                        # Suites de tests automatisés
│   ├── unit.php                  # Tests unitaires métier, permissions et parsing PHP
│   ├── integration.py            # Tests d'intégration HTTP (sécurité, sessions, CSRF, rôles)
│   └── browser.mjs               # Tests navigateur & accessibilité WCAG (Playwright + Axe)
└── views/                        # Gabarits de présentation HTML/PHP avec échappement systématique
    ├── layout.php                # Shell applicatif (sidebar responsive, topbar, recherche)
    ├── dashboard.php             # Tableau de bord principal avec statistiques
    ├── reports.php               # Liste filtrable et paginée des interventions
    ├── form.php                  # Formulaire de création / édition avec validation
    ├── detail.php                # Fiche détaillée avec historique d'audit
    ├── login.php                 # Page d'authentification sécurisée
    ├── installations.php         # Vue synthétique par type d'équipement
    ├── history.php               # Journal d'activité global
    ├── account.php               # Gestion du profil et déconnexion sécurisée
    └── help.php                  # Guide d'utilisation intégré
```

---

## 4. Prérequis

- **PHP 8.2 ou supérieur** avec les extensions :
  - `pdo` et `pdo_sqlite`
  - `mbstring`
  - `json`
  - `session`
  - `filter`
- Serveur HTTP : **Apache 2.4+** (avec `mod_rewrite`) ou **Nginx 1.20+**
- Navigateur web moderne supportant CSS Grid et Flexbox

---

## 5. Démarrage Rapide (Environnement Local)

### 1. Configuration
Copiez le fichier d'exemple pour préparer les variables d'environnement :
```bash
cp .env.example .env
```

### 2. Initialiser la base de données
```bash
php bin/console.php init
```
La base SQLite est créée dans `storage/app.sqlite` avec les permissions adaptées (`0600`).

### 3. Créer un compte administrateur
```bash
php bin/console.php user:create admin@beac.int "Super Administrateur" admin
```
*Le mot de passe vous est demandé sur l'entrée standard avec masquage de l'écho terminal (minimum 12 caractères).*

### 4. Lancer le serveur local
```bash
php -S 0.0.0.0:8080 -t public public/router.php
```
Accédez à l'application dans votre navigateur : `http://localhost:8080`.

---

## 6. Déploiement en Production

### 1. Variables d'environnement
Dans votre environnement système ou votre configuration de VirtualHost :
```ini
APP_ENV=production
APP_HTTPS=1
APP_DEMO=0
APP_DB=/chemin/absolu/vers/GMISPF/storage/app.sqlite
```

### 2. Permissions sur les fichiers
```bash
# Propriété www-data (ou l'utilisateur de votre serveur web)
chown -R www-data:www-data /var/www/gmispf/storage
chmod 755 /var/www/gmispf
chmod 700 /var/www/gmispf/storage
chmod 600 /var/www/gmispf/storage/app.sqlite
```

### 3. Configuration Apache
Pointez **impérativement** le `DocumentRoot` sur le sous-dossier `public` :
```apache
<VirtualHost *:443>
    ServerName gmispf.beac.int
    DocumentRoot /var/www/gmispf/public

    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/gmispf.crt
    SSLCertificateKeyFile /etc/ssl/private/gmispf.key

    <Directory /var/www/gmispf/public>
        AllowOverride All
        Require all granted
    </Directory>

    # Bloquer tout accès accidentel à la racine parente
    <Directory /var/www/gmispf>
        Require all denied
    </Directory>
    <Directory /var/www/gmispf/public>
        Require all granted
    </Directory>
</VirtualHost>
```

### 4. Configuration Nginx
```nginx
server {
    listen 443 ssl http2;
    server_name gmispf.beac.int;
    root /var/www/gmispf/public;
    index index.php;

    ssl_certificate /etc/ssl/certs/gmispf.crt;
    ssl_certificate_key /etc/ssl/private/gmispf.key;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param APP_ENV production;
        fastcgi_param APP_HTTPS 1;
        fastcgi_param APP_DEMO 0;
    }

    # Bloquer tout accès aux fichiers cachés
    location ~ /\. {
        deny all;
    }
}
```

---

## 7. Gestion des Utilisateurs et Rôles (CLI)

Toutes les opérations d'administration de comptes s'effectuent via l'outil en ligne de commande `bin/console.php` :

- **Lister les utilisateurs :**
  ```bash
  php bin/console.php user:list
  ```
- **Créer un utilisateur :**
  ```bash
  php bin/console.php user:create email@exemple.com "Nom Complet" [admin|agent|prestataire]
  ```
- **Réinitialiser un mot de passe (invalide immédiatement les sessions en cours) :**
  ```bash
  php bin/console.php user:password email@exemple.com
  ```
- **Désactiver un compte (révocation immédiate) :**
  ```bash
  php bin/console.php user:disable email@exemple.com
  ```

### Matrice des Rôles et Permissions :
| Action | Administrateur (`admin`) | Agent Technique (`agent`) | Prestataire (`prestataire`) |
| :--- | :---: | :---: | :---: |
| Consulter le tableau de bord | Oui (Global) | Oui (Global) | Oui (Ses interventions) |
| Voir la liste des interventions | Oui (Toutes) | Oui (Toutes) | Oui (Ses interventions uniquement) |
| Rechercher & Filtrer | Oui | Oui | Oui (Périmètre restreint) |
| Créer une intervention | Oui | Non | Oui |
| Modifier une intervention | Oui (Toutes) | Non | Oui (Les siennes uniquement) |
| Archiver une intervention | Oui | Non | Non |
| Exporter les données (CSV) | Oui (Global) | Oui (Global) | Oui (Ses interventions) |
| Consulter le journal d'audit | Oui (Global) | Oui (Global) | Oui (Ses interventions) |

---

## 8. Mode Démonstration (Aperçu)

L'application inclut un mode démonstration intégré (`APP_DEMO=1`) :
- Il permet de visualiser et d'interagir avec l'interface via 42 rapports fictifs générés à la volée.
- **Aucune base de données requise :** Les données réelles ne sont jamais touchées.
- **Lecture seule stricte :** Toute tentative de création, modification ou suppression est rejetée avec un code `HTTP 403`.
- **Protection en production :** Même si `APP_DEMO=1` est configuré, dès que `APP_ENV=production`, l'accès requiert obligatoirement une authentification réelle.

---

## 9. Tests et Assurance Qualité

Le projet dispose d'une couverture de tests automatisés sans dépendance externe lourde :

### Tests unitaires & logique métier (PHP)
```bash
php tests/unit.php
```
*Vérifie 69 assertions : validation de formulaire, échappement HTML, règles RBAC, contrôle de concurrence 409, transactions et rollback, limitation de débit.*

### Tests d'intégration HTTP (Python)
```bash
python3 tests/integration.py
```
*Vérifie 101 assertions sur un serveur réel temporaire : cycle de session, protection CSRF, en-têtes HTTP de sécurité, tentative d'injection SQL/XSS stockée, neutralisation des formules CSV, cloisonnement multi-utilisateurs.*

### Tests navigateur & accessibilité (Playwright + Axe)
```bash
npm run test:ui
```
*Vérifie l'absence totale de violation WCAG 2.1 AA (Axe Core), l'absence de débordement horizontal sur 390px (mobile) et 1440px (desktop), et l'interaction avec les filtres, le menu et l'export.*

---

## 10. Avertissement de Sécurité sur l'Historique Git

> **Important :** L'ancienne version du projet stockait des identifiants et des sauvegardes SQL en clair dans ses commits initiaux (`dfa9761a...`). Bien que ces fichiers aient été supprimés de la branche active de la V2, **tout mot de passe ou compte ayant figuré dans l'ancien historique doit être immédiatement changé et révoqué**.
