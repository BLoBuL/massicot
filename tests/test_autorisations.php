<?php

$extension_document = 'jpg';
$autorisation_modifier = true;
function include_spip($fichier) {}
function sql_getfetsel($select, $from, $where) {
	global $extension_document;
	return $extension_document;
}
function autoriser($faire, $type, $id, $qui = null, $opt = null) {
	global $autorisation_modifier;
	return $faire === 'modifier' ? $autorisation_modifier : false;
}

require dirname(__DIR__) . '/massicot_fonctions.php';
require dirname(__DIR__) . '/massicot_pipelines.php';

$qui = array('id_auteur' => 1);
$tests = array();
foreach (array('jpg', 'jpeg', 'png', 'gif', 'webp') as $extension_document) {
	$tests[$extension_document] = autoriser_massicoter_dist('massicoter', 'document', 1, $qui, array());
}
foreach (array('avif', 'svg', 'pdf', 'txt') as $extension_document) {
	$tests['refus ' . $extension_document] = !autoriser_massicoter_dist(
		'massicoter', 'document', 1, $qui, array()
	);
}
$extension_document = 'jpg';
$autorisation_modifier = false;
$tests['delegation modifier'] = !autoriser_massicoter_dist('massicoter', 'document', 1, $qui, array());
$tests['remplacement logo normal'] = massicot_roles_logo_modifies(
	array('logo_on' => array('error' => UPLOAD_ERR_OK)),
	false,
	false
) === array('');
$tests['remplacement logo survol'] = massicot_roles_logo_modifies(
	array('logo_off' => array('error' => UPLOAD_ERR_OK)),
	false,
	false
) === array('logo_survol');
$tests['upload en erreur conserve recadrage'] = massicot_roles_logo_modifies(
	array('logo_on' => array('error' => UPLOAD_ERR_PARTIAL)),
	false,
	false
) === array();
$tests['traitement en erreur conserve recadrage'] = massicot_roles_logo_modifies(
	array('logo_on' => array('error' => UPLOAD_ERR_OK)),
	false,
	false,
	false
) === array();

$echecs = array_keys(array_filter($tests, fn($ok) => !$ok));
if ($echecs) {
	fwrite(STDERR, 'ECHEC: ' . implode(', ', $echecs) . PHP_EOL);
	exit(1);
}

echo count($tests) . " tests autorisations OK\n";
