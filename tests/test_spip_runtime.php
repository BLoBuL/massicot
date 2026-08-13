<?php

$racine_spip = getenv('SPIP_ROOT');
if (!$racine_spip || !is_file($racine_spip . '/ecrire/inc_version.php')) {
	fwrite(STDERR, "SPIP_ROOT doit pointer vers une installation SPIP de test.\n");
	exit(2);
}

chdir($racine_spip);
define('_FILE_CONNECT', 'massicot-tests');
require 'ecrire/inc_version.php';
include_spip('inc/filtres');
include_spip('inc/filtres_images_mini');
include_spip('filtres/images_transforme');
require dirname(__DIR__) . '/massicot_fonctions.php';

$version = $GLOBALS['spip_version_affichee'] ?? 'inconnue';
$formats = array(
	'jpg' => 'imagejpeg',
	'png' => 'imagepng',
	'gif' => 'imagegif',
	'webp' => 'imagewebp',
	'avif' => 'imageavif',
);
$tests = array();
$testes = 0;

foreach ($formats as $extension => $encodeur) {
	if (!function_exists('imagecreatetruecolor') || !function_exists($encodeur)) {
		echo "SKIP {$extension}: encodeur indisponible\n";
		continue;
	}
	$testes++;
	$source = _DIR_TMP . 'massicot-runtime-' . $extension . '-' . uniqid() . '.' . $extension;
	$image = imagecreatetruecolor(80, 60);
	$couleur = imagecolorallocate($image, 230, 170, 20);
	imagefill($image, 0, 0, $couleur);
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
	@unlink($source);
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
