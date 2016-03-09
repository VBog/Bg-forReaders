
<?php
/**
 * Файл для пакетной обработки постов.
 * Генерирует файлы для чтения в форматах: PDF, ePub, mobi, fb2,
 * если эти файлы отсутствуют в заданном каталоге.
 *
 * использует плагин Bg forReaders и должен распологаться в каталоге плагина
 */
?>
<?php
header("Content-type: text/html; charset: UTF-8");
$absolute_path = explode('wp-content', $_SERVER['SCRIPT_FILENAME']);
$wp_load = $absolute_path[0] . 'wp-load.php';
require_once($wp_load);

//define('WP_USE_THEMES', false); get_header();

$debug_file = dirname(__FILE__ )."/forreaders.log";
if (file_exists ($debug_file) ) unlink ($debug_file);
echo date ("j-m-Y H:i"). " ===================== Start the batch mode =====================<br>";
error_log(date ("j-m-Y H:i"). " ===================== Start the batch mode =====================\n", 3, $debug_file);
$bg_forreaders = new BgForReaders();
	

$posts = get_posts( array('numberposts'     => -1 ) );
foreach ($posts as $post){ 
	echo date ("j-m-Y H:i"). " ".$post->post_name;
	error_log(date ("j-m-Y H:i"). " ".$post->post_name, 3, $debug_file);
	// Исключения 
	$ex_cats = explode ( ',' , get_option('bg_forreaders_excat') );	// если запрещены некоторые категории
	foreach($ex_cats as $cat) {
		foreach((get_the_category()) as $category) { 
			if (trim($cat) == $category->category_nicename) {
				echo " - category (".$category->category_nicename .") banned.<br>";
				error_log(" - category (".$category->category_nicename .") banned.\n", 3, $debug_file);
				continue 3;
			}
		}
	}
	$starttime =  microtime(true);
	$bg_forreaders->generate ($post->ID);
	echo " - files generated in ".round((microtime(true)-$starttime)*1000, 1)." msec.<br>";
	error_log(" - files generated in ".round((microtime(true)-$starttime)*1000, 1)." msec.\n", 3, $debug_file);
}

echo date ("j-m-Y H:i"). " ===================== Finish the batch mode =====================<br>";
error_log(date ("j-m-Y H:i"). " ===================== Finish the batch mode =====================\n", 3, $debug_file);
