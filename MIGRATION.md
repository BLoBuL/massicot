# Migration de Massicot 1.x vers 2.x

Massicot 2 cible SPIP 4.x et PHP 8 ou supérieur. Il conserve les coordonnées
enregistrées par les versions précédentes, mais n’altère plus implicitement les
balises natives sur une installation neuve.

## Mise à jour d’un site existant

La mise à jour active automatiquement le mode de compatibilité. Les anciens
traitements de `#FICHIER`, `#URL_DOCUMENT`, `#LARGEUR`, `#HAUTEUR` et `#LOGO_*`
continuent alors de fonctionner. Les données sérialisées de Massicot 1.x restent
lisibles ; toute nouvelle sauvegarde les réécrit en JSON validé.

Le mode historique est transitoire. Pour le désactiver :

1. rechercher les squelettes qui comptent sur un recadrage implicite ;
2. leur appliquer explicitement `|massicoter_objet{objet,id_objet,role}` ;
3. vérifier les téléchargements, portfolios, logos et dimensions ;
4. désactiver le mode dans la configuration de Massicot ;
5. vider le cache des squelettes.

Le document original n’est jamais remplacé par la migration et les lignes des
tables `spip_massicotages` et `spip_massicotages_liens` sont conservées.

## Installation neuve

Le mode de compatibilité est désactivé. `#FICHIER` et `#URL_DOCUMENT` gardent
leur contrat SPIP natif. Le recadrage doit être demandé explicitement.

## Retour arrière

Avant désactivation définitive, le mode historique peut être réactivé depuis
la configuration. Cette bascule ne transforme ni ne supprime les données.
