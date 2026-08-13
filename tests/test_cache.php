<?php

$requete_sql = 0;
function include_spip($fichier) {}
function objet_type($objet) { return rtrim((string) $objet, 's'); }
function sql_quote($valeur) { return "'" . addslashes($valeur) . "'"; }
function sql_getfetsel($select, $from, $where) {
	global $requete_sql;
	$requete_sql++;
	return '{"zoom":1,"x1":0,"x2":100,"y1":0,"y2":50}';
}

require dirname(__DIR__) . '/massicot_fonctions.php';

$premier = massicot_get_parametres('document', 42, 'illustration');
$second = massicot_get_parametres('document', 42, 'illustration');
if ($premier !== $second || $requete_sql !== 1) {
	fwrite(STDERR, "ECHEC cache de lecture\n");
	exit(1);
}

massicot_invalider_cache('document', 42, 'illustration');
massicot_get_parametres('document', 42, 'illustration');
if ($requete_sql !== 2) {
	fwrite(STDERR, "ECHEC invalidation ciblee\n");
	exit(1);
}

$GLOBALS['massicot_parametres']['document:42:autre'] = array('temoin' => true);
massicot_invalider_cache('document', 42);
if (isset($GLOBALS['massicot_parametres']['document:42:autre'])) {
	fwrite(STDERR, "ECHEC invalidation objet\n");
	exit(1);
}

echo "3 tests cache OK\n";
