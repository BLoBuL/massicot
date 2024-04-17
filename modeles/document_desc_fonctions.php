<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// constante _BOUTON_MODE_IMAGE
include_spip('modeles/document_case_fonctions');
if (!function_exists('affiche_bouton_mode_image_portfolio')) {
	if (!defined('_BOUTON_MODE_IMAGE')) {
		define('_BOUTON_MODE_IMAGE', true);
	}

	function affiche_bouton_mode_image_portfolio($inclus) {
		if (!defined('_COMPORTEMENT_HISTORIQUE_PORTFOLIO') or _COMPORTEMENT_HISTORIQUE_PORTFOLIO === false) {
			return '';
		}
		if ($inclus === 'image' and _BOUTON_MODE_IMAGE) {
			return ' ';
		}
		return '';
	}
}
