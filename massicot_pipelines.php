<?php
/**
 * Déclarations d'autorisations et utilisations de pipelines par Massicot
 *
 * @plugin	   Massicot
 * @copyright  2015
 * @author	   Michel @ Vertige ASBL
 * @licence	   GNU/GPL
 * @package	   SPIP\Massicot\Pipelines
 */

/**
 * Fonction du pipeline autoriser. N'a rien à faire
 *
 * @pipeline autoriser
 */
function massicot_autoriser() { }

/**
 * Autoriser le massicotage d'un document ou d'un logo
 *
 * Par défaut, l'autorisation est déléguée à 'autoriser_modifier'.
 *
 * @param  string $faire Action demandée
 * @param  string $type Type d'objet sur lequel appliquer l'action
 * @param  int $id Identifiant de l'objet
 * @param  array $qui Description de l'auteur demandant l'autorisation
 * @param  array $opt Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 */
function autoriser_massicoter_dist($faire, $type, $id, $qui, $opt) {
	include_spip('massicot_fonctions');
	if ($type === 'document') {
		$ext = sql_getfetsel(
			'extension',
			'spip_documents',
			'id_document='.intval($id)
		);
		if (!massicot_extension_recadrable($ext)) {
			return false;
		}
	}
	return autoriser('modifier', $type, $id, $qui, $opt);
}

/**
 * Ajouter une action "recadrer" sur les documents
 *
 * @pipeline editer_document_actions
 * @param  array $flux Données du pipeline
 * @return array	   Données du pipeline
 */
function massicot_document_desc_actions($flux) {

	$flux['data'] .= recuperer_fond(
		'prive/squelettes/inclure/lien_recadre',
		$flux['args']
	);

	return $flux;
}

/**
 * Supprimer les traitements lorsqu'on remplace l'image d'un document
 *
 * @pipeline post_edition
 * @param  array $flux Données du pipeline
 * @return array       Données du pipeline
 */
function massicot_post_edition($flux) {

	if (isset($flux['args']['type']) and ($flux['args']['type'] === 'document')
	    and isset($flux['data']['fichier'])) {
		massicot_supprimer_tous('document', $flux['args']['id_objet']);
	}

	return $flux;
}

/**
 * Supprimer les traitements lorsqu'on supprime un logo
 *
 * @pipeline formulaire_traiter
 * @param  array $flux Données du pipeline
 * @return array       Données du pipeline
 */
function massicot_formulaire_traiter($flux) {

	if (($flux['args']['form'] ?? '') === 'editer_logo') {
		$objet = $flux['args']['args'][0] ?? '';
		$id_objet = (int) ($flux['args']['args'][1] ?? 0);
		$fichiers = is_array($_FILES ?? null)
			? $_FILES
			: ($GLOBALS['HTTP_POST_FILES'] ?? array());
		$roles = massicot_roles_logo_modifies(
			$fichiers,
			(bool) _request('supprimer_logo_on'),
			(bool) _request('supprimer_logo_off'),
			empty($flux['data']['message_erreur'])
		);
		foreach ($roles as $role) {
			massicot_supprimer($objet, $id_objet, $role);
		}
	}

	return $flux;
}

/**
 * Détermine quels rôles ont réellement été supprimés ou remplacés par le
 * formulaire natif de SPIP.
 */
function massicot_roles_logo_modifies($fichiers, $supprimer_on, $supprimer_off, $traitement_ok = true) {
	$roles = array();
	if ($supprimer_on) {
		$roles[] = '';
	}
	if ($supprimer_off) {
		$roles[] = 'logo_survol';
	}
	if ($traitement_ok && is_array($fichiers)) {
		if (isset($fichiers['logo_on']) && (int) ($fichiers['logo_on']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
			$roles[] = '';
		}
		if (isset($fichiers['logo_off']) && (int) ($fichiers['logo_off']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
			$roles[] = 'logo_survol';
		}
	}
	return array_values(array_unique($roles));
}

/**
 * Charge la feuille du recadreur dans l'en-tête de l'espace privé.
 */
function massicot_header_prive($flux) {
	if (test_espace_prive() && _request('exec') === 'massicoter_image') {
		include_spip('inc/filtres');
		$css = timestamp(find_in_path('css/massicot.css'));
		$flux .= '<link rel="stylesheet" href="' . attribut_html($css) . '" type="text/css">';
		$js = timestamp(find_in_path('javascripts/formulaireMassicoterImage.js'));
		$flux .= '<script src="' . attribut_html($js) . '"></script>';
	}
	return $flux;
}

/**
 * Ajoute les actions de recadrage au formulaire natif des logos sans
 * surcharger son squelette complet.
 */
function massicot_formulaire_fond($flux) {
	$form = $flux['args']['form'] ?? '';
	if ($form === 'illustrer_document') {
		include_spip('base/abstract_sql');
		include_spip('inc/autoriser');
		$contexte = $flux['args']['contexte'] ?? array();
		$args = $flux['args']['args'] ?? array();
		$id_document = (int) ($contexte['id_document'] ?? ($args[0] ?? 0));
		$id_vignette = $id_document ? (int) sql_getfetsel(
			'id_vignette',
			'spip_documents',
			'id_document=' . $id_document
		) : 0;
		if (!$id_vignette || !autoriser('massicoter', 'document', $id_vignette)) {
			return $flux;
		}
		$actions = recuperer_fond(
			'prive/squelettes/inclure/massicot_actions_document',
			array('id_document' => $id_vignette, 'redirect' => self())
		);
		if ($actions) {
			$flux['data'] = preg_replace('#</form>#i', $actions . '</form>', $flux['data'], 1);
		}
		return $flux;
	}

	if ($form === 'editer_document') {
		include_spip('inc/autoriser');
		$contexte = $flux['args']['contexte'] ?? array();
		$args = $flux['args']['args'] ?? array();
		$id_document = (int) ($contexte['id_document'] ?? ($args[0] ?? 0));
		if (!$id_document || !autoriser('massicoter', 'document', $id_document)) {
			return $flux;
		}
		$actions = recuperer_fond(
			'prive/squelettes/inclure/massicot_actions_document',
			array('id_document' => $id_document, 'redirect' => self())
		);
		if ($actions) {
			$flux['data'] = preg_replace('#</form>#i', $actions . '</form>', $flux['data'], 1);
		}
		$flux['data'] = massicot_appliquer_recadrage_html(
			$flux['data'],
			'document',
			$id_document
		);
		$flux['data'] = massicot_appliquer_recadrage_apercu_document($flux['data'], $id_document);
		return $flux;
	}

	if ($form !== 'editer_logo') {
		return $flux;
	}

	$contexte = $flux['args']['contexte'] ?? array();
	$args = $flux['args']['args'] ?? array();
	$objet = $contexte['objet'] ?? ($args[0] ?? '');
	$id_objet = isset($contexte['id_objet'])
		? (int) $contexte['id_objet']
		: (isset($args[1]) ? (int) $args[1] : 0);
	if (!$objet) {
		return $flux;
	}

	$actions = recuperer_fond(
		'prive/squelettes/inclure/massicot_actions_logo',
		array('objet' => $objet, 'id_objet' => $id_objet, 'redirect' => self())
	);
	if ($actions) {
		$flux['data'] = preg_replace('#</form>#i', $actions . '</form>', $flux['data'], 1);
	}

	// Le formulaire et ses attributs restent natifs : seul le src est dérivé.
	$flux['data'] = massicot_appliquer_recadrage_html($flux['data'], $objet, $id_objet, '');
	$flux['data'] = massicot_appliquer_recadrage_html($flux['data'], $objet, $id_objet, 'logo_survol');
	return $flux;
}

/**
 * Appliquer le recadrage sur l'image affichée dans le formulaire illustrer_document
 *
 * @pipeline editer_contenu_objet
 * @param  array $flux Données du pipeline
 * @return array       Données du pipeline
 */
function massicot_formulaire_charger($flux) {

	if (($flux['args']['form'] === 'illustrer_document')
			&& !empty($flux['data']['id_vignette'])
			&& !empty($flux['data']['vignette'])) {
		$parametres = massicot_get_parametres(
			'document',
			$flux['data']['id_vignette']
		);

		$flux['data']['vignette'] = massicoter_fichier(
			$flux['data']['vignette'],
			$parametres
		);
	}

	return $flux;
}

/**
 * Normalise l'orientation EXIF des images générées par les modèles SPIP 4.
 *
 * Le pipeline post_propre couvre notamment les raccourcis <docXX>, <imgXX>
 * et les portfolios. Il ne modifie jamais la source éditoriale et devient
 * naturellement neutre pour une image sans orientation EXIF.
 *
 * @pipeline post_propre
 */
function massicot_post_propre($html) {
	if (!is_string($html) || $html === '' || stripos($html, '<img') === false) {
		return $html;
	}
	include_spip('massicot_fonctions');
	return preg_replace_callback(
		'#<img\b[^>]*>#i',
		function ($correspondance) {
			return massicot_normaliser_images_html_spip4($correspondance[0]);
		},
		$html
	);
}
