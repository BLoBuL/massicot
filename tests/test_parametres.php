<?php

function include_spip($fichier) {
}

require dirname(__DIR__) . '/massicot_fonctions.php';

$tests = array();
$tests['normalisation'] = massicot_normaliser_parametres(array(
	'zoom' => '1.5', 'x1' => '10', 'x2' => '110', 'y1' => '20', 'y2' => '70',
)) === array('zoom' => 1.5, 'x1' => 10, 'x2' => 110, 'y1' => 20, 'y2' => 70, 'filtre' => 'aucun');
$tests['ordre invalide'] = massicot_normaliser_parametres(array(
	'zoom' => 1, 'x1' => 10, 'x2' => 9, 'y1' => 0, 'y2' => 10,
)) === array();
$tests['zoom excessif'] = massicot_normaliser_parametres(array(
	'zoom' => 11, 'x1' => 0, 'x2' => 10, 'y1' => 0, 'y2' => 10,
)) === array();
$tests['hors canevas'] = massicot_normaliser_parametres(array(
	'zoom' => 1, 'x1' => 0, 'x2' => 101, 'y1' => 0, 'y2' => 50,
), 100, 50) === array();
$tests['json 2.x'] = massicot_decoder_parametres('{"zoom":1,"x1":0,"x2":100,"y1":0,"y2":50}')
	=== array('zoom' => 1.0, 'x1' => 0, 'x2' => 100, 'y1' => 0, 'y2' => 50, 'filtre' => 'aucun');
$tests['serialize 1.x'] = massicot_decoder_parametres(serialize(array(
	'zoom' => '1', 'x1' => '0', 'x2' => '100', 'y1' => '0', 'y2' => '50',
))) === array('zoom' => 1.0, 'x1' => 0, 'x2' => 100, 'y1' => 0, 'y2' => 50, 'filtre' => 'aucun');
$tests['filtre valide'] = massicot_normaliser_parametres(array(
	'zoom' => 1, 'x1' => 0, 'x2' => 10, 'y1' => 0, 'y2' => 10, 'filtre' => 'sepia',
))['filtre'] === 'sepia';
$tests['filtre inconnu neutralise'] = massicot_normaliser_parametres(array(
	'zoom' => 1, 'x1' => 0, 'x2' => 10, 'y1' => 0, 'y2' => 10, 'filtre' => 'php_eval',
))['filtre'] === 'aucun';
$tests['formats raster'] = array_reduce(
	array('jpg', 'jpeg', 'png', 'gif', 'webp'),
	fn($ok, $extension) => $ok && massicot_extension_recadrable($extension),
	true
);
$tests['formats vectoriels ou non images refuses'] = !massicot_extension_recadrable('svg')
	&& !massicot_extension_recadrable('pdf')
	&& !massicot_extension_recadrable('avif');
$tests['fraction EXIF'] = massicot_exif_fraction('28/10') === 2.8;

$echecs = array_keys(array_filter($tests, fn($ok) => !$ok));
if ($echecs) {
	fwrite(STDERR, 'ECHEC: ' . implode(', ', $echecs) . PHP_EOL);
	exit(1);
}

echo count($tests) . " tests OK\n";
