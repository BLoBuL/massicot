<?php

define('_ECRIRE_INC_VERSION', true);

function massicot_charger_langue_test($langue) {
	$GLOBALS['idx_lang'] = 'massicot_' . $langue;
	$GLOBALS[$GLOBALS['idx_lang']] = array();
	include dirname(__DIR__) . '/lang/massicot_' . $langue . '.php';
	return $GLOBALS[$GLOBALS['idx_lang']];
}

$francais = massicot_charger_langue_test('fr');
$espagnol = massicot_charger_langue_test('es');
$cles_manquantes = array_diff(array_keys($francais), array_keys($espagnol));
$cles_en_trop = array_diff(array_keys($espagnol), array_keys($francais));
$traductions_vides = array_keys(array_filter($espagnol, fn($texte) => trim((string) $texte) === ''));

if ($cles_manquantes || $cles_en_trop || $traductions_vides) {
	fwrite(STDERR, 'ECHEC langue espagnole : ' . json_encode(array(
		'manquantes' => array_values($cles_manquantes),
		'en_trop' => array_values($cles_en_trop),
		'vides' => array_values($traductions_vides),
	), JSON_UNESCAPED_UNICODE) . PHP_EOL);
	exit(1);
}

echo count($espagnol) . " traductions espagnoles OK\n";
