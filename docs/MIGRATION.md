# Guide de Migration : GMISPF V1 vers V2

Ce guide décrit la démarche pour migrer une installation existante de **GMISPF V1** (architecture MySQL non sécurisée avec pages PHP directes) vers **GMISPF V2** (architecture moderne isolée sous PHP 8.2+ et SQLite).

---

## 1. Pourquoi migrer ? (Différences majeures)

| Aspect | GMISPF V1 | GMISPF V2 |
| :--- | :--- | :--- |
| **Base de données** | MySQL (`gmispf.sql`), connexions ouvertes en dur | SQLite mono-fichier local (`storage/app.sqlite`), mode WAL |
| **Mots de passe** | Mots de passe stockés en clair | Hachage adaptatif `bcrypt` (coût 12) |
| **Contrôle d'accès** | Absence de vérification de session sur la plupart des pages | RBAC strict (`admin`, `agent`, `prestataire`), sessions signées |
| **Sécurité SQL** | Concaténation directe (`mysql_query` / `$bdd->query`) | 100% requêtes préparées PDO sans émulation |
| **Sécurité Web** | Fichiers PHP accessibles directement, failles CSRF/XSS | `public/index.php` unique DocumentRoot, jetons CSRF, CSP stricte |
| **Suppression / Audit** | Suppression physique directe sans confirmation (`delete.php?idrapport=X`) | Archivage logique avec contrôle de concurrence, journal d'audit immuable |

---

## 2. Préparation avant migration

### Avertissement critique sur la sécurité
Les mots de passe de la V1 étaient stockés en clair dans la base et certains identifiants figuraient dans le code ou l'historique de version.
**Ne réutilisez aucun des anciens mots de passe en production.** Tous les comptes doivent recevoir un nouveau mot de passe fort via la console CLI.

### Sauvegarde
Exportez un dump SQL propre de votre base de données MySQL V1 :
```bash
mysqldump -u utilisateur -p gmispf > sauvegarde_gmispf_v1.sql
```

---

## 3. Utilisation de l'outil automatisé `bin/migrate_v1.php`

Un script de migration autonome est fourni dans `bin/migrate_v1.php`. Il effectue :
1. L'analyse du dump SQL V1.
2. La normalisation des données :
   - Catégories : mappage automatique vers `Plomberie`, `Sanitaire`, `Forage`.
   - Priorités : `Faible`, `Moyenne`, `Urgente`.
   - États : `À suivre`, `Moyen`, `Critique`.
   - Statuts : détection des mentions « résolue » ou « terminée » dans l'historique.
3. L'attribution automatique au prestataire ou administrateur V2 sélectionné.
4. L'enregistrement des événements dans la table d'audit V2.

### Étape 1 : Initialiser la base V2
```bash
cp .env.example .env
php bin/console.php init
php bin/console.php user:create admin@beac.int "Administrateur BEAC" admin
```

### Étape 2 : Simulation préalable (Dry-run)
Vérifiez l'extraction des données sans modifier la base :
```bash
php bin/migrate_v1.php sauvegarde_gmispf_v1.sql --import-users --dry-run
```

### Étape 3 : Exécution de la migration
```bash
php bin/migrate_v1.php sauvegarde_gmispf_v1.sql --import-users
```

Si vous souhaitez attribuer toutes les interventions à un utilisateur spécifique (par exemple l'ID 1) :
```bash
php bin/migrate_v1.php sauvegarde_gmispf_v1.sql --owner-id=1
```

---

## 4. Post-migration et vérifications

1. **Renouveler les mots de passe des utilisateurs importés :**
   Les utilisateurs importés reçoivent un mot de passe temporaire aléatoire. Pour réinitialiser le mot de passe d'un utilisateur :
   ```bash
   php bin/console.php user:password email@exemple.com
   ```
2. **Vérifier les données importées :**
   Lancez le serveur local de test :
   ```bash
   php -S 0.0.0.0:8080 -t public public/router.php
   ```
   Connectez-vous avec le compte administrateur et consultez la liste des interventions ainsi que le journal d'activité.
3. **Supprimer le fichier de dump :**
   Ne conservez pas le fichier `sauvegarde_gmispf_v1.sql` sur le serveur Web ou dans le dépôt Git.
