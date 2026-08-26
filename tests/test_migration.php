<?php

$configuration = null;
$nombre_recadrages = 44;
function include_spip($fichier) {
}
function sql_countsel($table) {
	global $nombre_recadrages;
	return $table === 'spip_massicotages_liens' ? $nombre_recadrages : 0;
}
function ecrire_config($cle, $valeur) {
	global $configuration;
	$configuration = array($cle, $valeur);
}

require dirname(__DIR__) . '/massicot_administrations.php';
require dirname(__DIR__) . '/formulaires/configurer_massicot.php';

if (massicot_compter_recadrages() !== 44) {
	fwrite(STDERR, "ECHEC diagnostic des recadrages\n");
	exit(1);
}

massicot_initialiser_configuration(false);
if ($configuration !== array('massicot/mode_compatibilite', 'non')) {
	fwrite(STDERR, "ECHEC installation neuve\n");
	exit(1);
}

massicot_initialiser_configuration(true);
if ($configuration !== array('massicot/mode_compatibilite', 'oui')) {
	fwrite(STDERR, "ECHEC migration 1.x\n");
	exit(1);
}

echo "3 tests migration OK\n";
