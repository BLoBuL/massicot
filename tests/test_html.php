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
$tests = array(
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
);

$echecs = array_keys(array_filter($tests, fn($ok) => !$ok));
if ($echecs) {
	fwrite(STDERR, 'ECHEC: ' . implode(', ', $echecs) . PHP_EOL);
	exit(1);
}

echo count($tests) . " tests HTML OK\n";
