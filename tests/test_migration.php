<?php

$configuration = null;
function include_spip($fichier) {
}
function ecrire_config($cle, $valeur) {
	global $configuration;
	$configuration = array($cle, $valeur);
}

require dirname(__DIR__) . '/massicot_administrations.php';

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

echo "2 tests migration OK\n";
