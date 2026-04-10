# FEVdi FDS Manager

Plugin WordPress pour gérer un espace privé de fiches de données de sécurité (FDS) avec scan de dossiers, téléchargement sécurisé, upload administrateur et association automatique aux produits WooCommerce.

## Fonctionnalités

- Liste publique/privée des FDS via shortcode.
- Téléchargement sécurisé avec nonce WordPress et accès utilisateur.
- Journalisation des téléchargements.
- Espace compte utilisateur avec historique récent.
- Validation administrateur des demandes d'accès.
- Association manuelle ou automatique des FDS aux produits WooCommerce.
- Matching produit amélioré avec normalisation, SKU prioritaire et score fuzzy.
- Page admin premium `FDS Manager`.
- Table admin AJAX avec pagination serveur.
- Filtres avancés admin : recherche, répertoire, type, dates et nombre de lignes par page.
- Tri AJAX par type, nom, taille et date.
- Upload AJAX multi-fichiers PDF.
- Suppression AJAX sécurisée.
- Export CSV des logs.

## Prérequis

- WordPress
- PHP 8.1+
- WooCommerce pour l'affichage automatique des boutons FDS sur les fiches produits

Les fichiers sont lus depuis :

- `wp-content/uploads/FDS/Francais`
- `wp-content/uploads/FDS/Multilingue`

Le plugin crée le rôle `fevdi_fds_customer` et la table de logs `{$wpdb->prefix}fevdi_fds_logs`.

## Shortcodes

### Liste des FDS

```text
[fds_list]
```

Options disponibles :

```text
[fds_list dir="fr" per_page="10" search="true" title="Fiches de sécurité"]
```

Paramètres :

- `dir` : `fr` ou `multi`
- `per_page` : nombre de lignes par page
- `search` : `true` ou `false`
- `title` : titre affiché au-dessus de la liste

### Upload front admin

```text
[fds_upload]
```

Affiche un formulaire d'upload réservé aux administrateurs. La nouvelle page admin utilise aussi un upload AJAX plus complet.

### Inscription utilisateur

```text
[fds_register_form]
```

Permet à un visiteur de demander un accès FDS. Le compte reste en attente jusqu'à validation.

### Espace compte

```text
[fds_account]
```

Affiche l'état du compte et les derniers téléchargements de l'utilisateur connecté.

## Administration

La page `FDS Manager` est ajoutée au menu principal WordPress.

Elle permet de :

- consulter les fichiers FDS avec pagination AJAX réelle ;
- filtrer par texte, répertoire, extension et dates de modification ;
- modifier le nombre de lignes par page ;
- trier les résultats sans recharger la page ;
- uploader plusieurs PDF en AJAX ;
- supprimer un fichier en AJAX ;
- télécharger un fichier directement depuis la table ;
- exporter les logs au format CSV.

L'export CSV est protégé par nonce et réservé aux utilisateurs ayant la capacité `manage_options`.

## Logs

Chaque téléchargement valide est enregistré avec :

- l'identifiant utilisateur ;
- le nom du fichier ;
- le répertoire (`fr` ou `multi`) ;
- la date ;
- l'adresse IP.

Les logs sont consultables depuis la page admin et exportables en CSV.

## Matching WooCommerce

Dans l'édition produit WooCommerce, deux champs sont ajoutés :

- `FDS Français`
- `FDS Multilingue`

Chaque champ peut être renseigné manuellement. Si aucun fichier n'est sélectionné, le plugin tente une association automatique.

Ordre de priorité du matching automatique :

1. Correspondance SKU dans le nom de fichier.
2. Correspondance exacte du nom produit normalisé.
3. Score fuzzy basé sur similarité, tokens significatifs et distance Levenshtein.

Les termes génériques comme `fds`, `fiche`, `safety`, `data`, `sheet`, `pdf`, `fr`, `multi` et `multilingue` sont ignorés pendant la normalisation.

## Sécurité

- Accès admin limité à `manage_options`.
- Téléchargements protégés par nonce.
- Upload limité aux fichiers PDF.
- Suppression protégée par nonce et contrôle `realpath`.
- Exports CSV protégés par nonce.
- Noms de fichiers nettoyés avec les fonctions WordPress.

## Fichiers principaux

- `fevdi-fds-manager.php` : bootstrap, constantes, activation, table logs.
- `includes/Admin.php` : page admin, endpoints AJAX, export CSV.
- `includes/Scanner.php` : scan des dossiers FDS.
- `includes/Upload.php` : shortcode d'upload existant.
- `includes/Download.php` : téléchargement sécurisé et journalisation.
- `includes/Logs.php` : écriture, lecture et filtrage des logs.
- `includes/Matcher.php` : matching automatique produit/FDS.
- `includes/Product.php` : intégration WooCommerce produit.
- `assets/js/fds-manager.js` : tables publiques et interactions admin AJAX.
- `assets/css/fds-manager.css` : styles publics et admin.

## Version

Version actuelle : `2.2.0`
