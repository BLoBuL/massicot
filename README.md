# Massicot 2

Massicot fournit un recadrage non destructif pour les logos et documents image
de SPIP 4. Les sources éditoriales restent intactes ; les rendus sont produits
par les filtres d’image et le cache natifs de SPIP.

## Compatibilité

- SPIP 4.x ;
- PHP 8.0 ou supérieur ;
- JPEG, PNG, GIF et WebP selon le moteur d’image disponible ;
- images locales et distantes, ces dernières étant localisées avec
  `copie_locale()`.

AVIF, SVG et les documents non raster ne proposent pas l’action de recadrage :
les filtres d’image natifs communs à SPIP 4.2 et 4.4 ne prennent pas en charge
AVIF de façon portable.

## Interface

Les actions sont injectées par les pipelines de SPIP dans :

- le formulaire natif des logos ;
- la médiathèque et les descriptions de documents ;
- l’écran d’édition détaillé d’un document ;
- la gestion d’une vignette de document.

Le recadreur est responsive, tactile et utilisable au clavier. Les flèches
déplacent la sélection ou une poignée ; `Maj` augmente le pas.

La colonne d’outils propose des rendus calculés par les filtres natifs de
SPIP 4 : noir et blanc, sépia, luminosité, assombrissement, renforcement et
flou doux. Leur aperçu est immédiat, mais le dérivé final reste produit côté
serveur par SPIP.

Un panneau EXIF en lecture seule affiche les dimensions, le format et, quand
ils existent, l’appareil, l’objectif et les réglages photographiques. Les
coordonnées GPS et les champs libres ne sont jamais exposés.

L’orientation EXIF est matérialisée avant le recadrage avec l’API adoptée par
SPIP 5 (`image_oriente_selon_exif`) lorsqu’elle est disponible. Des boutons
permettent ensuite une rotation de sortie à 90°, 180° ou 270°. Massicot contrôle
les dimensions du dérivé SPIP et utilise un repli GD en cas de résultat
incohérent, sans modifier le fichier éditorial original.

## Squelettes

L’API explicite recommandée est :

```html
[(#FICHIER|massicoter_objet{document,#ID_DOCUMENT})]
[(#LOGO_ARTICLE|massicoter_objet{article,#ID_ARTICLE})]
```

Consulter [MIGRATION.md](MIGRATION.md) pour sortir progressivement du mode de
compatibilité des versions 1.x.

## Tests

```sh
php tests/test_parametres.php
php tests/test_migration.php
php tests/test_cache.php
php tests/test_autorisations.php
php tests/test_html.php
SPIP_ROOT=/chemin/vers/spip php tests/test_spip_runtime.php
```
