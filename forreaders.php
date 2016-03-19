<?php
/** 
 * Файл для пакетной обработки постов.
 * Генерирует файлы для чтения в форматах: PDF, ePub, mobi, fb2,
 * если эти файлы отсутствуют в заданном каталоге.
 *
 * использует плагин Bg forReaders и должен распологаться в каталоге плагина
 *
 */
header("Content-type: text/html; charset: UTF-8");
if(!defined('PATH')):
//	$value_const = str_replace('wp-content/plugins', '', dirname(__DIR__));
	$value_const =  dirname(dirname(dirname(__DIR__)));
	define("PATH", $value_const);
else: 
	exit();
endif; 
require_once(PATH.'/wp-load.php');

if (isset($argv[1])) {
	$arg = $argv[1];
	$e=explode("=",$arg);
    if(count($e)==2)
        $_GET[$e[0]]=$e[1];
    else    
        $_GET[$e[0]]=0;
}
// else exit; // Запрет запуска не из консоли

$e=explode("=",$argv[2]);
$echo_on = ("echo" == $e[0]);


$debug_file = dirname(__FILE__ )."/forreaders.log";
if (file_exists ($debug_file) ) unlink ($debug_file);
error_log(date ("j-m-Y H:i"). " ===================== Start the batch mode =====================". PHP_EOL, 3, $debug_file);
if ($echo_on) echo date ("j-m-Y H:i"). " ===================== Start the batch mode =====================". PHP_EOL;
$bg_forreaders = new BgForReaders();
$starttime =  microtime(true);

// Если указан список постов, то обрабатываем только их
if (isset($_GET['id'])) {
	$id_list = explode ( ',' , $_GET['id'] );
	$cnt = count($id_list);
	error_log(" List of posts (".$cnt."): ".$_GET['id']. PHP_EOL, 3, $debug_file);
	if ($echo_on) echo " List of posts (".$cnt."): ".$_GET['id']. PHP_EOL;

	$args = array('post_type' => array( 'post', 'page'), 'post_status' => 'publish', 'numberposts' => 1, 'offset' => 0, 'orderby' => 'ID');
	for ($i = 0; $i < $cnt; $i++){
		$post = get_post($id_list[$i]);
		
		if ($post) {
			error_log(date ("j-m-Y H:i"). " ".$post->ID. " ".$post->post_name, 3, $debug_file);
			if ($echo_on) echo date ("j-m-Y H:i"). " ".$post->ID. " ".$post->post_name;
			$the_time =  microtime(true);
			$bg_forreaders->generate ($post->ID);
			error_log(" - files generated in ".round((microtime(true)-$the_time)*1000, 1)." msec.". PHP_EOL, 3, $debug_file);
			if ($echo_on) echo " - files generated in ".round((microtime(true)-$the_time)*1000, 1)." msec.". PHP_EOL;
		}
	}
// Иначе если указан параметр all, то обрабатываем все посты
} elseif (isset($_GET['all'])) {
	$cnt = wp_count_posts()->publish;
	error_log(" All posts (".$cnt.")". PHP_EOL, 3, $debug_file);
	if ($echo_on) echo " All posts (".$cnt.")". PHP_EOL;

	for ($i = 0; $i < $cnt; $i++){
		$args = array('post_type' => array( 'post', 'page'), 'post_status' => 'publish', 'numberposts' => 1, 'offset' => $i, 'orderby' => 'ID');
		$posts_array = get_posts($args);
		$post = $posts_array[0];

		// Исключения 
		$ex_cats = explode ( ',' , get_option('bg_forreaders_excat') );	// если запрещены некоторые категории
		foreach($ex_cats as $cat) {
			foreach((get_the_category()) as $category) { 
				if (trim($cat) == $category->category_nicename) {
					error_log(" - category (".$category->category_nicename .") banned.". PHP_EOL, 3, $debug_file);
					if ($echo_on) echo " - category (".$category->category_nicename .") banned.". PHP_EOL;
					continue 3;
				}
			}
		}
		error_log(date ("j-m-Y H:i"). " ".$post->ID. " ".$post->post_name, 3, $debug_file);
		if ($echo_on) echo date ("j-m-Y H:i"). " ".$post->ID. " ".$post->post_name;
		$the_time =  microtime(true);
		$bg_forreaders->generate ($post->ID);
		error_log(" - files generated in ".round((microtime(true)-$the_time)*1000, 1)." msec.". PHP_EOL, 3, $debug_file);
	}
}
error_log("TOTAL TIME: ".round((microtime(true)-$starttime), 1)." sec.". PHP_EOL, 3, $debug_file);
error_log(date ("j-m-Y H:i"). " ===================== Finish the batch mode =====================". PHP_EOL, 3, $debug_file);
if ($echo_on) echo "TOTAL TIME: ".round((microtime(true)-$starttime), 1)." sec.". PHP_EOL;
if ($echo_on) echo date ("j-m-Y H:i"). " ===================== Finish the batch mode =====================". PHP_EOL;
exit;