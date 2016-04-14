<?php
/** 
 * Файл для пакетной обработки постов.
 * Генерирует файлы для чтения в форматах: PDF, ePub, mobi, fb2,
 * если эти файлы отсутствуют в заданном каталоге.
 *
 * Использует плагин Bg forReaders и должен распологаться в каталоге плагина
 
	Параметры:
	
	* первый параметр
		id=[список id постов через запятую] - обрабатываются все указанные в списке ID посты
	или
		all=[from],[to] - обрабатываются все посты сайта из указанного диапазона,
			кроме указанных в исключениях см. настройки плагина
			(для типа 'post' - могут быть указаны исключенные или, наоборот, разрешенные категории
			 для типа 'page' - произвольное поле for_readers='on' разрешает создание файлов)
		Например,
		all - все опубликованные посты и страницы
		all=[from] - все посты, начиная с порядкового номера [from] и до конца
		all=[from],[to] - все посты, начиная с порядкового номера [from] и до номера [to]
	или 
		stack - вынимает из стека первый элемент - id поста и обрабатывает его
	
	* второй параметр
		echo - выводить информацию о выполнении скрипта на экран
 *
 */
header("Content-type: text/html; charset: UTF-8");
header("X-Accel-Buffering: no");

if(!defined('PATH')):
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
else exit; // Запрет запуска не из консоли (без параметров)

if (file_exists('lock')) exit;
$fp = fopen($lock_file, 'w'); 	// Создаем блокировочный файл
//flock($fp, LOCK_EX); 			// Блокаруем его на всякий случай

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

//	$args = array('post_type' => array( 'post', 'page'), 'post_status' => 'publish', 'numberposts' => 1, 'offset' => 0, 'orderby' => 'ID');
	for ($i = 0; $i < $cnt; $i++){
		$post = get_post($id_list[$i]);
		
		if ($post) {
			error_log(($i+1).". ".date ("j-m-Y H:i"). " ".$post->ID. " ".$post->post_name, 3, $debug_file);
			if ($echo_on) echo ($i+1).". ".date ("j-m-Y H:i"). " ".$post->ID. " ".$post->post_name;
			$the_time =  microtime(true);
			$bg_forreaders->generate ($post->ID);
			error_log(" - files generated in ".round((microtime(true)-$the_time)*1000, 1)." msec.". PHP_EOL, 3, $debug_file);
			if ($echo_on) {
				echo " - files generated in ".round((microtime(true)-$the_time)*1000, 1)." msec.". PHP_EOL;
				flush();
				ob_flush();
			}
		}
	}
// Иначе если указан параметр all, то обрабатываем все посты
} elseif (isset($_GET['all'])) {
	$cnt = wp_count_posts('post')->publish + wp_count_posts('page')->publish;
	$id_list = explode ( ',' , $_GET['all'] );
	if (isset($id_list[0])) {
		$start = intval ($id_list[0]);
		if (isset($id_list[1])) $finish = intval ($id_list[1]);
		else $finish = $cnt;
	} else {
		$start = 0;
		$finish = $cnt;
	}
	error_log(" All posts (".$cnt."): Start=".$start.", Finish=".$finish. PHP_EOL, 3, $debug_file);
	if ($echo_on) {
		echo " All posts (".$cnt."): Start=".$start.", Finish=".$finish. PHP_EOL;
		flush();
		ob_flush();
	}
	for ($i = 0; $i < $cnt; $i++){
		if ($i < $start-1) continue;
		if ($i > $finish-1) break;
		$args = array('post_type' => array( 'post', 'page'), 'post_status' => 'publish', 'numberposts' => 1, 'offset' => $i, 'orderby' => 'ID');
		$posts_array = get_posts($args);
		$post = $posts_array[0];
		error_log(($i+1).". ".date ("j-m-Y H:i"). " ".$post->ID. " ".$post->post_name. "  (".$post->post_type. ") ", 3, $debug_file);
		if ($echo_on) echo ($i+1).". ".date ("j-m-Y H:i"). " ".$post->ID. " ".$post->post_name. " (".$post->post_type. ") ";

		if (!check_exceptions ($post,  $debug_file, $echo_on)) {
		
			$the_time =  microtime(true);
			$bg_forreaders->generate ($post->ID);
			error_log(" - files generated in ".round((microtime(true)-$the_time)*1000, 1)." msec.". PHP_EOL, 3, $debug_file);
			if ($echo_on) echo " - files generated in ".round((microtime(true)-$the_time)*1000, 1)." msec.". PHP_EOL;
			flush();
			ob_flush();
		}
	}
// Иначе если указан параметр stack, то вынимаем первый элемент из стека id постов
} elseif (isset($_GET['stack'])) {
	$stack = get_option ('bg_forreaders_stack');
	if (isset($stack) && count($stack)){
		$post_id = array_shift($stack);
		update_option('bg_forreaders_stack', $stack);
		$post = get_post($post_id);
		
		if ($post) {
			error_log("stack(left ".count($stack)."): ".date ("j-m-Y H:i"). " ".$post->ID. " ".$post->post_name, 3, $debug_file);
			if ($echo_on) echo "stack(left ".count($stack)."): ".date ("j-m-Y H:i"). " ".$post->ID. " ".$post->post_name;
			$the_time =  microtime(true);
			$bg_forreaders->generate ($post->ID);
			error_log(" - files generated in ".round((microtime(true)-$the_time)*1000, 1)." msec.". PHP_EOL, 3, $debug_file);
			if ($echo_on) {
				echo " - files generated in ".round((microtime(true)-$the_time)*1000, 1)." msec.". PHP_EOL;
				flush();
				ob_flush();
			}
		}
	} else {
		error_log("stack empty". PHP_EOL, 3, $debug_file);
		if ($echo_on) echo "stack empty". PHP_EOL;
		
	}
	
}
error_log("TOTAL TIME: ".round((microtime(true)-$starttime), 1)." sec.". PHP_EOL, 3, $debug_file);
error_log(date ("j-m-Y H:i"). " ===================== Finish the batch mode =====================". PHP_EOL, 3, $debug_file);
if ($echo_on) echo "TOTAL TIME: ".round((microtime(true)-$starttime), 1)." sec.". PHP_EOL;
if ($echo_on) echo date ("j-m-Y H:i"). " ===================== Finish the batch mode =====================". PHP_EOL;

//flock($fp, LOCK_UN); 	// отпираем файл
fclose($fp);			// закрываем его
unlink ('lock');		// и удаляем

exit;

function check_exceptions ($post,  $debug_file, $echo_on) {
	if ($post->post_type == 'post') {
		// Исключения - категории
		$ex_cats = explode ( ',' , get_option('bg_forreaders_excat') );		
		foreach($ex_cats as $cat) {
			if (get_option('bg_forreaders_cats') == 'excluded') {	// если запрещены некоторые категории
				foreach((get_the_category()) as $category) { 
					if (trim($cat) == $category->category_nicename) {
						error_log(" - category (".$category->category_nicename .") banned.". PHP_EOL, 3, $debug_file);
						if ($echo_on) echo " - category (".$category->category_nicename .") banned.". PHP_EOL;
						return true;
					}
				}
			} else {												// если разрешены некоторые категории
				foreach((get_the_category()) as $category) { 
					if (trim($cat) == $category->category_nicename) return false;
				}
				error_log(" - categories not allowed.". PHP_EOL, 3, $debug_file);
				if ($echo_on) echo " - categories not allowed.". PHP_EOL;
				return true;
			}
		}
	} elseif ($post->post_type == 'page') {
		// Исключение - произвольное поле not_for_readers
		$for_readers = get_post_meta($post->ID, 'for_readers', true);
		if (!$for_readers) {
			error_log(" - field 'for_readers' not checked.". PHP_EOL, 3, $debug_file);
			if ($echo_on) echo " - field 'for_readers' not checked.". PHP_EOL;
			return true;
		}
	}
	return false;
}