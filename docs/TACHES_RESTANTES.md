# Document de Suivi : Tâches Non Réalisées, Périmètre Différé et Feuille de Route (V2.1+)

**Projet : GMISPF (Gestion de la Maintenance des Installations Sanitaires, Plomberie et Forage — BEAC Bafoussam)**

Ce document recense de manière exhaustive et transparente l'ensemble des **tâches non réalisées dans cette version 2.0**, les fonctionnalités différées, les limites actuelles du système ainsi que la feuille de route recommandée pour les évolutions futures.

---

## 1. Synthèse de l'Existant (Socle V2 Réalisé à 100%)

Pour rappel, la refonte V2 a intégralement livré :
- **Architecture technique moderne :** Passage à PHP 8.2+ strict, isolation du DocumentRoot dans `public/`, architecture MVC légère sans framework lourd.
- **Sécurité en profondeur :** Hachage des mots de passe en `bcrypt` (coût 12), requêtes 100% préparées PDO, protection CSRF systématique, échappement contextuel universel (XSS), limitation de débit anti-force brute (par compte et par IP avec retour HTTP 429), en-têtes HTTP durcis (CSP, HSTS, no-sniff, anti-clickjacking) et cookies sécurisés `__Host-`.
- **Modèle de droits (RBAC) :** 3 rôles distincts (`admin`, `agent`, `prestataire`) avec cloisonnement strict au niveau objet pour les prestataires.
- **Expérience utilisateur (UX/UI) :** Tableau de bord réactif (desktop et mobile 390px-1440px+), graphiques synthétiques natifs, recherche avec raccourci `/`, filtres dynamiques, conformité **WCAG 2.1 AA** (0 violation Axe Core).
- **Mode Démonstration :** Environnement d'aperçu dynamique de 42 rapports fictifs en lecture seule, totalement découplé de la base de données.
- **Outils d'exploitation :** Console d'administration CLI (`bin/console.php`), outil de migration depuis les anciens dumps MySQL (`bin/migrate_v1.php`).
- **Suites de tests automatisés :** 69 tests unitaires PHP, 101 tests d'intégration HTTP (Python) et tests navigateur Playwright.

---

## 2. Inventaire des Tâches Non Réalisées et Fonctionnalités Différées

Les éléments ci-dessous n'ont pas été inclus dans le périmètre de la V2.0, soit parce qu'ils nécessitent des composants d'infrastructure externes spécifiques à la BEAC (serveur SMTP, annuaire LDAP, passerelle SMS), soit parce qu'ils dépassaient le cadre de la refonte du socle fonctionnel existant.

### 2.1 Notifications Externes en Temps Réel (E-mail SMTP & SMS)
- **État actuel :** Le système signale les urgences via l'interface (badge d'alerte rouge, pastille de notification sur la cloche en haut à droite, filtrage automatique des urgences non résolues).
- **Ce qui n'est pas fait :** Aucun e-mail ni SMS n'est envoyé lors de la création d'une intervention urgente ou lors de sa résolution.
- **Raison :** Aucun serveur SMTP d'entreprise ou passerelle SMS locale n'était configuré dans la V1. Intégrer un service tiers sans connaître l'infrastructure réseau de la BEAC (pare-feu bancaire, relais SMTP interne) aurait créé une dépendance non fonctionnelle.
- **Plan pour la V2.1 :** 
  - Ajouter un service `Mailer` asynchrone utilisant PHPMailer ou Symfony Mailer configuré via variables d'environnement (`SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS`).
  - Permettre l'envoi d'alertes par e-mail aux agents techniques lorsqu'une intervention est marquée « Urgente ».

### 2.2 Téléversement Sécurisé de Pièces Jointes et Photos
- **État actuel :** Les comptes rendus et observations sont uniquement textuels (jusqu'à 3000 caractères par rapport).
- **Ce qui n'est pas fait :** Impossibilité d'attacher des photos avant/après intervention (ex. fuite de tuyau, fissure sur cuve de forage, remplacement de vanne) ou des documents PDF (devis, bons de commande).
- **Raison :** La gestion sécurisée de fichiers uploadés est une source majeure de vulnérabilités (téléversement de scripts malveillants `.php`, attaques par déni de service de stockage, métadonnées sensibles EXIF). Elle exige une infrastructure dédiée : validation stricte du type MIME réel via `finfo`, renommage aléatoire, stockage hors du DocumentRoot, nettoyage des métadonnées et idéalement analyse antivirale.
- **Plan pour la V2.1 :**
  - Créer une table `report_attachments` (`id`, `report_id`, `filename`, `file_hash`, `file_size`, `mime_type`, `created_at`).
  - Mettre en place un script de téléchargement sécurisé passant par un contrôleur PHP avec contrôle strict des droits d'accès.

### 2.3 Authentification Multi-Facteurs (2FA / TOTP) et Annuaire d'Entreprise (LDAP / Active Directory)
- **État actuel :** Authentification par mot de passe fort (minimum 12 caractères, hachage bcrypt coût 12) avec protection anti-force brute.
- **Ce qui n'est pas fait :** Pas de second facteur d'authentification (Google Authenticator, code TOTP) ni de délégation d'authentification sur l'Active Directory de la BEAC.
- **Raison :** Complexité d'infrastructure nécessitant la mise en place d'une autorité de synchronisation avec le réseau informatique bancaire de la BEAC.
- **Plan pour la V2.2 :**
  - Intégrer une extension TOTP (RFC 6238) avec génération de QR Code pour les comptes administrateurs.
  - Option d'authentification via protocole LDAP / LDAPS pour lier les comptes des agents techniques à l'annuaire d'entreprise.

### 2.4 Module Avancé de Gestion de Stock des Pièces de Rechange
- **État actuel :** Le champ `part` permet de consigner textuellement la pièce concernée par l'intervention (ex. « Joint torique 20x27 », « Raccord PVC 50 », « Pompe immergée »).
- **Ce qui n'est pas fait :** Pas d'inventaire quantitatif en temps réel, pas de gestion d'entrées/sorties de stock, pas d'alerte de stock critique, pas de valorisation financière du matériel.
- **Raison :** L'application originale GMISPF était un outil de rapport de maintenance et non un progiciel de gestion intégrée (ERP) de stock.
- **Plan pour la V3 :**
  - Table `parts_catalog` (référence, désignation, catégorie, stock_actuel, stock_minimum, coût_unitaire).
  - Décrémentation automatique du stock lors de la clôture d'une intervention.

### 2.5 Workflow d'Approbation Hiérarchique et Signature Électronique
- **État actuel :** Un prestataire crée ou modifie son rapport. Un administrateur peut archiver le rapport une fois terminé.
- **Ce qui n'est pas fait :** Pas de circuit formel de validation en plusieurs étapes (ex. Statut « Soumis par prestataire » -> « Vérifié par Agent technique » -> « Validé et signé par Chef de service BEAC »).
- **Plan pour la V2.2 :**
  - Ajouter un cycle de validation formalisé avec horodatage cryptographique et émargement numérique.

### 2.6 Support de Base de Données Multi-Serveurs (MySQL 8 / PostgreSQL)
- **État actuel :** Base de données SQLite locale avec mode WAL (`storage/app.sqlite`), idéale pour un serveur unique dédié.
- **Ce qui n'est pas fait :** Pas de pilote de connexion direct vers un serveur MySQL ou PostgreSQL distant.
- **Raison :** Pour une agence locale (BEAC Bafoussam), SQLite offre d'excellentes performances (accès disque direct en mémoire partagée, absence de port réseau ouvert, zéro administration de serveur de base). En revanche, si la BEAC souhaite mutualiser cette base sur un cluster de serveurs de bases de données centralisé à Yaoundé, une abstraction multi-SGBD sera nécessaire.
- **Plan pour la V2.2 :**
  - Créer un adaptateur PDO abstrait supportant SQLite, PostgreSQL et MySQL via une variable `DB_DRIVER`.

### 2.7 Purge Définitive de l'Historique Git des Anciens Secrets V1
- **État actuel :** Tous les anciens fichiers non sécurisés (`gmispf.sql`, scripts avec mots de passe en clair) ont été supprimés de l'arbre de travail de la branche V2.
- **Ce qui n'est pas fait :** L'ancien commit initial de 2022 (`dfa9761a0aec56f2b81dfc91415051ce951d1265`) sur la branche `main` contient toujours l'historique historique de ces fichiers.
- **Raison :** La réécriture de l'historique d'un dépôt Git distant nécessite des privilèges d'administration GitHub avec poussée forcée (`git push --force`), ce qui doit être réalisé sous le contrôle exclusif du propriétaire du dépôt.
- **Action à réaliser par le propriétaire :**
  - Utiliser `git-filter-repo` ou `BFG Repo-Cleaner` sur le dépôt cloné pour expurger l'ancien fichier `gmispf.sql` de tous les commits historiques, ou créer un nouveau dépôt vierge à partir de la branche V2.

---

## 3. Matrice de Priorité des Évolutions Futures

| Fonctionnalité | Priorité | Effort Estimé | Dépendances |
| :--- | :---: | :---: | :--- |
| **Purge de l'historique Git des anciens secrets** | **Critique** | 1 heure | Accès administrateur GitHub du propriétaire |
| **Alertes E-mail SMTP sur urgences** | **Haute** | 1 à 2 jours | Serveur SMTP relais BEAC |
| **Téléversement de photos d'interventions** | **Haute** | 3 à 4 jours | Espace disque sécurisé, extension `fileinfo` |
| **Circuit d'approbation hiérarchique** | **Moyenne** | 2 jours | Spécification interne des rôles de validation |
| **Authentification double facteur (2FA TOTP)** | **Moyenne** | 2 à 3 jours | Application TOTP sur smartphone des agents |
| **Module d'inventaire complet des pièces** | **Basse (V3)** | 1 à 2 semaines | Définition du catalogue des pièces et coûts |
| **Support multi-SGBD (PostgreSQL / MySQL)** | **Optionnelle** | 3 jours | Infrastructure clusterisée |

---

## 4. Checklist Impérative Avant la Mise en Production

Avant d'ouvrir l'application GMISPF V2 aux utilisateurs réels de la BEAC, assurez-vous de réaliser les actions suivantes :

- [ ] **1. HTTPS :** Installer un certificat SSL/TLS valide et forcer la redirection HTTP vers HTTPS.
- [ ] **2. Révocation :** Changer tous les mots de passe et adresses e-mail qui figuraient dans l'ancien fichier `gmispf.sql`.
- [ ] **3. Isolation DocumentRoot :** Vérifier dans la configuration Apache ou Nginx que seul le sous-dossier `public/` est accessible depuis le navigateur.
- [ ] **4. Permissions du stockage :**
  ```bash
  chmod 755 /chemin/vers/GMISPF
  chmod 700 /chemin/vers/GMISPF/storage
  chmod 600 /chemin/vers/GMISPF/storage/app.sqlite
  ```
- [ ] **5. Sauvegardes automatisées :** Configurer une tâche cron quotidienne pour sauvegarder la base de données :
  ```bash
  0 2 * * * sqlite3 /var/www/gmispf/storage/app.sqlite ".backup '/var/backups/gmispf/gmispf_\$(date +\%F).sqlite'"
  ```
- [ ] **6. Production désactivée de la démo :** Définir `APP_ENV=production`, `APP_DEMO=0` et `APP_HTTPS=1`.
