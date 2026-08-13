<?php
/**
 * Traitements du formulaire de massicotage
 *
 * @plugin	   Massicot
 * @copyright  2015
 * @author	   Michel @ Vertige ASBL
 * @licence	   GNU/GPL
 */

/**
 * Saisies du formulaire de massicotage
 *
 * @return array
 *	   Tableau des saisies du formulaire
 */
function formulaires_massicoter_image_saisies_dist($objet, $id_objet, $redirect, $format = null, $role = null) {

	$saisies = array(
		array(
			'saisie' => 'hidden',
			'options' => array(
				'nom' => 'zoom',
			),
		),
		array(
			'saisie' => 'hidden',
			'options' => array(
				'nom' => 'x1',
			),
		),
		array(
			'saisie' => 'hidden',
			'options' => array(
				'nom' => 'x2',
			),
		),
		array(
			'saisie' => 'hidden',
			'options' => array(
				'nom' => 'y1',
			),
		),
		array(
			'saisie' => 'hidden',
			'options' => array(
				'nom' => 'y2',
			),
		),
	);

	if (isset($GLOBALS['presets_format_massicot'])
			and is_array($GLOBALS['presets_format_massicot'])
			and count($GLOBALS['presets_format_massicot'])) {
		$data = array();
		foreach ($GLOBALS['presets_format_massicot'] as $preset) {
			$cle = $preset['largeur'] . ':' . $preset['hauteur'];
			$data[$cle] = _T($preset['nom']);
		}

		$saisies[] = array(
			'saisie' => 'selection',
			'options' => array(
				'nom' => 'format',
				'label' => _T('massicot:label_format'),
				'data' => $data,
			),
		);
	}

	return $saisies;
}

/**
 * Chargement du formulaire de massicotage
 *
 * Déclarer les champs postés et y intégrer les valeurs par défaut
 *
 * @return array
 *	   Environnement du formulaire
 */
function formulaires_massicoter_image_charger_dist($objet, $id_objet, $redirect, $format = null, $role = null) {
	include_spip('inc/autoriser');
	if (!autoriser('massicoter', $objet, $id_objet)) {
		return array(
			'editable' => false,
			'message_erreur' => _T('massicot:operation_non_autorisee'),
		);
	}

	$parametres = massicot_get_parametres($objet, $id_objet, $role);
	$recadrage_existant = (bool) $parametres;

	if (! $parametres) {
		$parametres = array(
			'zoom' => 1,
		);
	}

	if ($format) {
		$parametres['format'] = $format;
	}

	$parametres['objet']	= $objet;
	$parametres['id_objet'] = $id_objet;
	$parametres['role']     = $role;
	$parametres['_recadrage_existant'] = $recadrage_existant;

	return $parametres;
}

/**
 * Verifie cote serveur les coordonnees envoyees par le navigateur.
 */
function formulaires_massicoter_image_verifier_dist($objet, $id_objet, $redirect, $format = null, $role = null) {
	$erreurs = array();
	if (_request('annuler') || _request('supprimer_recadrage')) {
		return $erreurs;
	}
	include_spip('inc/autoriser');
	if (!autoriser('massicoter', $objet, $id_objet)) {
		return array('message_erreur' => _T('massicot:operation_non_autorisee'));
	}
	$chemin = massicot_chemin_image($objet, $id_objet, $role);
	$dimensions = $chemin ? @getimagesize($chemin) : false;
	if (!$dimensions) {
		$erreurs['message_erreur'] = _T('massicot:erreur_fichier_image');
		return $erreurs;
	}

	$parametres = array(
		'zoom' => _request('zoom'),
		'x1' => _request('x1'),
		'x2' => _request('x2'),
		'y1' => _request('y1'),
		'y2' => _request('y2'),
	);
	if (!massicot_normaliser_parametres($parametres, $dimensions[0], $dimensions[1])) {
		$erreurs['message_erreur'] = _T('massicot:erreur_parametres_invalides');
	}

	return $erreurs;
}

/**
 * Traitement du formulaire de massicotage
 *
 * Traiter les champs postés
 *
 * @return array
 *	   Retours des traitements
 */
function formulaires_massicoter_image_traiter_dist($objet, $id_objet, $redirect, $format = null, $role = null) {

	if (_request('supprimer_recadrage')) {
		if ($err = massicot_supprimer($objet, $id_objet, (string) $role)) {
			return array('message_erreur' => $err);
		}
	} elseif (! _request('annuler')) {
		$parametres = array(
			'zoom' => _request('zoom'),
			'x1'   => _request('x1'),
			'x2'   => _request('x2'),
			'y1'   => _request('y1'),
			'y2'   => _request('y2'),
			'role' => $role,
		);

		if ($err = massicot_enregistrer($objet, $id_objet, $parametres)) {
			spip_log($err, 'massicot.'._LOG_ERREUR);
			return array('message_erreur' => $err);
		}
	}

	return array(
		'redirect' => $redirect,
	);
}
