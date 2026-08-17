<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de https://trad.spip.net/tradlang_module/massicot?lang_cible=en
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS[$GLOBALS['idx_lang']] = array(
	// C
	'configuration_titre' => 'Massicot migration',

	// E
	'erreur_fichier_image' => 'The image file cannot be found or read.',
	'erreur_image_trop_petite' => 'This image is too small for this preset.',
	'erreur_parametre_manquant' => 'The parameter @parametre@ is mandatory !',
	'erreur_parametres_invalides' => 'The crop parameters are invalid.',

	// L
	'label_annuler' => 'Cancel',
	'label_dimensions' => 'Size of the resulting cropped image : ',
	'label_format' => 'size presets : ',

	// M
	'massicot_titre' => 'Image cropper',
	'massicoter' => 'Crop the image',
	'massicoter_logo' => 'Crop logo',
	'massicoter_logo_survol' => 'Crop rollover logo',
	'selection_recadrage' => 'Crop selection. Use arrow keys to move it.',
	'supprimer_recadrage' => 'Remove crop',
	'filtres_titre' => 'Image rendering',
	'formats_titre' => 'Crop format',
	'format_libre' => 'Free',
	'filtre_aucun' => 'Original',
	'filtre_nb' => 'Black and white',
	'filtre_sepia' => 'Sepia',
	'filtre_lumineux' => 'Brighter',
	'filtre_sombre' => 'Darker',
	'filtre_net' => 'Sharper',
	'filtre_flou' => 'Soft blur',
	'exif_titre' => 'Image information (EXIF)',
	'rotation_titre' => 'Output rotation',
	'rotation_gauche' => 'Rotate 90° left',
	'rotation_droite' => 'Rotate 90° right',
	'rotation_aucune' => 'No rotation',
	'rotation_demi_tour' => 'Rotate 180°',
	'rotation_apercu' => 'Final output preview',
	'rotation_explication' => 'Output uses SPIP 4 image_rotation(), checked and corrected by Massicot. EXIF orientation is normalized automatically.',
	'exif_dimensions' => 'Dimensions',
	'exif_mime' => 'Format',
	'exif_appareil' => 'Camera',
	'exif_objectif' => 'Lens',
	'exif_prise_de_vue' => 'Captured',
	'exif_exposition' => 'Exposure',
	'exif_ouverture' => 'Aperture',
	'exif_iso' => 'Sensitivity',
	'exif_focale' => 'Focal length',
	'zone_recadrage' => 'Interactive crop area',
	'mode_compatibilite_explication' => 'When this option is enabled, templates built for Massicot 1.x continue to apply saved crops automatically to documents and logos. Keep it enabled until those templates have been updated. You can disable it once images that require cropping explicitly use the massicoter_objet filter. Disabling it does not delete images or saved crops.',
	'mode_compatibilite_label' => 'Compatibility with Massicot 1.x templates',

	// R
	'reinitialiser' => 'Reset',

	// Z
	'zoom' => 'Zoom'
);
