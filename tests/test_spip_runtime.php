<?php

$racine_spip = getenv('SPIP_ROOT');
if (!$racine_spip || !is_file($racine_spip . '/ecrire/inc_version.php')) {
	fwrite(STDERR, "SPIP_ROOT doit pointer vers une installation SPIP de test.\n");
	exit(2);
}

chdir($racine_spip);
define('_FILE_CONNECT', 'massicot-tests');
if (is_file('vendor/autoload.php')) {
	require 'vendor/autoload.php';
}
require 'ecrire/inc_version.php';
include_spip('inc/filtres');
include_spip('inc/filtres_images_mini');
include_spip('filtres/images_transforme');
require dirname(__DIR__) . '/massicot_fonctions.php';

$GLOBALS['meta']['image_process'] = 'gd2';
$GLOBALS['meta']['gd_formats'] = 'jpg,png,gif,webp';

$version = $GLOBALS['spip_version_affichee'] ?? 'inconnue';
$formats = array(
	'jpg' => 'imagejpeg',
	'png' => 'imagepng',
	'gif' => 'imagegif',
	'webp' => 'imagewebp',
);
$tests = array();
$testes = 0;

function massicot_test_couleur($fichier, $x, $y) {
	$infos = @getimagesize($fichier);
	$chargeurs = array(IMAGETYPE_JPEG => 'imagecreatefromjpeg', IMAGETYPE_PNG => 'imagecreatefrompng', IMAGETYPE_GIF => 'imagecreatefromgif');
	if (defined('IMAGETYPE_WEBP')) {
		$chargeurs[IMAGETYPE_WEBP] = 'imagecreatefromwebp';
	}
	$chargeur = $infos ? ($chargeurs[$infos[2]] ?? '') : '';
	$image = $chargeur && function_exists($chargeur) ? @$chargeur($fichier) : false;
	if (!$image) { return array(); }
	$couleur = imagecolorsforindex($image, imagecolorat($image, $x, $y));
	imagedestroy($image);
	return array($couleur['red'], $couleur['green'], $couleur['blue']);
}

function massicot_test_est_couleur($couleur, $attendue) {
	return count($couleur) === 3
		&& abs($couleur[0] - $attendue[0]) < 45
		&& abs($couleur[1] - $attendue[1]) < 45
		&& abs($couleur[2] - $attendue[2]) < 45;
}

function massicot_test_ajouter_orientation_exif($fichier, $orientation) {
	$jpeg = file_get_contents($fichier);
	$tiff = "II\x2A\x00" . pack('V', 8) . pack('v', 1)
		. pack('vvVv', 0x0112, 3, 1, $orientation) . pack('v', 0) . pack('V', 0);
	$app1 = "Exif\x00\x00" . $tiff;
	file_put_contents($fichier, substr($jpeg, 0, 2) . "\xFF\xE1" . pack('n', strlen($app1) + 2) . $app1 . substr($jpeg, 2));
}

foreach ($formats as $extension => $encodeur) {
	if (!function_exists('imagecreatetruecolor') || !function_exists($encodeur)) {
		echo "SKIP {$extension}: encodeur indisponible\n";
		continue;
	}
	$testes++;
	$source = _DIR_TMP . 'massicot-runtime-' . $extension . '-' . uniqid() . '.' . $extension;
	$image = imagecreatetruecolor(80, 60);
	$rouge = imagecolorallocate($image, 220, 20, 20);
	$vert = imagecolorallocate($image, 20, 180, 20);
	$bleu = imagecolorallocate($image, 20, 40, 220);
	$jaune = imagecolorallocate($image, 230, 200, 20);
	imagefilledrectangle($image, 0, 0, 39, 29, $rouge);
	imagefilledrectangle($image, 40, 0, 79, 29, $vert);
	imagefilledrectangle($image, 0, 30, 39, 59, $bleu);
	imagefilledrectangle($image, 40, 30, 79, 59, $jaune);
	$encodeur($image, $source);
	imagedestroy($image);

	$derive = massicoter_fichier($source, array(
		'zoom' => 1,
		'x1' => 10,
		'x2' => 70,
		'y1' => 5,
		'y2' => 55,
	));
	$dimensions = @getimagesize($derive);
	$tests[$extension] = $dimensions && $dimensions[0] === 60 && $dimensions[1] === 50;
	foreach (massicot_filtres_disponibles() as $filtre) {
		$filtre_derive = massicot_appliquer_filtre_spip($derive, $filtre);
		$filtre_dimensions = @getimagesize($filtre_derive);
		$tests[$extension . '-' . $filtre] = $filtre_dimensions
			&& $filtre_dimensions[0] === 60
			&& $filtre_dimensions[1] === 50;
	}
	foreach (array(90 => array(50, 60), 180 => array(60, 50), 270 => array(50, 60)) as $rotation => $attendu) {
		$rotation_derive = massicot_appliquer_rotation($derive, $rotation);
		$rotation_dimensions = @getimagesize($rotation_derive);
		$tests[$extension . '-rotation-spip-' . $rotation] = $rotation_dimensions
			&& $rotation_dimensions[0] === $attendu[0]
			&& $rotation_dimensions[1] === $attendu[1]
			&& ($rotation !== 90 || massicot_test_est_couleur(massicot_test_couleur($rotation_derive, 4, 4), array(20, 40, 220)));
		$rotation_repli = massicot_appliquer_rotation($derive, $rotation, true);
		$repli_dimensions = @getimagesize($rotation_repli);
		$tests[$extension . '-rotation-repli-' . $rotation] = $repli_dimensions
			&& $repli_dimensions[0] === $attendu[0]
			&& $repli_dimensions[1] === $attendu[1]
			&& ($rotation !== 90 || massicot_test_est_couleur(massicot_test_couleur($rotation_repli, 4, 4), array(20, 40, 220)));
	}
	$chaine = massicoter_fichier($source, array(
		'zoom' => 1, 'x1' => 10, 'x2' => 70, 'y1' => 5, 'y2' => 55,
		'filtre' => 'sepia', 'rotation' => 90,
	));
	$chaine_dimensions = @getimagesize($chaine);
	$tests[$extension . '-recadrage-filtre-rotation'] = $chaine_dimensions
		&& $chaine_dimensions[0] === 50
		&& $chaine_dimensions[1] === 60;
	@unlink($source);
}

if (function_exists('imagejpeg') && function_exists('exif_read_data')) {
	$source_exif = _DIR_TMP . 'massicot-runtime-exif-' . uniqid() . '.jpg';
	$image_exif = imagecreatetruecolor(80, 60);
	$rouge = imagecolorallocate($image_exif, 220, 20, 20);
	$vert = imagecolorallocate($image_exif, 20, 180, 20);
	$bleu = imagecolorallocate($image_exif, 20, 40, 220);
	$jaune = imagecolorallocate($image_exif, 230, 200, 20);
	imagefilledrectangle($image_exif, 0, 0, 39, 29, $rouge);
	imagefilledrectangle($image_exif, 40, 0, 79, 29, $vert);
	imagefilledrectangle($image_exif, 0, 30, 39, 59, $bleu);
	imagefilledrectangle($image_exif, 40, 30, 79, 59, $jaune);
	imagejpeg($image_exif, $source_exif, 95);
	imagedestroy($image_exif);
	massicot_test_ajouter_orientation_exif($source_exif, 6);
	$tests['exif-orientation-lue'] = massicot_orientation_exif($source_exif) === 6;
	$source_orientee = massicot_orienter_selon_exif($source_exif);
	$dimensions_orientees = @getimagesize($source_orientee);
	$tests['exif-orientation-materialisee'] = $dimensions_orientees
		&& $dimensions_orientees[0] === 60
		&& $dimensions_orientees[1] === 80
		&& massicot_test_est_couleur(massicot_test_couleur($source_orientee, 4, 4), array(20, 40, 220));
	$source_exif_url = str_replace('\\', '/', substr($source_exif, strlen(_DIR_RACINE)));
	$html_oriente = massicot_normaliser_images_html_spip4(
		'<img src="' . $source_exif_url . '" srcset="' . $source_exif_url . ' 1x, image-distante.jpg 2x" alt="Portrait">'
	);
	$tests['pipeline-html-exif'] = !str_contains($html_oriente, 'src="' . $source_exif_url . '"')
		&& str_contains($html_oriente, 'srcset=')
		&& str_contains($html_oriente, 'alt="Portrait"');
	@unlink($source_exif);
}

if (!$testes) {
	fwrite(STDERR, "ECHEC: aucun moteur raster disponible\n");
	exit(1);
}
$echecs = array_keys(array_filter($tests, fn($ok) => !$ok));
if ($echecs) {
	fwrite(STDERR, 'ECHEC SPIP ' . $version . ': ' . implode(', ', $echecs) . PHP_EOL);
	exit(1);
}

echo 'SPIP ' . $version . ' / PHP ' . PHP_VERSION . ': ' . count($tests) . " formats recadres OK\n";
