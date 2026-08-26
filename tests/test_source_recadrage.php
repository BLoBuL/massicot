<?php

$dossier_test = 'tests/.tmp-massicot-source-' . getmypid();
mkdir($dossier_test);
$source = $dossier_test . '/source.png';
$derive_affiche = $dossier_test . '/derive-affiche.png';
$derive_massicot = $dossier_test . '/derive-massicot.png';
$portrait_png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAACgAAAA8CAYAAAAUufjgAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAEGSURBVGhD7c6xDcIwEIXhTOINWIO5WIFdWIiGmoYaZKRI6Jfj89lHlESv+JoovvdPr+fjvWUTP2yNAkcpcNRQ4PmUmvFtq65AjnvwlsUVyLERvL2kOZADEbhR0hTIw5G4RfsP5MF/4KYCvbjpCpzdb9dw3ChRYA03SpoCeTgSt6g7MKX0xe9k/cct6g6Mwi1SoIVbZAbyYDTukRmY8WgkbpECLdwiBVq4RccIzHg4AjdKjhOYcWAEby9xBWYc6sGbNccLzDjowVuWrsAZx2v4ttUqgXznoUAF1jBkCd957Dtwmi6r4K4CvbirQC/uKtCLuwr04q4CvbirQC/uKtCLuwr04u6vD2Nqmoz8sJ1DAAAAAElFTkSuQmCC');
$derive_png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl2nkwAAAAASUVORK5CYII=');
file_put_contents($source, $portrait_png);
file_put_contents($derive_affiche, $derive_png);
file_put_contents($derive_massicot, $derive_png);

$GLOBALS['massicot_test_source'] = $source;
$GLOBALS['massicot_test_derive'] = $derive_massicot;
$GLOBALS['massicot_test_appels'] = array();

define('_DIR_IMG', $dossier_test . '/');
define('_NOM_PERMANENTS_ACCESSIBLES', '');

function include_spip($fichier) {
}

function objet_type($objet) {
	return $objet;
}

function id_table_objet($objet) {
	return $objet;
}

function charger_fonction($nom, $repertoire) {
	return function ($id_objet, $objet, $type_logo) {
		return array($GLOBALS['massicot_test_source']);
	};
}

function sql_getfetsel($champ, $table, $where = null) {
	if ($champ === 'traitements') {
		return serialize(array(
			'zoom' => '2',
			'x1' => '0',
			'x2' => '1',
			'y1' => '0',
			'y2' => '1',
		));
	}
	return null;
}

function sql_quote($valeur) {
	return "'" . addslashes((string) $valeur) . "'";
}

function extraire_attribut($html, $nom) {
	if (preg_match('/\\s' . preg_quote($nom, '/') . '=("|\\\')([^"\\\']*)\\1/i', $html, $match)) {
		return html_entity_decode($match[2], ENT_QUOTES, 'UTF-8');
	}
	return '';
}

function inserer_attribut($html, $nom, $valeur) {
	$valeur = htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
	$motif = '/\\s' . preg_quote($nom, '/') . '=("|\\\')[^"\\\']*\\1/i';
	if (preg_match($motif, $html)) {
		return preg_replace($motif, ' ' . $nom . '="' . $valeur . '"', $html, 1);
	}
	return preg_replace('/>$/', ' ' . $nom . '="' . $valeur . '">', $html, 1);
}

function image_reduire($fichier, $largeur, $hauteur) {
	$GLOBALS['massicot_test_appels'][] = array('filtre' => 'reduire', 'fichier' => $fichier);
	return '<img src="' . htmlspecialchars($GLOBALS['massicot_test_derive'], ENT_QUOTES, 'UTF-8') . '">';
}

function image_passe_partout($fichier, $largeur, $hauteur, $forcer) {
	$GLOBALS['massicot_test_appels'][] = array('filtre' => 'passe_partout', 'fichier' => $fichier);
	return '<img src="' . htmlspecialchars($GLOBALS['massicot_test_derive'], ENT_QUOTES, 'UTF-8') . '">';
}

function image_recadre($fichier, $largeur, $hauteur, $position) {
	$GLOBALS['massicot_test_appels'][] = array('filtre' => 'recadre', 'fichier' => $fichier, 'position' => $position);
	return '<img src="' . htmlspecialchars($GLOBALS['massicot_test_derive'], ENT_QUOTES, 'UTF-8') . '">';
}

function charger_filtre($nom) {
	return function ($fichier, $alt = '', $classe = '') {
		return '<img src="' . htmlspecialchars($fichier, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . '" class="' . htmlspecialchars($classe, ENT_QUOTES, 'UTF-8') . '">';
	};
}

require dirname(__DIR__) . '/massicot_fonctions.php';

$html = '<picture><source srcset="source.webp 2x"><img src="' . $derive_affiche . '" alt="Portrait synthetique" loading="lazy" data-test="oui" class="logo"></picture>';
$resultat_html = massicoter_objet($html, 'article', 42);
$premier_appel_html = $GLOBALS['massicot_test_appels'][0]['fichier'] ?? '';

$GLOBALS['massicot_test_appels'] = array();
$logo = '<img src="' . $derive_affiche . '" alt="Portrait synthetique" class="logo">';
$resultat_logo = massicoter_logo($logo, 'article', 42);
$premier_appel_logo = $GLOBALS['massicot_test_appels'][0]['fichier'] ?? '';
$dimensions_source = getimagesize($source);

$tests = array(
	'fixture raster synthetique lisible' => $dimensions_source
		&& $dimensions_source[0] === 40
		&& $dimensions_source[1] === 60
		&& $dimensions_source[2] === IMAGETYPE_PNG,
	'objet repart de la source' => $premier_appel_html === $source,
	'logo repart de la source' => $premier_appel_logo === $source,
	'derive applique au html' => strpos($resultat_html, 'src="' . $derive_massicot . '"') !== false,
	'attributs html preserves' => strpos($resultat_html, 'alt="Portrait synthetique"') !== false
		&& strpos($resultat_html, 'loading="lazy"') !== false
		&& strpos($resultat_html, 'data-test="oui"') !== false
		&& strpos($resultat_html, 'class="logo"') !== false,
	'source picture preservee' => strpos($resultat_html, 'srcset="source.webp 2x"') !== false,
	'dimensions derivees actualisees' => strpos($resultat_html, 'width="1"') !== false
		&& strpos($resultat_html, 'height="1"') !== false,
	'logo massicote' => strpos($resultat_logo, $derive_massicot) !== false,
);

$echecs = array_keys(array_filter($tests, function ($ok) {
	return !$ok;
}));

@unlink($source);
@unlink($derive_affiche);
@unlink($derive_massicot);
@rmdir($dossier_test);

if ($echecs) {
	fwrite(STDERR, 'ECHEC: ' . implode(', ', $echecs) . PHP_EOL);
	exit(1);
}

echo count($tests) . " tests source/HTML synthetiques OK\n";
