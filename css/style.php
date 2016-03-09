<?php
header("Content-type: text/css; charset: UTF-8");
$absolute_path = explode('wp-content', $_SERVER['SCRIPT_FILENAME']);
$wp_load = $absolute_path[0] . 'wp-load.php';
require_once($wp_load);
if (function_exists ( 'get_option' )){
	$zoom = get_option('bg_forreaders_zoom');
	echo $zoom."<br>";
}
?>
.bg_forreaders {
}
div.bg_forreaders {
	<?php if($zoom) echo 'height: '. (88*$zoom) .'px;' ?>
	padding: 0px;
	margin: 0px;
	border: 0px;
	font-size: <?php echo ($zoom?0:1) ?>em;
}

.bg_forreaders div {
	padding: 0px;
	margin: 0px;
	border: 0px;
	display: inline;
}
.bg_forreaders span a {
	padding: 0px 0px 4em 0px;
	margin: 0px;
	border: 0px;
	font-size: 0.8em !important;
	box-shadow: none !important;
	text-decoration:  none !important;
}

.bg_forreaders div a {
	padding: 0px <?php echo (69*$zoom) ?>px <?php echo (88*$zoom) ?>px 0px;
	margin: 0px <?php echo (10*$zoom) ?>px 0px 0px;
	border: 0px;
	font-size: 0em !important;
	box-shadow: none !important;
	text-decoration:  none !important;
}

.bg_forreaders .pdf {
	background: url(document-pdf.png) no-repeat 50% 50%;
	background-size: contain;
}

.bg_forreaders .epub {
	background: url(document-epub.png) no-repeat 50% 50%;
	background-size: contain;
}

.bg_forreaders .mobi{
	background: url(document-mobi.png) no-repeat 50% 50%;
	background-size: contain;
}

.bg_forreaders .fb2 {
	background: url(document-fb2.png) no-repeat 50% 50%;
	background-size: contain;
}
