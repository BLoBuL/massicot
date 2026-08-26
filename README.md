# Massicot 2

Massicot fournit un recadrage non destructif pour les logos et documents image de SPIP 4. Les sources éditoriales restent intactes ; les rendus sont produits par les filtres d’image et les caches SPIP.

- [Installation et mise à jour](#installation-et-mise-à-jour)
- [Utilisation](#utilisation)
- [Rotation et orientation EXIF](#rotation-et-orientation-exif)
- [Utilisation dans les squelettes](#utilisation-dans-les-squelettes)
- [Limites](#limites)
- [Tests](#tests)
- [Journal des changements](CHANGELOG.md)

## Compatibilité

- SPIP 4.x ;
- PHP 8.0 ou supérieur ;
- JPEG, PNG, GIF et WebP selon le moteur d’image disponible ;
- images distantes localisées avec `copie_locale()` lorsqu’elles passent explicitement par Massicot.

AVIF, SVG et les documents non raster ne proposent pas l’action de recadrage.

## Installation et mise à jour

Installer le plugin dans `plugins/auto/`, puis l’activer depuis la gestion des plugins de SPIP. Une installation neuve laisse les balises natives inchangées.

Lors d’une mise à jour depuis Massicot 1.x, le mode de compatibilité est activé automatiquement. Les coordonnées et liens existants sont conservés. Les anciens paramètres sérialisés restent lisibles et sont réécrits en JSON lors de la prochaine sauvegarde. Consulter [MIGRATION.md](MIGRATION.md) avant de désactiver ce mode.

## Utilisation

Depuis un logo, un document image ou une vignette, choisir **Recadrer** :

1. régler le zoom ;
2. choisir éventuellement un format libre, 1:1, 2:1, 1:2, 3:4 ou 4:3 ;
3. déplacer ou redimensionner la sélection avec la souris, le tactile ou les flèches (`Maj` augmente le pas) ;
4. sélectionner éventuellement un filtre ;
5. choisir une rotation de sortie avec les commandes 0°, 90°, 180° ou 270° et
   contrôler le résultat dans l’aperçu final ;
6. valider avec **Recadrer l’image**.

**Réinitialiser** restaure l’image entière, le rendu original et une rotation nulle. **Supprimer le recadrage** efface uniquement la règle Massicot. Le fichier source n’est jamais remplacé.

Les filtres disponibles utilisent les fonctions natives de SPIP 4 : noir et blanc, sépia, luminosité, assombrissement, renforcement et flou doux. Leur aperçu est immédiat ; le dérivé final est calculé côté serveur.

Le panneau EXIF affiche uniquement dimensions, format, appareil, objectif et réglages photographiques disponibles. Les coordonnées GPS et champs libres ne sont jamais exposés.

## Rotation et orientation EXIF

Massicot distingue deux opérations :

- l’orientation EXIF est corrigée automatiquement avant le recadrage pour accorder pixels, dimensions et affichage ;
- la rotation de sortie est un choix éditorial appliqué après le recadrage.

La correction privilégie `image_oriente_selon_exif()` lorsqu’elle existe, comme dans SPIP 5 et les versions récentes de Filtres Images. Sous SPIP 4, Massicot vérifie le dérivé et utilise un repli GD mis en cache si le résultat est absent ou de dimensions incohérentes.

Le pipeline `post_propre` applique cette normalisation aux JPEG locaux produits par les modèles SPIP (`<docXX>`, `<imgXX>` et portfolios). Il conserve les attributs HTML et `srcset`, sans modifier les originaux.

Les commandes de l’interface appellent `image_rotation()` de SPIP 4. Massicot
vérifie ensuite les dimensions attendues pour chaque quart de tour et déclenche
son repli GD uniquement si le résultat natif est incorrect. L’aperçu combine la
sélection, le filtre et la rotation sans modifier la zone de travail : les
coordonnées restent celles de l’image orientée avant rotation de sortie.

## Utilisation dans les squelettes

L’API explicite recommandée est :

```html
[(#FICHIER|massicoter_objet{document,#ID_DOCUMENT})]
[(#LOGO_ARTICLE|massicoter_objet{article,#ID_ARTICLE})]
[(#LOGO_ARTICLE_SURVOL|massicoter_objet{article,#ID_ARTICLE,logo_survol})]
```

Le filtre retourne le chemin du dérivé. Sans règle valide, il restitue le fichier reçu sans altérer le contrat des balises natives.

## Limites

- le pipeline global corrige les JPEG locaux présents dans le HTML produit par les modèles ;
- une URL écrite directement dans `background-image` ne passe pas par `post_propre` : employer un dérivé explicite ;
- les URL distantes ne sont pas réécrites par le pipeline global ;
- AVIF et SVG ne sont pas recadrés dans la série 2.x ;
- les animations GIF ne sont pas garanties après un traitement GD ;
- Massicot corrige son périmètre d’affichage sans modifier le cœur de SPIP 4 ni les originaux de `IMG/`.

## Tests

```sh
php tests/test_parametres.php
php tests/test_migration.php
php tests/test_cache.php
php tests/test_autorisations.php
php tests/test_html.php
SPIP_ROOT=/chemin/vers/spip php tests/test_spip_runtime.php
```

La CI couvre SPIP 4.2/PHP 8.1 et SPIP 4.4/PHP 8.4.
