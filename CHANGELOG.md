# Journal des changements

## [2.3.1] - 2026-08-17

### Corrigé

- suppression de l'inclusion qui tentait de compiler la feuille statique
  `css/massicot.css` comme un squelette SPIP inexistant ;
- conservation du chargement ciblé des styles et du JavaScript sur l'écran de
  recadrage via le pipeline `header_prive`.

## [2.3.0] - 2026-08-14

### Ajouté

- préréglages de recadrage libre, 1:1, 2:1, 1:2, 3:4 et 4:3 ;
- recalcul centré immédiat de la sélection et de l’aperçu final ;
- verrouillage du rapport lors du redimensionnement de la sélection.

## [2.2.0] - 2026-08-13

### Ajouté

- accès direct dans le recadreur aux rotations SPIP 4 à 0°, 90°, 180° et 270° ;
- aperçu du rendu final combinant recadrage, filtre et rotation ;
- état visuel et accessible de la rotation sélectionnée.

### Corrigé

- proportion de l’aperçu final désormais calculée depuis le recadrage réel et
  inversée automatiquement pour les rotations à 90° et 270° ;
- les rotations de l’interface passent par `image_rotation()`, puis par le
  contrôle de dimensions et le repli GD de Massicot si SPIP 4 échoue ;
- l’aperçu ne transforme pas les coordonnées de sélection : la rotation reste
  appliquée après le recadrage, comme côté serveur.

## [2.1.2] - 2026-08-13

### Corrigé

- normalisation de l’orientation EXIF des JPEG locaux produits par les modèles SPIP 4 (`<docXX>`, `<imgXX>` et portfolios) via `post_propre` ;
- conservation des attributs HTML et des candidats `srcset` ;
- aucune modification des originaux ni des URL distantes.

## [2.1.1] - 2026-08-13

### Ajouté

- rotations de sortie 0°, 90°, 180° et 270° ;
- tests raster asymétriques vérifiant pixels et dimensions pour JPEG, PNG, GIF et WebP ;
- test JPEG avec orientation EXIF 6, représentatif des appareils mobiles.

### Corrigé

- orientation EXIF matérialisée avant recadrage avec `image_oriente_selon_exif()` en priorité, conformément à SPIP 5 ;
- contrôle du résultat de `image_rotation()` sous SPIP 4 et repli GD mis en cache ;
- transparence préservée lors du repli.

## [2.1.0] - 2026-08-13

### Ajouté

- interface responsive, tactile et utilisable au clavier ;
- filtres SPIP : original, noir et blanc, sépia, clair, sombre, net et flou doux ;
- prévisualisation immédiate et panneau EXIF sans GPS ni champs libres.

### Modifié

- dimensions du recadrage conservées après filtrage ;
- paramètres `filtre` et `rotation` rétrocompatibles, sans migration de table.

## [2.0.1] - 2026-08-13

### Corrigé

- compatibilité SPIP 4 et PHP 8 ;
- intégration non destructive aux logos, documents et vignettes ;
- logos distants localisés avec l’API SPIP ;
- invalidation des règles au remplacement d’une image ;
- cache isolé par objet et rôle, et index de lecture des liens.

### Migration

- données sérialisées Massicot 1.x toujours lisibles ;
- réécriture en JSON validé lors de la prochaine sauvegarde ;
- mode de compatibilité activé uniquement lors d’une mise à jour.
