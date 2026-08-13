<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// Fichier source, a modifier dans https://git.spip.net/spip-contrib-extensions/massicot.git
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS[$GLOBALS['idx_lang']] = array(
	// C
	'configuration_titre' => 'Migration de Massicot',

	// E
	'erreur_image_trop_petite' => 'Cette image est trop petite pour ce format.',
	'erreur_fichier_image' => 'Le fichier image est introuvable ou illisible.',
	'erreur_parametre_manquant' => 'Le paramètre @parametre@ est obligatoire !',
	'erreur_parametres_invalides' => 'Les paramètres de recadrage sont invalides.',

	// L
	'label_annuler' => 'Annuler',
	'label_dimensions' => 'Taille de l’image recadrée en pixels : ',
	'label_format' => 'dimensions prédéfinies : ',

	// M
	'massicot_titre' => 'Massicot',
	'massicoter' => 'Recadrer l’image',
	'massicoter_logo' => 'Recadrer le logo',
	'massicoter_logo_survol' => 'Recadrer le logo de survol',
	'selection_recadrage' => 'Sélection de recadrage. Utilisez les flèches pour la déplacer.',
	'supprimer_recadrage' => 'Supprimer le recadrage',
	'filtres_titre' => 'Rendu de l’image',
	'filtre_aucun' => 'Original',
	'filtre_nb' => 'Noir et blanc',
	'filtre_sepia' => 'Sépia',
	'filtre_lumineux' => 'Plus clair',
	'filtre_sombre' => 'Plus sombre',
	'filtre_net' => 'Plus net',
	'filtre_flou' => 'Flou doux',
	'exif_titre' => 'Informations de l’image (EXIF)',
	'rotation_titre' => 'Rotation de sortie',
	'rotation_gauche' => 'Tourner de 90° vers la gauche',
	'rotation_droite' => 'Tourner de 90° vers la droite',
	'rotation_aucune' => 'Aucune rotation',
	'rotation_demi_tour' => 'Tourner de 180°',
	'rotation_apercu' => 'Aperçu du rendu final',
	'rotation_explication' => 'Le rendu utilise image_rotation() de SPIP 4, contrôlé et corrigé par Massicot. L’orientation EXIF est normalisée automatiquement.',
	'exif_dimensions' => 'Dimensions',
	'exif_mime' => 'Format',
	'exif_appareil' => 'Appareil',
	'exif_objectif' => 'Objectif',
	'exif_prise_de_vue' => 'Prise de vue',
	'exif_exposition' => 'Exposition',
	'exif_ouverture' => 'Ouverture',
	'exif_iso' => 'Sensibilité',
	'exif_focale' => 'Focale',
	'zone_recadrage' => 'Zone interactive de recadrage',
	'mode_compatibilite_explication' => 'À conserver temporairement après une mise à jour depuis Massicot 1.x. Ce mode modifie globalement les balises de documents et de logos. Désactivez-le après avoir remplacé ces usages par le filtre explicite massicoter_objet.',
	'mode_compatibilite_label' => 'Activer les traitements automatiques historiques (déprécié)',

	// O
	'operation_non_autorisee' => 'Opération non autorisée.',

	// R
	'reinitialiser' => 'Réinitialiser',

	// Z
	'zoom' => 'Zoom'
);
