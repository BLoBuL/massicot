<?php
/**
 * Fichier gérant l'installation et désinstallation du plugin Massicot
 *
 * @plugin	   Massicot
 * @copyright  2015
 * @author	   Michel @ Vertige ASBL
 * @licence	   GNU/GPL
 * @package	   SPIP\Massicot\Installation
 */

/**
 * Fonction d'installation et de mise à jour du plugin Massicot.
 *
 * @param string $nom_meta_base_version
 *	   Nom de la meta informant de la version du schéma de données du plugin installé dans SPIP
 * @param string $version_cible
 *	   Version du schéma de données dans ce plugin (déclaré dans paquet.xml)
 * @return void
**/
function massicot_upgrade($nom_meta_base_version, $version_cible) {
	$maj = array();

	$maj['create'] = array(
		array('maj_tables', array('spip_massicotages', 'spip_massicotages_liens')),
		array('massicot_initialiser_configuration', false),
	);

	$maj['1.1.0'] = array(array('maj_tables', array('spip_massicotages_liens')));
	$maj['2.0.0'] = array(array('massicot_initialiser_configuration', true));

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

/**
 * Initialise le mode de compatibilite.
 *
 * Une installation neuve n'altere pas les balises natives de SPIP. Lors de
 * la mise a jour d'un site 1.x, le comportement historique reste actif le
 * temps d'adapter explicitement ses squelettes.
 */
function massicot_initialiser_configuration($migration = false) {
	include_spip('inc/config');
	ecrire_config('massicot/mode_compatibilite', $migration ? 'oui' : 'non');
}


/**
 * Fonction de désinstallation du plugin Massicot.
 *
 * @param string $nom_meta_base_version
 *	   Nom de la meta informant de la version du schéma de données du plugin installé dans SPIP
 * @return void
**/
function massicot_vider_tables($nom_meta_base_version) {

	sql_drop_table('spip_massicotages');
	sql_drop_table('spip_massicotages_liens');

	include_spip('inc/config');
	effacer_config('massicot');

	effacer_meta($nom_meta_base_version);
}
