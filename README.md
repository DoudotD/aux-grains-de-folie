# Aux Grains de Folie – site vitrine + actualités

Site de la boulangerie-pâtisserie *Aux Grains de Folie* (Coëx, Vendée).

| Partie | Fichiers | Technologie |
|---|---|---|
| Site vitrine (accueil) | `index.php`, `style.css` | HTML/CSS + PHP (aperçu des 2 dernières actualités) |
| Page « Actualités » | `actualites.php` | PHP 8.0+ (sans base de données) |
| Espace de gestion | `admin/` | PHP + JavaScript (éditeur WYSIWYG maison, sans dépendance) |
| Données | `data/` (JSON protégés), `uploads/` (images) | fichiers |

## Mise en ligne

1. Hébergement avec **PHP 8.0 ou plus** (extension **GD** recommandée pour redimensionner les photos).
2. Envoyer **tout le dossier** par FTP, **sauf** `tools/`, `.claude/` et `README.md`.
3. Donner les droits d'écriture à `data/` et `uploads/` (755, ou 775 selon l'hébergeur).
4. Ouvrir `https://votre-site/admin/` : le formulaire de première connexion s'affiche.
   - Il demande une **clé d'installation** : c'est le contenu de `data/setup.json.php` (à lire via FTP).
   - Créez ensuite le premier compte (nom, email, mot de passe de 10 caractères minimum).
5. Depuis **Mon compte**, ajoutez les autres personnes autorisées (Yoann, Tiffany…).

> Faites l'étape 4 immédiatement après la mise en ligne, tant qu'aucun compte n'existe.

## Utilisation (pour la personne qui saisit)

*Espace de gestion → Nouvelle actualité*, en 5 étapes numérotées : titre, image à la une, texte
(boutons Gras, Titre, Liste, Lien, **Image**, **Vidéo YouTube/Vimeo**), boutons d'action, auteur et date.
- **Aperçu avant publication** : rendu exact du site, en version ordinateur ou téléphone.
- **Brouillon** : enregistré sans être visible. **Date future** : publication programmée.
- Une sauvegarde automatique locale (navigateur) propose de restaurer le texte en cas de fermeture accidentelle.

## Page publique

- Les actualités les plus récentes sont en haut ; **10 par page**, avec pagination et lien « Tout afficher sur une seule page ».
- Archives **par année puis par mois**, bouton **« Haut »** flottant.
- Paramètres d'URL : `?annee=2026&mois=10&page=2`, `?tout=1`.
- Le nombre par page se règle dans `inc/bootstrap.php` (`PER_PAGE`).

## Sécurité – ce qui est en place

- Mots de passe hachés (`password_hash`), sessions `HttpOnly`/`SameSite`, déconnexion après 4 h d'inactivité.
- Jetons **CSRF** sur tous les formulaires et envois d'images.
- Blocage 15 min après **5 échecs** de connexion (par IP et par email).
- Le HTML saisi est **re-nettoyé côté serveur** (liste blanche, `inc/sanitize.php`) : scripts, attributs, iframes, liens `javascript:` supprimés.
- Images : type vérifié, **ré-encodées** (GD), renommées aléatoirement, exécution de scripts interdite dans `uploads/`.
- Fichiers de données `*.json.php` : commencent par un `exit` PHP, illisibles même sans `.htaccess`.
- En-têtes `Content-Security-Policy`, `X-Frame-Options`, `nosniff`.
- Vidéos intégrées via `youtube-nocookie.com` / `player.vimeo.com?dnt=1`.

## Limites à connaître

- **Pas d'envoi d'email** : pas de « mot de passe oublié ». Une autre personne autorisée peut recréer le compte
  (Mon compte → Retirer / Ajouter). Si plus personne ne peut se connecter, supprimer `data/users.json.php` par FTP.
- Sauvegarde : copier régulièrement les dossiers `data/` et `uploads/`.
- Les photos du site vitrine (`index.php`) sont chargées depuis Unsplash.

## Tester en local

```bash
php -S localhost:8000
```

Données de démonstration (25 actualités fictives, à ne pas déployer) :

```bash
GDF_DATA_DIR=/tmp/gdf-demo php tools/seed-demo.php 25
GDF_DATA_DIR=/tmp/gdf-demo php -S localhost:8000
```
