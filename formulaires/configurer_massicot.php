<?php

/**
 * Configuration de la transition depuis Massicot 1.x.
 */
function formulaires_configurer_massicot_charger_dist() {
	include_spip('inc/config');
	return array(
		'mode_compatibilite' => lire_config('massicot/mode_compatibilite', 'non'),
	);
}

function formulaires_configurer_massicot_traiter_dist() {
	include_spip('inc/config');
	$mode = _request('mode_compatibilite') === 'oui' ? 'oui' : 'non';
	ecrire_config('massicot/mode_compatibilite', $mode);

	include_spip('inc/invalideur');
	suivre_invalideur('1');
	return array('message_ok' => _T('config_info_enregistree'));
}
