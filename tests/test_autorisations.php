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
foreach (array('jpg', 'jpeg', 'png', 'gif', 'webp', 'avif') as $extension_document) {
	$tests[$extension_document] = autoriser_massicoter_dist('massicoter', 'document', 1, $qui, array());
}
foreach (array('svg', 'pdf', 'txt') as $extension_document) {
	$tests['refus ' . $extension_document] = !autoriser_massicoter_dist(
		'massicoter', 'document', 1, $qui, array()
	);
}
$extension_document = 'jpg';
$autorisation_modifier = false;
$tests['delegation modifier'] = !autoriser_massicoter_dist('massicoter', 'document', 1, $qui, array());

$echecs = array_keys(array_filter($tests, fn($ok) => !$ok));
if ($echecs) {
	fwrite(STDERR, 'ECHEC: ' . implode(', ', $echecs) . PHP_EOL);
	exit(1);
}

echo count($tests) . " tests autorisations OK\n";
