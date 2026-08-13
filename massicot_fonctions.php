<?php
/**
 * Fonctions utiles au plugin Massicot
 *
 * @plugin	   Massicot
 * @copyright  2015
 * @author	   Michel @ Vertige ASBL
 * @licence	   GNU/GPL
 * @package	   SPIP\Massicot\Fonctions
 */

/**
 * Retrouver le chemin d'une image donnée par un couple objet, id_objet
 *
 * Si le type d'objet est un document, on retourne le chemin du
 * fichier, sinon on cherche un éventuel logo pour l'objet
 *
 * @param string $objet : Le type d'objet
 * @param integer $id_objet : L'identifiant de l'objet
 *
 * @return string : le chemin vers l'image, un string vide sinon
 */
function massicot_chemin_image($objet, $id_objet, $role = null) {

	include_spip('base/abstract_sql');
	include_spip('base/objets');

	if (objet_type($objet) === 'document') {
		$fichier = sql_getfetsel(
			'fichier',
			'spip_documents',
			'id_document='.intval($id_objet)
		);
		if (!$fichier) {
			return '';
		}
		if (preg_match('#^https?://#i', $fichier)) {
			return massicot_localiser_image($fichier);
		}
		include_spip('inc/documents');
		$chemin = get_spip_doc($fichier);
		if (!file_exists($chemin)) {
			$chemin = find_in_path(_NOM_PERMANENTS_ACCESSIBLES . $fichier);
		}
		return massicot_localiser_image($chemin);
	} else {
		if ($role === 'logo_survol') {
			$type_logo = 'off';
		} elseif ((! $role) or ($role === 'logo')) {
			$type_logo = 'on';
		} else {
			$type_logo = $role;
		}

		$chercher_logo = charger_fonction('chercher_logo', 'inc');
		$logo = $chercher_logo($id_objet, id_table_objet($objet), $type_logo);
		if (is_array($logo)) {
			return massicot_localiser_image(array_shift($logo));
		}
	}
}

/**
 * Copie localement une image distante avec l'API native de SPIP.
 *
 * Les hébergeurs d'images refusent fréquemment l'affichage à chaud. Le
 * traitement et l'aperçu doivent donc travailler sur la copie locale gérée
 * par SPIP, sans modifier la source éditoriale du document ou du logo.
 */
function massicot_localiser_image($fichier) {
	if (!$fichier || !preg_match('#^https?://#i', $fichier)) {
		return $fichier;
	}

	include_spip('inc/distant');
	$copie = copie_locale($fichier);
	return $copie ? _DIR_RACINE . $copie : '';
}

/**
 * Formats raster que les filtres images de SPIP 4 peuvent recadrer.
 * La disponibilité effective du moteur est ensuite vérifiée par getimagesize.
 */
function massicot_extension_recadrable($extension) {
	return in_array(strtolower((string) $extension), array(
		'jpg', 'jpeg', 'png', 'gif', 'webp'
	), true);
}

/**
 * Filtres visuels stables fournis par le plugin Filtres Images de SPIP 4.
 */
function massicot_filtres_disponibles() {
	return array('aucun', 'nb', 'sepia', 'lumineux', 'sombre', 'net', 'flou');
}

/**
 * Vérifie qu'une source est une image raster réellement lisible.
 */
function massicot_fichier_recadrable($fichier) {
	if (!$fichier) {
		return false;
	}
	$path = parse_url($fichier, PHP_URL_PATH) ?: $fichier;
	if (!massicot_extension_recadrable(pathinfo($path, PATHINFO_EXTENSION))) {
		return false;
	}
	return (bool) @getimagesize($fichier);
}

/**
 * Indique si les traitements automatiques de Massicot 1.x sont actifs.
 */
function massicot_mode_compatibilite() {
	include_spip('inc/config');
	return lire_config('massicot/mode_compatibilite', 'non') === 'oui';
}

/**
 * Normalise et valide les parametres persistants ou recus du formulaire.
 *
 * @return array Tableau normalise, vide si les donnees sont invalides.
 */
function massicot_normaliser_parametres($parametres, $largeur = null, $hauteur = null) {
	if (!is_array($parametres)) {
		return array();
	}

	foreach (array('zoom', 'x1', 'x2', 'y1', 'y2') as $cle) {
		if (!isset($parametres[$cle]) || !is_numeric($parametres[$cle])) {
			return array();
		}
	}

	$normalises = array(
		'zoom' => (float) $parametres['zoom'],
		'x1' => (int) round((float) $parametres['x1']),
		'x2' => (int) round((float) $parametres['x2']),
		'y1' => (int) round((float) $parametres['y1']),
		'y2' => (int) round((float) $parametres['y2']),
	);
	$filtre = isset($parametres['filtre']) ? strtolower(trim((string) $parametres['filtre'])) : 'aucun';
	$normalises['filtre'] = in_array($filtre, massicot_filtres_disponibles(), true) ? $filtre : 'aucun';

	if ($normalises['zoom'] < 0.01 || $normalises['zoom'] > 10
		|| $normalises['x1'] < 0 || $normalises['y1'] < 0
		|| $normalises['x2'] <= $normalises['x1']
		|| $normalises['y2'] <= $normalises['y1']) {
		return array();
	}

	if ($largeur && $hauteur) {
		$largeur_canevas = (int) ceil($largeur * $normalises['zoom']);
		$hauteur_canevas = (int) ceil($hauteur * $normalises['zoom']);
		if ($normalises['x2'] > $largeur_canevas || $normalises['y2'] > $hauteur_canevas) {
			return array();
		}
	}

	return $normalises;
}

/**
 * Lit le format JSON 2.x et, en repli, les donnees serialisees de Massicot 1.x.
 */
function massicot_decoder_parametres($traitements) {
	if (!is_string($traitements) || $traitements === '') {
		return array();
	}

	$parametres = json_decode($traitements, true);
	if (!is_array($parametres)) {
		$parametres = @unserialize($traitements, array('allowed_classes' => false));
	}

	return massicot_normaliser_parametres($parametres);
}

/**
 * Clé stable du cache mémoire de la requête HTTP courante.
 */
function massicot_cle_cache($objet, $id_objet, $role = '') {
	return objet_type($objet) . ':' . (int) $id_objet . ':' . (string) $role;
}

/**
 * Invalide les lectures mémorisées d'un objet après une écriture.
 */
function massicot_invalider_cache($objet, $id_objet, $role = null) {
	$GLOBALS['massicot_documents_par_fichier'] = array();
	foreach (array('massicot_parametres', 'massicot_identifiants') as $nom) {
		if (!isset($GLOBALS[$nom]) || !is_array($GLOBALS[$nom])) {
			$GLOBALS[$nom] = array();
		}
		if ($role !== null) {
			unset($GLOBALS[$nom][massicot_cle_cache($objet, $id_objet, $role)]);
			continue;
		}
		$prefixe = objet_type($objet) . ':' . (int) $id_objet . ':';
		foreach (array_keys($GLOBALS[$nom]) as $cle) {
			if (str_starts_with($cle, $prefixe)) {
				unset($GLOBALS[$nom][$cle]);
			}
		}
	}
}

/**
 * Enregistre un massicotage dans la base de données
 *
 * @param string $objet : le type d'objet
 * @param integer $id_objet : l'identifiant de l'objet
 * @param array parametres : Un tableau de parametres pour le
 *							 massicotage, doit contenir les clés
 *							 'zoom', 'x1', 'x2', 'y1', et 'y2'
 *
 * @return mixed   Rien si tout s'est bien passé, un message d'erreur
 *				   sinon
 */
function massicot_enregistrer($objet, $id_objet, $parametres) {

	include_spip('inc/autoriser');
	if (!autoriser('massicoter', $objet, $id_objet)) {
		return _T('massicot:operation_non_autorisee');
	}

	include_spip('action/editer_objet');
	include_spip('action/editer_liens');

	/* Tester l'existence des parametres nécessaires */
	if (! isset($parametres['zoom'])) {
		return _T('massicot:erreur_parametre_manquant', array('parametre' => 'zoom'));
	} elseif (! isset($parametres['x1'])) {
		return _T('massicot:erreur_parametre_manquant', array('parametre' => 'x1'));
	} elseif (! isset($parametres['x2'])) {
		return _T('massicot:erreur_parametre_manquant', array('parametre' => 'x2'));
	} elseif (! isset($parametres['y1'])) {
		return _T('massicot:erreur_parametre_manquant', array('parametre' => 'y1'));
	} elseif (! isset($parametres['y2'])) {
		return _T('massicot:erreur_parametre_manquant', array('parametre' => 'y2'));
	}

	/* le rôle est traité à part */
	if (isset($parametres['role'])) {
		$role = $parametres['role'];
		unset($parametres['role']);
	} else {
		$role = '';
	}

	$chemin_image = massicot_chemin_image($objet, $id_objet, $role);
	$dimensions = $chemin_image ? @getimagesize($chemin_image) : false;
	if (!$dimensions) {
		return _T('massicot:erreur_fichier_image');
	}
	list($width, $height) = $dimensions;
	$parametres = massicot_normaliser_parametres($parametres, $width, $height);
	if (!$parametres) {
		return _T('massicot:erreur_parametres_invalides');
	}

	$id_massicotage = sql_getfetsel(
		'id_massicotage',
		'spip_massicotages_liens',
		array(
			'objet='.sql_quote($objet),
			'id_objet='.intval($id_objet),
			'role='.sql_quote($role),
		)
	);

	if (! $id_massicotage) {
		$id_massicotage = objet_inserer('massicotage');
		objet_associer(
			array('massicotage' => $id_massicotage),
			array($objet => $id_objet),
			array('role' => $role)
		);

		/* Le logo du site est un cas spécial. SPIP le traite comme le « site »
		 * avec l'id 0, alors on fait pareil. */
		if ($id_objet == 0) { // peut être le string '0'
			sql_insertq(
				'spip_massicotages_liens',
				array(
					'id_massicotage' => $id_massicotage,
					'id_objet' => 0,
					'objet' => 'site',
					'role' => $role,
				)
			);
		}
	}

	if ($err = objet_modifier(
		'massicotage',
		$id_massicotage,
		array('traitements' => json_encode($parametres, JSON_THROW_ON_ERROR))
	)) {
		return $err;
	}
	massicot_invalider_cache($objet, $id_objet, $role);
	$GLOBALS['massicot_parametres'][massicot_cle_cache($objet, $id_objet, $role)] = $parametres;
	$GLOBALS['massicot_identifiants'][massicot_cle_cache($objet, $id_objet, $role)] = (int) $id_massicotage;
}

/**
 * Supprimer le massicotage
 *
 * @param string $objet : le type d'objet
 * @param integer $id_objet : l'identifiant de l'objet
 * @param string $role : le rôle
 *
 * @return null|string : Rien, ou un message d'erreur
 */
function massicot_supprimer($objet, $id_objet, $role='') {

	include_spip('inc/autoriser');
	if (!autoriser('massicoter', $objet, $id_objet)) {
		return _T('massicot:operation_non_autorisee');
	}

	include_spip('base/abstract_sql');

	$id_massicotage = massicot_get_id($objet, $id_objet, $role);

	if (!$id_massicotage) {
		return null;
	}

	if (sql_delete(
		'spip_massicotages_liens',
		'id_massicotage=' . intval($id_massicotage)
	) === false) {
		return "massicot_supprimer : erreur lors de la suppression";
	}
	massicot_invalider_cache($objet, $id_objet, $role);

	if (sql_delete(
		'spip_massicotages',
		'id_massicotage=' . intval($id_massicotage)
	) === false) {
		return "massicot_supprimer : erreur lors de la suppression";
	}

}

/**
 * Supprime tous les recadrages associes a un objet remplace.
 */
function massicot_supprimer_tous($objet, $id_objet) {
	include_spip('action/editer_liens');
	$liens = objet_trouver_liens(
		array('massicotage' => '*'),
		array($objet => (int) $id_objet)
	);
	$ids = array_map('intval', array_column($liens, 'id_massicotage'));
	if (!$ids) {
		return;
	}

	$where = sql_in('id_massicotage', $ids);
	sql_delete('spip_massicotages_liens', $where);
	sql_delete('spip_massicotages', $where);
	massicot_invalider_cache($objet, $id_objet);
}

/**
 * Retourne l'identifiant du massicotage d'une image
 *
 * S'il n'y a pas de massicotage défini pour cet objet, on ne retourne rien.
 *
 * @param string $objet : le type d'objet
 * @param integer $id_objet : l'identifiant de l'objet
 * @param string $role : le rôle
 *
 * @return integer|null : L'identifiant du massicotage, rien sinon
 */
function massicot_get_id($objet, $id_objet, $role) {
	$cle_cache = massicot_cle_cache($objet, $id_objet, $role);
	if (array_key_exists($cle_cache, $GLOBALS['massicot_identifiants'] ?? array())) {
		return $GLOBALS['massicot_identifiants'][$cle_cache] ?: null;
	}

	include_spip('action/editer_liens');

	$massicotages = objet_trouver_liens(
		array('massicotage' => '*'),
		array($objet => $id_objet)
	);

	foreach ($massicotages as $massicotage) {
		if ($massicotage['role'] === $role) {
			return $GLOBALS['massicot_identifiants'][$cle_cache] = intval($massicotage['id_massicotage']);
		}
	}
	$GLOBALS['massicot_identifiants'][$cle_cache] = 0;
	return null;
}

/**
 * Retourne les paramètres de massicotage d'une image
 *
 * S'il n'y a pas de massicotage défini pour cet objet, on retourne
 * un tableau vide.
 *
 * @param string $objet : le type d'objet
 * @param integer $id_objet : l'identifiant de l'objet
 * @param string $role : le rôle
 *
 * @return array : Un tableau avec les paramètres de massicotage
 */
function massicot_get_parametres($objet, $id_objet, $role = '') {
	$cle_cache = massicot_cle_cache($objet, $id_objet, $role);
	if (array_key_exists($cle_cache, $GLOBALS['massicot_parametres'] ?? array())) {
		return $GLOBALS['massicot_parametres'][$cle_cache];
	}

	include_spip('base/abstract_sql');

	$traitements = sql_getfetsel(
		'traitements',
		'spip_massicotages as M '
		. 'INNER JOIN spip_massicotages_liens as L ON M.id_massicotage=L.id_massicotage',
		array(
			'L.objet='.sql_quote($objet),
			'L.id_objet='.intval($id_objet),
			'L.role='.sql_quote($role)
		)
	);

	$GLOBALS['massicot_parametres'][$cle_cache] = $traitements
		? massicot_decoder_parametres($traitements)
		: array();
	return $GLOBALS['massicot_parametres'][$cle_cache];
}

/**
 * Trouver l'objet associé à un logo donné par son fichier
 *
 * Retourne un tableau avec des clés 'objet' et 'id_objet'
 *
 * @param String $fichier : Le fichier de logo
 *
 * @return mixed : Un tableau représentant l'objet, rien si on n'a pas
 *				   réussi à deviner
 */
function massicot_trouver_objet_logo($fichier) {

	$fichier = basename($fichier);

	/* on retire l'extension */
	$fichier = substr($fichier, 0, strpos($fichier, '.'));

	$row = explode('on', $fichier);

	if (is_array($row) and (count($row) === 2)) {
		return array(
			'objet' => sinon(objet_type(
				array_search($row[0], $GLOBALS['table_logos'])
			),$row[0]),
			'id_objet' => $row[1],
		);
	}
}

/**
 * Massicoter un fichier image
 *
 * La fonction générale qui d'occupe du recadrage des images
 *
 * @param string $fichier : Le fichier
 * @param array $parametres : le tableau des paramètres de massicotage
 *
 * @return string : Un fichier massicoté
 */
function massicoter_fichier($fichier, $parametres) {

	include_spip('inc/filtres');
	include_spip('inc/filtres_images_mini');
	include_spip('filtres/images_transforme');

	$fichier_original = $fichier;

	/* on vire un éventuel query string */
	$fichier = parse_url($fichier);
	$fichier = $fichier['path'];

	/* la balise #FICHIER sur les boucles documents donne un chemin
	   relatif au dossier IMG qu'on ne peut pas retourner tel quel,
	   sous peine de de casser le portfolio de la dist.
	   (constaté sur SPIP 3.1 RC1) */
	if (substr($fichier, 0, 1) === '/') {
		return $fichier_original;
	}
	if (! file_exists($fichier)) {
		$fichier = _DIR_IMG . $fichier;
		// Si on n'a toujours rien, c'est probablement un fichier distant
		if (! file_exists($fichier)) {
			return $fichier_original;
		}
	}

	$parametres = massicot_normaliser_parametres($parametres);

	/* ne rien faire s'il n'y a pas de massicotage défini ou valide */
	if (!$parametres) {
		return $fichier;
	}

	$dimensions = @getimagesize($fichier);
	if (!$dimensions) {
		return $fichier_original;
	}
	list($width, $height) = $dimensions;
	if ($parametres['zoom'] === 1.0
		&& $parametres['x1'] === 0
		&& $parametres['x2'] === $width
		&& $parametres['y1'] === 0
		&& $parametres['y2'] === $height
		&& ($parametres['filtre'] ?? 'aucun') === 'aucun'
		) {
		// Ne rien faire si rien ne change
		return $fichier;
	}

	if ($parametres['zoom'] <= 1) {
		$fichier = extraire_attribut(
			image_reduire(
				$fichier,
				intval($parametres['zoom'] * $width),
				intval($parametres['zoom'] * $height)
			),
			'src'
		);
	} else {
		$fichier = extraire_attribut(
			image_passe_partout(
				$fichier,
				intval($parametres['zoom'] * $width),
				intval($parametres['zoom'] * $height),
				true
			),
			'src'
		);
	}

	/* on vire un éventuel query string */
	$fichier = parse_url($fichier);
	$fichier = $fichier['path'];

	if (empty($fichier)) {
		return $fichier_original;
	}

	list($width, $height) = getimagesize($fichier);
	$width = abs($width - intval($parametres['x1']));
	$height = abs($height - intval($parametres['y1']));
	$fichier = extraire_attribut(
		image_recadre(
			$fichier,
			$width,
			$height,
			'bottom right'
		),
		'src'
	);

	$width = abs(intval($parametres['x2']) - intval($parametres['x1']));
	$height = abs(intval($parametres['y2']) - intval($parametres['y1']));
	$fichier = extraire_attribut(
		image_recadre(
			$fichier,
			$width,
			$height,
			'top left'
		),
		'src'
	);

	$fichier = massicot_appliquer_filtre_spip($fichier, $parametres['filtre'] ?? 'aucun');

	return $fichier;
}

/**
 * Applique un effet via les filtres d'image natifs de SPIP 4.
 */
function massicot_appliquer_filtre_spip($fichier, $filtre) {
	$filtre = in_array($filtre, massicot_filtres_disponibles(), true) ? $filtre : 'aucun';
	if ($filtre === 'aucun') {
		return $fichier;
	}
	include_spip('inc/filtres');
	include_spip('filtres/images_transforme');
	$dimensions_source = @getimagesize(parse_url($fichier, PHP_URL_PATH) ?: $fichier);
	$balise = match ($filtre) {
		'nb' => image_nb($fichier),
		'sepia' => image_sepia($fichier),
		'lumineux' => image_gamma($fichier, 24),
		'sombre' => image_gamma($fichier, -24),
		'net' => image_renforcement($fichier, 0.7),
		'flou' => image_flou($fichier, 2),
		default => $fichier,
	};
	if (!is_string($balise) || $balise === '') {
		return $fichier;
	}
	$derive = extraire_attribut($balise, 'src');
	$derive = $derive ?: $balise;
	$dimensions_derive = @getimagesize(parse_url($derive, PHP_URL_PATH) ?: $derive);
	if ($dimensions_source && $dimensions_derive
		&& ($dimensions_source[0] !== $dimensions_derive[0] || $dimensions_source[1] !== $dimensions_derive[1])) {
		$recadre = image_recadre($derive, $dimensions_source[0], $dimensions_source[1], 'center');
		$derive = extraire_attribut($recadre, 'src') ?: $derive;
	}
	return $derive;
}

/**
 * Métadonnées photographiques utiles, sans données GPS ni champs libres.
 */
function massicot_lire_exif($fichier) {
	$dimensions = $fichier ? @getimagesize($fichier) : false;
	$infos = array();
	if ($dimensions) {
		$infos['dimensions'] = $dimensions[0] . ' × ' . $dimensions[1] . ' px';
		$infos['mime'] = $dimensions['mime'] ?? '';
	}
	if (!function_exists('exif_read_data') || !preg_match('/\.(jpe?g)$/i', parse_url((string) $fichier, PHP_URL_PATH) ?: $fichier)) {
		return array_filter($infos);
	}
	$exif = @exif_read_data($fichier, 'IFD0,EXIF', true, false);
	if (!is_array($exif)) {
		return array_filter($infos);
	}
	$ifd0 = $exif['IFD0'] ?? array();
	$prise = $exif['EXIF'] ?? array();
	$champs = array(
		'appareil' => trim(($ifd0['Make'] ?? '') . ' ' . ($ifd0['Model'] ?? '')),
		'objectif' => $prise['LensModel'] ?? '',
		'prise_de_vue' => $prise['DateTimeOriginal'] ?? '',
		'exposition' => $prise['ExposureTime'] ?? '',
		'ouverture' => isset($prise['FNumber']) ? 'f/' . massicot_exif_fraction($prise['FNumber']) : '',
		'iso' => isset($prise['ISOSpeedRatings']) ? 'ISO ' . (is_array($prise['ISOSpeedRatings']) ? reset($prise['ISOSpeedRatings']) : $prise['ISOSpeedRatings']) : '',
		'focale' => isset($prise['FocalLength']) ? massicot_exif_fraction($prise['FocalLength']) . ' mm' : '',
	);
	return array_filter(array_merge($infos, $champs), fn($valeur) => $valeur !== '' && $valeur !== null);
}

function massicot_exif_fraction($valeur) {
	if (is_string($valeur) && preg_match('#^(-?\d+)/(\d+)$#', $valeur, $m) && (int) $m[2] !== 0) {
		return round((int) $m[1] / (int) $m[2], 2);
	}
	return $valeur;
}

function massicot_label_exif($cle) {
	return _T('massicot:exif_' . preg_replace('/[^a-z0-9_]/', '', strtolower((string) $cle)));
}

/**
 * Massicoter un document
 *
 * À utiliser comme filtre sur les balises #FICHIER ou #URL_DOCUMENT
 *
 * @param string $fichier : Le fichier du document
 *
 * @return string : Un fichier massicoté
 */
function massicoter_document($fichier = false) {

	if (! $fichier) {
		return;
	}

	include_spip('base/abstract_sql');
	include_spip('inc/documents');
	$cle_fichier = set_spip_doc($fichier);
	if (array_key_exists($cle_fichier, $GLOBALS['massicot_documents_par_fichier'] ?? array())) {
		return massicoter_fichier($fichier, $GLOBALS['massicot_documents_par_fichier'][$cle_fichier]);
	}

	$parametres = sql_getfetsel(
		'traitements',
		'spip_massicotages as M' .
		' INNER JOIN spip_massicotages_liens as L ON L.id_massicotage = M.id_massicotage' .
		' INNER JOIN spip_documents as D ON (D.id_document = L.id_objet AND L.objet="document")',
		'D.fichier='.sql_quote($cle_fichier)
	);

	if (!is_null($parametres)) {
		$parametres = massicot_decoder_parametres($parametres);
	} else {
		$parametres = array();
	}
	$GLOBALS['massicot_documents_par_fichier'][$cle_fichier] = $parametres;

	return massicoter_fichier($fichier, $parametres);
}

/**
 * Massicoter un logo donné par son nom de fichier
 *
 * Utilisé par formulaires/inc-apercu-logo
 *
 * @param string $fichier : Le fichier à massicoter
 * @param string $objet : Le type d'objet
 * @param string $id_obejt : L'identifiant de l'objet
 *
 * @return string : Un fichier massicoté
 */
function massicoter_objet($fichier, $objet, $id_objet, $role = null) {
	if (is_string($fichier) && stripos($fichier, '<img') !== false) {
		$source = massicot_chemin_image($objet, $id_objet, $role);
		$derive = $source ? massicoter_fichier(
			$source,
			massicot_get_parametres($objet, $id_objet, $role)
		) : '';
		if (!$derive || $derive === $source) {
			return $fichier;
		}
		$dimensions = @getimagesize(parse_url($derive, PHP_URL_PATH) ?: $derive);
		return massicot_remplacer_premiere_image_html($fichier, $derive, $dimensions);
	}
	return massicoter_fichier($fichier, massicot_get_parametres($objet, $id_objet, $role));
}

/**
 * Remplace uniquement la source de la première image en conservant tous les
 * attributs HTML produits par SPIP.
 */
function massicot_remplacer_premiere_image_html($html, $derive, $dimensions = false) {
	include_spip('inc/filtres');
	return preg_replace_callback(
		'#<img\\b[^>]*>#i',
		function ($match) use ($derive, $dimensions) {
			$balise = inserer_attribut($match[0], 'src', $derive);
			if ($dimensions) {
				$balise = inserer_attribut($balise, 'width', $dimensions[0]);
				$balise = inserer_attribut($balise, 'height', $dimensions[1]);
			}
			return $balise;
		},
		$html,
		1
	);
}

/**
 * Applique un recadrage aux seules images qui affichent la source de l'objet.
 *
 * SPIP reste propriétaire de la balise HTML et de tous ses attributs. Massicot
 * ne remplace que le src lorsqu'un dérivé a effectivement été produit.
 */
function massicot_appliquer_recadrage_html($html, $objet, $id_objet, $role = '') {
	$source = massicot_chemin_image($objet, $id_objet, $role);
	$parametres = massicot_get_parametres($objet, $id_objet, $role);
	if (!$html || !$source || !$parametres) {
		return $html;
	}

	$derive = massicoter_fichier($source, $parametres);
	if (!$derive || $derive === $source) {
		return $html;
	}

	include_spip('inc/filtres');
	$sources = array(parse_url($source, PHP_URL_PATH) ?: $source);
	if (objet_type($objet) === 'document') {
		$original = sql_getfetsel('fichier', 'spip_documents', 'id_document=' . (int) $id_objet);
		if ($original) {
			$sources[] = parse_url($original, PHP_URL_PATH) ?: $original;
		}
	}
	$noms_sources = array_unique(array_map('basename', $sources));
	$dimensions = @getimagesize(parse_url($derive, PHP_URL_PATH) ?: $derive);
	return preg_replace_callback(
		'#<img\\b[^>]*>#i',
		function ($match) use ($noms_sources, $derive, $dimensions) {
			$src = extraire_attribut($match[0], 'src');
			$src_path = $src ? (parse_url($src, PHP_URL_PATH) ?: $src) : '';
			if (!$src_path || !in_array(basename($src_path), $noms_sources, true)) {
				return $match[0];
			}
			$balise = inserer_attribut($match[0], 'src', $derive);
			if ($dimensions) {
				$balise = inserer_attribut($balise, 'width', $dimensions[0]);
				$balise = inserer_attribut($balise, 'height', $dimensions[1]);
			}
			return $balise;
		},
		$html
	);
}

/**
 * Applique le dérivé à l'aperçu natif du formulaire editer_document.
 *
 * Cet aperçu est lui-même une vignette SPIP et ne porte donc plus le nom du
 * fichier source. Le conteneur stable `.editer_apercu` est utilisé comme point
 * d'intégration ciblé, sans copie du squelette de Médias.
 */
function massicot_appliquer_recadrage_apercu_document($html, $id_document) {
	$source = massicot_chemin_image('document', $id_document);
	$parametres = massicot_get_parametres('document', $id_document);
	$derive = ($source && $parametres) ? massicoter_fichier($source, $parametres) : '';
	if (!$derive || $derive === $source) {
		return $html;
	}

	include_spip('inc/filtres');
	$dimensions = @getimagesize(parse_url($derive, PHP_URL_PATH) ?: $derive);
	return preg_replace_callback(
		'#(<div\\b[^>]*class=(["\'])[^"\']*\\bediter_apercu\\b[^"\']*\\2[^>]*>.*?)(<img\\b[^>]*>)(.*?</div>)#is',
		function ($match) use ($derive, $dimensions) {
			$balise = inserer_attribut($match[3], 'src', $derive);
			if ($dimensions) {
				$balise = inserer_attribut($balise, 'width', $dimensions[0]);
				$balise = inserer_attribut($balise, 'height', $dimensions[1]);
			}
			return $match[1] . $balise . $match[4];
		},
		$html,
		1
	);
}

/**
 * Massicoter un logo document
 *
 * Traitement automatique sur les balises #LOGO_DOCUMENT
 *
 * @param string $fichier : Le logo
 *
 * @return string : Un logo massicoté
 */
function massicoter_logo_document($logo, $doc = array()) {

	include_spip('inc/filtres');
	include_spip('inc/filtres_images_mini');
	include_spip('base/abstract_sql');

	$id_vignette = sql_getfetsel(
		'id_vignette',
		'spip_documents',
		'id_document='.intval($doc['id_document'])
	);

	/* S'il y a un id_vignette, on l'utilise */
	if ($id_vignette) {
		$doc['id_document'] = $id_vignette;
		unset($doc['fichier']);
	}

	/* S'il n'y a pas de fichier dans la pile, on va le chercher dans
	   la table documents */
	if (! isset($doc['fichier'])) {
		$row = sql_fetsel(
			'fichier, extension',
			'spip_documents',
			'id_document='.intval($doc['id_document'])
		);
		if (!$row) {
			return $logo;
		}
		$doc['fichier'] = $row['fichier'];
		$doc['extension'] = $row['extension'];
	}

	/* Si le document en question n'est pas une image, on ne fait rien */
	if ((! $logo)
		or (isset($doc['extension']) && !massicot_extension_recadrable($doc['extension']))) {
		return $logo;
	}

	$fichier = extraire_attribut($logo, 'src');
	if (empty($fichier)) {
		return $logo;
	}

	/* On se débarasse d'un éventuel query string */
	$fichier = preg_replace('#\?[0-9]+#', '', $fichier);

	list($largeur_logo, $hauteur_logo) =
		getimagesize($fichier);

	$fichier_massicote = massicoter_document(get_spip_doc($doc['fichier']));

	/* Comme le logo reçu en paramètre peut avoir été réduit grâce aux
	   paramètres de la balise LOGO_, il faut s'assurer que l'image
	   qu'on renvoie fait bien la même taille que le logo qu'on a
	   reçu. */
	$balise_reduite = image_reduire(
		$fichier_massicote,
		$largeur_logo,
		$hauteur_logo
	);
	$src_reduit = extraire_attribut($balise_reduite, 'src');
	$largeur_reduite = extraire_attribut($balise_reduite, 'width');
	$hauteur_reduite = extraire_attribut($balise_reduite, 'height');
	return preg_replace_callback(
		'#<img\\b[^>]*>#i',
		function ($match) use ($src_reduit, $largeur_reduite, $hauteur_reduite) {
			$balise = inserer_attribut($match[0], 'src', $src_reduit);
			if ($largeur_reduite) {
				$balise = inserer_attribut($balise, 'width', $largeur_reduite);
			}
			if ($hauteur_reduite) {
				$balise = inserer_attribut($balise, 'height', $hauteur_reduite);
			}
			return $balise;
		},
		$logo,
		1
	);
}

/**
 * Massicoter un logo
 *
 * Traitement automatique sur les balises #LOGO_*
 *
 * @param string $fichier : Le logo
 *
 * @return string : Un logo massicoté
 */
function massicoter_logo($logo, $objet_type = null, $id_objet = null, $role = null, $env = null) {

	include_spip('inc/filtres');

	if (! $logo) {
		return $logo;
	}

	$src     = extraire_attribut($logo, 'src');
	$onmouseover = extraire_attribut($logo, 'onmouseover');
	$onmouseout  = extraire_attribut($logo, 'onmouseout');

	/* S'il n'y a pas d'id_objet, on essaie de le deviner avec le nom du
	   fichier, c'est toujours mieux que rien. Sinon on abandonne… */
	if (is_null($id_objet) or is_null($objet_type)) {
		$objet = massicot_trouver_objet_logo($src);

		/* Si le plugin roles_documents est activé, l'objet n'est pas forcément
		 * devinable via le nom de fichier (notamment avec la balise
		 * LOGO_ARTICLE_RUBRIQUE). Dans ce cas on essaie de bidouiller un truc
		 * avec l'environnement. */
		if (test_plugin_actif('roles_documents') and $env) {
			if (isset($env['id_article'])) {
				$objet = array(
					'objet' => 'article',
					'id_objet' => $env['id_article'],
				);
			} elseif (isset($env['id_rubrique'])) {
				$objet = array(
					'objet' => 'rubrique',
					'id_objet' => $env['id_rubrique'],
				);
			}
		}

		if (is_null($objet)) {
			return $logo;
		}

		$objet_type = $objet['objet'];
		$id_objet	= $objet['id_objet'];
	}

	$parametres = massicot_get_parametres($objet_type, $id_objet, $role);
	$source = massicot_chemin_image($objet_type, $id_objet, $role) ?: $src;
	$fichier = massicoter_fichier($source, $parametres);

	if ($onmouseout) {
		$onmouseout = str_replace($src, $fichier, $onmouseout);
	}

	if ($onmouseover) {
		$src_off = preg_replace('/^.*[\']([^\']+)[\']/', '$1', $onmouseover);
		$parametres_off = massicot_get_parametres($objet_type, $id_objet, 'logo_survol');
		$source_off = massicot_chemin_image($objet_type, $id_objet, 'logo_survol') ?: $src_off;
		$fichier_off = massicoter_fichier($source_off, $parametres_off);
		$onmouseover = str_replace($src_off, $fichier_off, $onmouseover);
	}

	return preg_replace_callback(
		'#<img\\b[^>]*>#i',
		function ($match) use ($fichier, $onmouseover, $onmouseout) {
			$balise = inserer_attribut($match[0], 'src', $fichier);
			if ($onmouseover) {
				$balise = inserer_attribut($balise, 'onmouseover', $onmouseover);
			}
			if ($onmouseout) {
				$balise = inserer_attribut($balise, 'onmouseout', $onmouseout);
			}
			return $balise;
		},
		$logo,
		1
	);
}

/**
 * Traitement auto sur les balises #LARGEUR
 *
 * @param string $largeur : La largeur renvoyée par la balise
 *
 * @return string : La largeur de l'image après massicotage
 */
function massicoter_largeur($largeur, $doc = array()) {

	if ((! $largeur) or (! isset($doc['id_document']))) {
		return $largeur;
	}

	$parametres = massicot_get_parametres('document', $doc['id_document']);

	// Si les paramètre de l'image sont vide, on renvoie la largeur directement
	if (empty($parametres)) {
		return $largeur;
	}

	return (string) round(($parametres['x2'] - $parametres['x1']));
}

/**
 * Traitement auto sur les balises #HAUTEUR
 *
 * @param string $hauteur : La hauteur renvoyée par la balise
 *
 * @return string : La hauteur de l'image après massicotage
 */
function massicoter_hauteur($hauteur, $doc = array()) {

	if ((! $hauteur) or (! isset($doc['id_document']))) {
		return $hauteur;
	}

	$parametres = massicot_get_parametres('document', $doc['id_document']);

	// Si les paramètre de l'image sont vide, on renvoie la hauteur directement
	if (empty($parametres)) {
		return $hauteur;
	}

	return (string) round(($parametres['y2'] - $parametres['y1']));
}

