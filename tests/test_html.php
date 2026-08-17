<?php

function include_spip($fichier) {}
function inserer_attribut($html, $nom, $valeur) {
	$valeur = htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
	if (preg_match('/\\s' . preg_quote($nom, '/') . '=("|\')[^"\']*\\1/i', $html)) {
		return preg_replace(
			'/\\s' . preg_quote($nom, '/') . '=("|\')[^"\']*\\1/i',
			' ' . $nom . '="' . $valeur . '"',
			$html,
			1
		);
	}
	return preg_replace('/>$/', ' ' . $nom . '="' . $valeur . '">', $html, 1);
}

require dirname(__DIR__) . '/massicot_fonctions.php';

$html = '<picture><source srcset="original.webp 2x"><img src="original.jpg" alt="Portrait" loading="lazy" data-test="oui" class="logo"></picture>';
$resultat = massicot_remplacer_premiere_image_html($html, 'cache/derive.jpg', array(60, 50));
$interface = file_get_contents(dirname(__DIR__) . '/formulaires/massicoter_image.html');
$javascript = file_get_contents(dirname(__DIR__) . '/javascripts/formulaireMassicoterImage.js');
$style_prive = file_get_contents(dirname(__DIR__) . '/prive/style_prive_plugin_massicot.html');
$lang_fr = file_get_contents(dirname(__DIR__) . '/lang/massicot_fr.php');
$tests = array(
	'explication compatibilite explicite' => str_contains($lang_fr, 'Compatibilité avec les squelettes Massicot 1.x')
		&& str_contains($lang_fr, 'La désactivation ne supprime ni les images ni les recadrages enregistrés.'),
	'feuille statique non compilee comme squelette' => !str_contains($style_prive, '#INCLURE{fond=css/massicot.css}'),
	'source remplacee' => str_contains($resultat, 'src="cache/derive.jpg"'),
	'dimensions actualisees' => str_contains($resultat, 'width="60"') && str_contains($resultat, 'height="50"'),
	'attributs preserves' => str_contains($resultat, 'alt="Portrait"')
		&& str_contains($resultat, 'loading="lazy"')
		&& str_contains($resultat, 'data-test="oui"')
		&& str_contains($resultat, 'class="logo"'),
	'source picture preservee' => str_contains($resultat, 'srcset="original.webp 2x"'),
	'pipeline ignore image distante' => massicot_normaliser_images_html_spip4(
		'<img src="https://example.test/photo.jpg" alt="Test">'
	) === '<img src="https://example.test/photo.jpg" alt="Test">',
	'pipeline ignore PNG' => massicot_normaliser_images_html_spip4(
		'<img src="IMG/png/photo.png" alt="Test">'
	) === '<img src="IMG/png/photo.png" alt="Test">',
	'interface rotations SPIP' => str_contains($interface, 'data-angle="-90"')
		&& str_contains($interface, 'data-rotation="180"')
		&& str_contains($interface, 'data-angle="90"'),
	'apercu rotation final' => str_contains($interface, 'massicot-apercu-sortie-canvas')
		&& str_contains($javascript, 'drawOutputPreview')
		&& str_contains($javascript, 'outputPreviewContext.rotate'),
	'apercu proportions dynamiques' => str_contains($javascript, 'outputPreview.width =')
		&& str_contains($javascript, 'outputPreview.height =')
		&& str_contains($javascript, 'quarterTurn ? cropHeight : cropWidth'),
	'formats proposes' => str_contains($interface, 'data-ratio="1"')
		&& str_contains($interface, 'data-ratio="2"')
		&& str_contains($interface, 'data-ratio="0.5"')
		&& str_contains($interface, 'data-ratio="0.75"')
		&& str_contains($interface, 'data-ratio="1.3333333333"'),
);

$echecs = array_keys(array_filter($tests, fn($ok) => !$ok));
if ($echecs) {
	fwrite(STDERR, 'ECHEC: ' . implode(', ', $echecs) . PHP_EOL);
	exit(1);
}

echo count($tests) . " tests HTML OK\n";
