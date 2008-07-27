<?php
if (file_exists('../../../wp-load.php')){
	require_once '../../../wp-load.php';
} else {
	require_once '../../../wp-config.php';
}

require_once 'comp.class.php';

global $scriptcomp;

$comp = new Compressor($scriptcomp->getScripts(), get_option('blog_charset'));
$comp->sendHeader();
echo $comp->getContent();
?>