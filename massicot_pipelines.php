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
	if ($type === 'document') {
		$ext = sql_getfetsel(
			'extension',
			'spip_documents',
			'id_document='.intval($id)
		);
		if ($ext === 'svg') {
			return false;
		}
	} else {
		$chercher_logo = charger_fonction('chercher_logo', 'inc');
		foreach(array('on', 'off') as $role) {
		$logo = $chercher_logo($id, $type, $role);
			if (is_array($logo)) {
				$logo = array_shift($logo);
				if (!is_null($logo)) {
					$logo = pathinfo($logo);
					if (!empty($logo['extension']) && ($logo['extension'] === 'svg')) {
						return false;
					}
				}
			}
		}
	}
	return autoriser('modifier', $type, $id, $qui, $opt);
}

/**
 * Insérer le plugin jquery de selection du cadre
 *
 * @pipeline jquery_plugins
 * @param  array $scripts  Les scripts qui seront insérés dans la page
 * @return array	   La liste des scripts complétée
 */
function massicot_jquery_plugins($scripts) {

	if (test_espace_prive() && _request('exec') === 'massicoter_image') {
		$scripts[] = 'javascripts/formulaireMassicoterImage.js';
	}

	return $scripts;
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

	if (($flux['args']['form'] === 'editer_logo')
		&& (_request('supprimer_logo_on') || _request('supprimer_logo_off'))) {
		$objet = $flux['args']['args'][0];
		$id_objet = $flux['args']['args'][1];
		$role = _request('supprimer_logo_off') ? 'logo_survol' : '';
		massicot_supprimer($objet, $id_objet, $role);
	}

	return $flux;
}

/**
 * Charge la feuille du recadreur avec le pipeline CSS natif de SPIP 4.
 */
function massicot_insert_head_css($flux) {
	if (test_espace_prive() && _request('exec') === 'massicoter_image') {
		include_spip('inc/filtres');
		$css = timestamp(find_in_path('css/massicot.css'));
		$flux .= '<link rel="stylesheet" href="' . attribut_html($css) . '" type="text/css">';
	}
	return $flux;
}

/**
 * Ajoute les actions de recadrage au formulaire natif des logos sans
 * surcharger son squelette complet.
 */
function massicot_formulaire_fond($flux) {
	if (($flux['args']['form'] ?? '') !== 'editer_logo') {
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
 * Ajouter un lien pour recadrer les vignettes des documents
 *
 * @pipeline editer_contenu_objet
 * @param  array $flux Données du pipeline
 * @return array       Données du pipeline
 */
function massicot_editer_contenu_objet($flux) {

	$html = $flux['data'];
	$args = $flux['args'];

	if ($args['type'] === 'illustrer_document') {
		include_spip('base/abstract_sql');
		include_spip('inc/autoriser');

		if ($id_vignette = sql_getfetsel(
			'id_vignette',
			'spip_documents',
			'id_document='.intval($args['id'])
		)
		and autoriser('massicoter', 'document', $args['id'])
		and autoriser('massicoter', 'document', $id_vignette)) {
			$href = generer_url_ecrire(
				'massicoter_image',
				'objet=document&id_objet=' . $id_vignette . '&redirect=' . urlencode(self())
			);
			$lien = '<a href="' . $href . '"><strong>' . _T('massicot:massicoter') . '</strong></a>';

			$repere = '<span class=\'image_loading\'>';
			$flux['data'] = str_replace($repere, $lien . $repere, $html);
		}
	}

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
