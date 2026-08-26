<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS[$GLOBALS['idx_lang']] = array(
	// C
	'configuration_titre' => 'Migración de Massicot',

	// D
	'diagnostic_limite' => 'Massicot no puede comprobar automáticamente si todas las plantillas del sitio utilizan el filtro massicoter_objet.',
	'diagnostic_migration_titre' => 'Estado de la migración',
	'diagnostic_mode_actif' => 'La compatibilidad con Massicot 1.x está activada.',
	'diagnostic_mode_inactif' => 'El sitio utiliza el funcionamiento moderno de Massicot.',
	'diagnostic_recadrage_un' => 'Se conserva un recorte guardado.',
	'diagnostic_recadrages_plusieurs' => 'Se conservan @nb@ recortes guardados.',

	// E
	'erreur_fichier_image' => 'No se encuentra el archivo de imagen o no se puede leer.',
	'erreur_image_trop_petite' => 'Esta imagen es demasiado pequeña para este formato.',
	'erreur_parametre_manquant' => 'El parámetro @parametre@ es obligatorio.',
	'erreur_parametres_invalides' => 'Los parámetros de recorte no son válidos.',
	'exif_appareil' => 'Cámara',
	'exif_dimensions' => 'Dimensiones',
	'exif_exposition' => 'Exposición',
	'exif_focale' => 'Distancia focal',
	'exif_iso' => 'Sensibilidad',
	'exif_mime' => 'Formato',
	'exif_objectif' => 'Objetivo',
	'exif_ouverture' => 'Apertura',
	'exif_prise_de_vue' => 'Captura',
	'exif_titre' => 'Información de la imagen (EXIF)',

	// F
	'filtre_aucun' => 'Original',
	'filtre_flou' => 'Desenfoque suave',
	'filtre_lumineux' => 'Más clara',
	'filtre_nb' => 'Blanco y negro',
	'filtre_net' => 'Más nítida',
	'filtre_sepia' => 'Sepia',
	'filtre_sombre' => 'Más oscura',
	'filtres_titre' => 'Representación de la imagen',
	'format_libre' => 'Libre',
	'formats_titre' => 'Formato de recorte',

	// L
	'label_annuler' => 'Cancelar',
	'label_dimensions' => 'Tamaño de la imagen recortada en píxeles:',
	'label_format' => 'Dimensiones predefinidas:',

	// M
	'massicot_titre' => 'Massicot',
	'massicoter' => 'Recortar la imagen',
	'massicoter_logo' => 'Recortar el logotipo',
	'massicoter_logo_survol' => 'Recortar el logotipo al pasar el puntero',
	'mode_compatibilite_explication' => 'Cuando esta opción está marcada, las plantillas diseñadas para Massicot 1.x siguen aplicando automáticamente los recortes guardados a los documentos y logotipos. Déjela marcada hasta que se hayan adaptado esas plantillas. Podrá desmarcarla cuando las imágenes que deban recortarse utilicen explícitamente el filtro massicoter_objet. La desactivación no elimina imágenes ni recortes guardados.',
	'mode_compatibilite_label' => 'Compatibilidad con las plantillas de Massicot 1.x',

	// O
	'operation_non_autorisee' => 'Operación no autorizada.',

	// R
	'reinitialiser' => 'Restablecer',
	'rotation_apercu' => 'Vista previa del resultado final',
	'rotation_aucune' => 'Sin rotación',
	'rotation_demi_tour' => 'Girar 180°',
	'rotation_droite' => 'Girar 90° a la derecha',
	'rotation_explication' => 'El resultado utiliza image_rotation() de SPIP 4, comprobado y corregido por Massicot. La orientación EXIF se normaliza automáticamente.',
	'rotation_gauche' => 'Girar 90° a la izquierda',
	'rotation_titre' => 'Rotación de salida',

	// S
	'selection_recadrage' => 'Selección de recorte. Utilice las flechas para desplazarla.',
	'supprimer_recadrage' => 'Eliminar el recorte',

	// Z
	'zone_recadrage' => 'Zona interactiva de recorte',
	'zoom' => 'Zoom',
);
