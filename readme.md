# OpenClassrooms - Projet 12 : Créez une API avec Symfony pour mettre à disposition des données

EcoGarden & co est une entreprise spécialisée dans le domaine du jardinage et de l’agriculture écologique. Elle vise à encourager les pratiques durables et respectueuses de l’environnement pour aider les amateurs de jardinage à cultiver leurs propres plantes, légumes et herbes aromatiques.

Elle veut alors rendre les informations sur son site web disponibles pour leurs sites partenaires et des associations pour qu’un public plus large puisse commencer à cultiver ses propres plantes. Pour ce faire, elle a décidé de mettre à disposition une API qui sera en accès libre.

# API Symfony 7.4 - Gestion de Météo et Conseils dans le domaine du jardinage

Cette API robuste, développée avec Symfony 7.4, offre un ensemble de services pour la gestion d'utilisateurs, la consultation de données météorologiques en temps réel, et la fourniture de conseils dans le dommaine du jardinage. Elle est conçue pour être performante et sécurisée, intégrant l'authentification JWT et des mécanismes d'autorisation précis.

## Fonctionnalités principales

- **Gestion des Utilisateurs** : Création, consultation, mise à jour et suppression de profils utilisateurs.
- **Consultation Météo** :
    - Récupération des conditions météorologiques actuelles pour la ville de l'utilisateur connecté.
    - Accès aux données météorologiques de n'importe quelle ville spécifiée.
    - Système de cache intégré pour optimiser les performances et réduire les appels à l'API externe.
- **Gestion des Conseils dans le domaine du jardinage** :
    - Affichage d'une liste de conseils.
    - Filtrage des conseils par mois spécifique ou récupération des conseils du mois courant.
    - Opérations CRUD complètes (Créer, Lire, Mettre à jour, Supprimer) pour les conseils, avec des autorisations basées sur les rôles.

## Focus Sécurité (Authentification & Autorisations)

Cette API implémente une stratégie de sécurité complète basée sur JWT (JSON Web Tokens) et les fonctionnalités natives de Symfony.

### Authentification JWT

L'API utilise le bundle `LexikJWTAuthenticationBundle` pour gérer l'authentification par token.

- **Génération de Token** : Lors d'une requête `POST` sur la route `/api/auth` avec des identifiants valides (email et mot de passe), un token JWT est généré et retourné à l'utilisateur. Ce token doit être inclus dans l'en-tête `Authorization: Bearer <token>` de toutes les requêtes subséquentes aux routes sécurisées.
- **Durée de Vie** : La durée de vie des tokens est configurable dans `config/packages/lexik_jwt_authentication.yaml`.
- **Route de Login** :

    ```
    POST /api/auth
    Content-Type: application/json

    {
        "username": "user@example.com",
        "password": "your_password"
    }
    ```

### Mécanismes d'Autorisation Symfony

Les autorisations sont gérées via une combinaison de la hiérarchie des rôles et de l'attribut `#[IsGranted]`.

- **Hiérarchie des Rôles** : Le fichier `config/packages/security.yaml` définit la structure des rôles et leurs dépendances. Par défaut, `ROLE_USER` est attribué à tous les utilisateurs, et `ROLE_ADMIN` confère des privilèges élevés.
- **Attribut `#[IsGranted]`** : Cet attribut est utilisé directement dans les contrôleurs pour restreindre l'accès à certaines actions basées sur les rôles de l'utilisateur.
    - Exemple dans `ConseilController.php` et `UserController.php` : Les opérations de création, mise à jour et suppression (`POST`, `PUT`, `DELETE`) sont protégées par `#[IsGranted('ROLE_ADMIN')]`, garantissant que seuls les administrateurs peuvent effectuer ces actions.
    - Exemple dans `MeteoController.php` : La suppression du cache est protégée par `#[IsGranted('ROLE_ADMIN')]`.
- **Accès Général** : Le `security.yaml` spécifie que toutes les routes sous `/api` (sauf `/api/auth` et `/api/user` pour la création) requièrent `IS_AUTHENTICATED_FULLY`, ce qui signifie qu'un utilisateur doit être authentifié pour y accéder.

## Focus Performances & Intégrations externes

Le module météorologique est un exemple clé d'intégration externe et d'optimisation des performances.

### Intégration de l'API Météo Externe

L'API s'intègre avec une API météorologique externe pour récupérer les données.

- **Nom/URL de l'API** : L'intégration utilise une API externe dont l'URL de base est configurée via la variable d'environnement `WEATHER_API_BASE_URL` et authentifiée via `WEATHER_API_KEY`.
- **Requêtes** : Le `HttpClientInterface` de Symfony est utilisé pour effectuer des requêtes sécurisées et performantes vers cette API tierce.

### Stratégie d'Optimisation par Cache

Pour optimiser les temps de réponse et éviter de surcharger l'API externe, un mécanisme de cache est implémenté sur les endpoints météo.

- **Composant de Cache Symfony** : Le `TagAwareCacheInterface` du composant Cache de Symfony est utilisé pour stocker temporairement les résultats des requêtes à l'API météo.
- **Durée de Vie (TTL)** : La durée de vie des données en cache est définie par la valeur du paramètre 'app.weatherapi_cache_ttl' dans le fichier de configuration 'config/services.yaml'. Le contrôleur `MeteoController` utilise cette valeur pour la configuration du cache.
- **Invalidation du Cache** : Un endpoint `DELETE /api/meteo` est disponible (restreint aux `ROLE_ADMIN`) pour invalider manuellement le cache météorologique, ce qui est utile pour forcer une récupération de données fraîches.

## Pile technique et Dépendances

### Stack de Base

- **PHP** : `^8.2`
- **Framework** : Symfony `7.4.*`
- **Base de Données** : MySQL '8.1'

### Bundles Majeurs Installés

| Bundle                                    | Rôle Spécifique dans l'Application                                                               |
| :---------------------------------------- | :----------------------------------------------------------------------------------------------- |
| `doctrine/doctrine-bundle`                | Intégration de Doctrine ORM pour la gestion des entités et la persistance des données.           |
| `doctrine/orm`                            | Couche Object-Relational Mapping pour interagir avec la base de données.                         |
| `doctrine/doctrine-migrations-bundle`     | Gère les migrations de base de données pour évoluer le schéma.                                   |
| `lexik/jwt-authentication-bundle`         | Implémente l'authentification par token JWT pour sécuriser l'API.                                |
| `symfony/http-client`                     | Client HTTP pour effectuer des requêtes vers des services externes (ex: API météo).              |
| `symfony/cache`                           | Fournit un système de cache robuste utilisé pour optimiser les requêtes météo.                   |
| `symfony/security-bundle`                 | Gère l'authentification, l'autorisation et la sécurité globale de l'application.                 |
| `symfony/serializer`                      | Permet la sérialisation et la désérialisation des objets PHP en/depuis des formats comme JSON.   |
| `symfony/validator`                       | Valide les données des entités et les requêtes entrantes selon des contraintes définies.         |
| `symfony/dotenv`                          | Charge les variables d'environnement depuis le fichier `.env`.                                   |
| `symfony/flex`                            | Facilite l'installation et la configuration des paquets Symfony.                                 |
| `symfony/maker-bundle` (dev)              | Outil de développement pour générer rapidement du boilerplate code (entités, contrôleurs, etc.). |
| `doctrine/doctrine-fixtures-bundle` (dev) | Permet de charger des jeux de données de test dans la base de données.                           |

## Installation et Démarrage rapide

Suivez ces étapes pour configurer et lancer le projet API sur votre machine locale.

1.  **Cloner le dépôt** :

    ```bash
    git clone [URL_DU_DEPOT]
    cd OCR-Projet12
    ```

2.  **Installer les dépendances Composer** :

    ```bash
    composer install
    ```

3.  **Configurer les variables d'environnement** :
    Copiez le fichier d'exemple `.env` et mettez à jour les valeurs nécessaires.

    ```bash
    cp .env .env.local
    ```

    Éditez `.env.local` et configurez au minimum :
    - `DATABASE_URL="mysql://app:!ChangeMe!@127.0.0.1:3306/app?serverVersion=8.0.*&charset=utf8mb4"`
    - `WEATHER_API_KEY="[VOTRE_CLE_API_METEO]"` : Clé d'accès à l'API météo externe.
    - `WEATHER_API_BASE_URL="[URL_DE_BASE_API_METEO]"` : URL de base de l'API météo externe.

    Éditez `config/services.yaml` et configurez au minimum :
    - 'app.weatherapi_cache_ttl:' Durée de vie du cache réponses API météo (en secondes)

4.  **Générer les clés SSL JWT** :
    Ces clés sont essentielles pour l'authentification JWT.

    ```bash
    mkdir -p config/jwt
    openssl genpkey -out config/jwt/private.pem -aes256 -alg rsa -pkeyopt rsa_keygen_bits:4096
    openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
    ```

    Lorsque vous y êtes invité, saisissez une phrase secrète. **N'oubliez pas cette phrase secrète**, elle devra être définie dans votre fichier `.env.local` (ou une autre variable d'environnement pour la production) comme suit :

    ```
    JWT_PASSPHRASE="[VOTRE_PHRASE_SECRETE_OPENSSL]"
    ```

5.  **Créer la base de données** :

    ```bash
    php bin/console doctrine:database:create
    ```

6.  **Exécuter les migrations de base de données** :

    ```bash
    php bin/console doctrine:migrations:migrate
    ```

7.  **Charger les fixtures (optionnel, pour le développement)** :

    ```bash
    php bin/console doctrine:fixtures:load
    ```

8.  **Démarrer le serveur de développement Symfony** :
    ```bash
    symfony server:start
    ```
    L'API sera accessible à `http://127.0.0.1:8000` (ou le port indiqué par Symfony CLI).
