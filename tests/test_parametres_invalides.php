<?php

// Image synthétique, sans dossier temporaire ni donnée de site.
$source = 'massicot-fixture-' . getmypid() . '.png';
file_put_contents($source, base64_decode('iVBORw0KGgoAAAANSUhEUgAAACgAAAA8CAYAAAAUufjgAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAEGSURBVGhD7c6xDcIwEIXhTOINWIO5WIFdWIiGmoYaZKRI6Jfj89lHlESv+JoovvdPr+fjvWUTP2yNAkcpcNRQ4PmUmvFtq65AjnvwlsUVyLERvL2kOZADEbhR0hTIw5G4RfsP5MF/4KYCvbjpCpzdb9dw3ChRYA03SpoCeTgSt6g7MKX0xe9k/cct6g6Mwi1SoIVbZAbyYDTukRmY8WgkbpECLdwiBVq4RccIzHg4AjdKjhOYcWAEby9xBWYc6sGbNccLzDjowVuWrsAZx2v4ttUqgXznoUAF1jBkCd957Dtwmi6r4K4CvbirQC/uKtCLuwr04q4CvbirQC/uKtCLuwr04u6vD2Nqmoz8sJ1DAAAAAElFTkSuQmCC'));
register_shutdown_function(function () use ($source) { unlink($source); });
function include_spip($file) {}
function extraire_attribut($html, $name) { preg_match('/src="([^"]+)"/', $html, $m); return $m[1] ?? ''; }
function image_reduire($file, $width, $height) {
    $GLOBALS['calls'][] = array('reduce', $width, $height);
    return '<img src="' . $file . '">';
}
function image_recadre($file, $width, $height, $position) {
    if ($width < 1 || $height < 1) { throw new ValueError('Non-positive GD dimension'); }
    $GLOBALS['calls'][] = array('crop', $width, $height, $position);
    return '<img src="' . $file . '">';
}
require $argv[1] ?? dirname(__DIR__) . '/massicot_fonctions.php';
$valid = array('zoom' => '1', 'x1' => '2', 'x2' => '20', 'y1' => '3', 'y2' => '30');
$invalid = array(
    array('y2' => '3'), array('x2' => '2'), array('x2' => '1'), array('y2' => '2'),
    array('zoom' => '0'), array('zoom' => '-1'), array('zoom' => '0.001'),
    array('x1' => '-1'), array('y1' => '-1'), array('x1' => '40', 'x2' => '50'),
    array('y1' => '60', 'y2' => '70'), array('zoom' => 'invalid'), array('zoom' => INF),
    array('x2' => null), array('x2' => '2.9'),
);
foreach ($invalid as $changes) {
    $GLOBALS['calls'] = array();
    $result = massicoter_fichier($source, array_replace($valid, $changes));
    if ($result !== $source || $GLOBALS['calls']) { throw new RuntimeException('Invalid crop reached image filters'); }
}
$GLOBALS['calls'] = array();
massicoter_fichier($source, $valid);
if (count($GLOBALS['calls']) !== 3 || $GLOBALS['calls'][2] !== array('crop', 18, 27, 'top left')) {
    throw new RuntimeException('Valid crop changed');
}
$GLOBALS['calls'] = array();
if (massicoter_fichier($source, array()) !== $source || $GLOBALS['calls']) { throw new RuntimeException('Empty crop changed'); }
echo "17 synthetic crop checks OK\n";
