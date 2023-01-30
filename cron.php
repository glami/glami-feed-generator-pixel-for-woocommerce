<?php
$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once($root.'/wp-load.php');

$feed = new Glami_Feed_Generator_Pixel_For_Woocommerce_Engine();
$feed->generate();