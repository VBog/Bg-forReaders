<?php
/*
Plugin Name: Bg forReaders
Plugin URI: https://bogaiskov.ru/bg_forreaders
Description: Convert post content to most popular e-book formats for readers and displays a form for download.
Version: 1.1.12
Author: VBog
Author URI:  https://bogaiskov.ru
License:     GPL2
Text Domain: bg-forreaders
Domain Path: /languages
*/
/*  Copyright 2016  Vadim Bogaiskov  (email: vadim.bogaiskov@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
/*****************************************************************************************
	Блок загрузки плагина
	
******************************************************************************************/

// Запрет прямого запуска скрипта
if ( !defined('ABSPATH') ) {
	die( 'Sorry, you are not allowed to access this page directly.' ); 
}
if ( version_compare( PHP_VERSION, '5.3', '<' ) ) {
    add_action( 'admin_notices', 'bg_forreaders_no_activate_notice' );
    add_action( 'admin_init', 'bg_forreaders_deactivate_self' );
    return;
}

function bg_forreaders_no_activate_notice() {
	echo '<div class="error"><p>'.__('Bg forReaders requires PHP 5.3 to function properly. Please upgrade PHP. The Plugin has been auto-deactivated.', 'bg-forreaders') .'</p></div>'; 
	if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );
}
function bg_forreaders_deactivate_self() {
	deactivate_plugins( plugin_basename( __FILE__ ) );
}

define( 'BG_FORREADERS_VERSION', '1.1.12' );
$upload_dir = wp_upload_dir();
define( 'BG_FORREADERS_URI', plugin_dir_path( __FILE__ ) );
define( 'BG_FORREADERS_PATH', str_replace ( ABSPATH , '' , BG_FORREADERS_URI ) );
define( 'BG_FORREADERS_STORAGE', 'bg_forreaders' );
define( 'BG_FORREADERS_STORAGE_URL', $upload_dir['baseurl'] ."/". BG_FORREADERS_STORAGE );
define( 'BG_FORREADERS_STORAGE_URI', $upload_dir['basedir'] ."/". BG_FORREADERS_STORAGE );
define( 'BG_FORREADERS_STORAGE_PATH', str_replace ( ABSPATH , '' , BG_FORREADERS_STORAGE_URI  ) );
define( 'BG_FORREADERS_TMP_COVER', BG_FORREADERS_STORAGE_URI."/".'tmp_cover.png' );

// Для всех форматов
define( 'BG_FORREADERS_CSS', "");
define( 'BG_FORREADERS_TAGS',
"img[src|alt],div[id],blockquote[id],
h1[align|id],h2[align|id],h3[align|id],h4[align|id],h5[align|id],h6[align|id],
hr,p[align|id],br,ol[id],ul[id],li[id],a[href|name|id],
table[id],tr[align],th[id|colspan|rowspan|align],td[id|colspan|rowspan|align],
b,strong,i,em,u,sub,sup,strike,code");

define( 'BG_FORREADERS_DEBUG_FILE', dirname(__FILE__ )."/forreaders.log");
define( 'BG_FORREADERS_DEBUG_OLD', dirname(__FILE__ )."/forreaders.old");

$bg_forreaders_start_time = microtime(true);
$formats = array(
	'pdf'  => 'PDF',
	'epub' => 'ePub',
	'mobi' => 'mobi',
	'fb2' => 'fb2'
);
// Функция, исполняемая при активации плагина
function bg_forreaders_activate() {
	if (!file_exists(BG_FORREADERS_STORAGE_URI)) @mkdir( BG_FORREADERS_STORAGE_URI );
	if (!file_exists(BG_FORREADERS_STORAGE_URI.'/index.php')) @copy( BG_FORREADERS_URI.'/css/download', BG_FORREADERS_STORAGE_URI.'/index.php' );
	if (!file_exists(BG_FORREADERS_STORAGE_URI.'/document-pdf.png')) @copy( BG_FORREADERS_URI.'/css/document-pdf.png', BG_FORREADERS_STORAGE_URI.'/document-pdf.png' );
	if (!file_exists(BG_FORREADERS_STORAGE_URI.'/document-epub.png')) @copy( BG_FORREADERS_URI.'/css/document-epub.png', BG_FORREADERS_STORAGE_URI.'/document-epub.png' );
	if (!file_exists(BG_FORREADERS_STORAGE_URI.'/document-mobi.png')) @copy( BG_FORREADERS_URI.'/css/document-mobi.png', BG_FORREADERS_STORAGE_URI.'/document-mobi.png' );
	if (!file_exists(BG_FORREADERS_STORAGE_URI.'/document-fb2.png')) @copy( BG_FORREADERS_URI.'/css/document-fb2.png', BG_FORREADERS_STORAGE_URI.'/document-fb2.png' );
	bg_forreaders_add_options ();
}
register_activation_hook( __FILE__, 'bg_forreaders_activate' );

// Загрузка интернационализации
add_action( 'plugins_loaded', 'bg_forreaders_load_textdomain' );
function bg_forreaders_load_textdomain() {
  load_plugin_textdomain( 'bg-forreaders', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 
}

// Динамическая таблица стилей для плагина
function bg_forreaders_frontend_styles () {
	wp_enqueue_style( "bg_forreaders_styles", plugins_url( "/css/style.css", plugin_basename(__FILE__) ), array() , BG_FORREADERS_VERSION  );
	$bg_forreaders = BG_FORREADERS_STORAGE_URL.'/';
	$zoom=(float) get_option('bg_forreaders_zoom');
	$custom_css = "
div.bg_forreaders {"
	.(($zoom)?("height: ".(88*$zoom)."px;"):"")."
	font-size: ".($zoom?0:1)."em;
}
.bg_forreaders div a {
	padding: 0px ".(69*$zoom)."px ".(88*$zoom)."px 0px;
	margin: 0px ".(10*$zoom)."px 0px 0px;
}
.bg_forreaders .pdf {
	background: url(".$bg_forreaders."document-pdf.png) no-repeat 50% 50%;
	background-size: contain;
}
.bg_forreaders .epub {
	background: url(".$bg_forreaders."document-epub.png) no-repeat 50% 50%;
	background-size: contain;
}

.bg_forreaders .mobi{
	background: url(". $bg_forreaders."document-mobi.png) no-repeat 50% 50%;
	background-size: contain;
}
.bg_forreaders .fb2 {
	background: url(".$bg_forreaders."document-fb2.png) no-repeat 50% 50%;
	background-size: contain;
}
	";
	wp_add_inline_style( 'bg_forreaders_styles', $custom_css );
}
add_action( 'wp_enqueue_scripts' , 'bg_forreaders_frontend_styles' );

// Функция, исполняемая при удалении плагина
function bg_forreaders_uninstall() {
	removeDirectory(BG_FORREADERS_STORAGE_URI);
	bg_forreaders_delete_options();
	bg_forreaders_unschedule();
	remove_filter( 'cron_schedules', 'bg_forreaders_add_cron_schedule' );
}
function removeDirectory($dir) {
	if ($objs = glob($dir."/*")) {
		foreach($objs as $obj) {
			is_dir($obj) ? removeDirectory($obj) : unlink($obj);
		}
	}
	rmdir($dir);
}

register_uninstall_hook(__FILE__, 'bg_forreaders_uninstall');

// Подключаем дополнительные модули
include_once('includes/main_class.php' );
include_once('includes/settings.php' );

if ( defined('ABSPATH') && defined('WPINC') ) {
// Регистрируем крючок для обработки контента при его загрузке
	add_filter( 'the_content', 'bg_forreaders_proc' );
}

/*****************************************************************************************
	Функции запуска плагина
	
******************************************************************************************/
 
// Функция вставки блока загрузки файлов для чтения
function bg_forreaders_proc($content) {
	global $post, $formats;
	
	$forreaders = bg_forreaders ($post);
	
	if ($forreaders) 
		$content = (get_option('bg_forreaders_before') ? $forreaders : '') .$content. (get_option('bg_forreaders_after') ? $forreaders : '');
	
	return $content;
}
// Функция формирования блока загрузки файлов для чтения
function bg_forreaders ($post) {
	global $formats;

	$bg_forreaders = new BgForReaders();
	
	// Исключения 
	if (!is_object($post)) return "";		// если не пост
	
	switch ($post->post_type) :
	case 'post' :
		if (get_option('bg_forreaders_single') && !is_single() ) return "";		// если не одиночная статья (опция)
		$ex_cats = explode ( ',' , get_option('bg_forreaders_excat') );			// если запрещены некоторые категории
		foreach($ex_cats as $cat) {
			if (get_option('bg_forreaders_cats') == 'excluded') {
				foreach((get_the_category()) as $category) { 
					if (trim($cat) == $category->category_nicename) return "";
				}
			} else {
				foreach((get_the_category()) as $category) { 
					if (trim($cat) == $category->category_nicename) break 2;
				}
				return "";
			}
		}
		// Создаем во всех уже опубликованных постах произвольное поле 'for_readers' 
		// (если оно еще не существует) со значением по умолчанию
		add_post_meta($post->ID, 'for_readers', (get_option('bg_forreaders_type_post')=='on'), true );
		// Теперь поле наверняка существует - проверяем его значение
		$for_readers_field = get_post_meta($post->ID, 'for_readers', true);
		if (!$for_readers_field) return "";
	break;
	case 'page' :
		// Создаем во всех уже опубликованных постах произвольное поле 'for_readers' 
		// (если оно еще не существует) со значением по умолчанию
		add_post_meta($post->ID, 'for_readers', (get_option('bg_forreaders_type_page')=='on'), true );
		// Теперь поле наверняка существует - проверяем его значение
		$for_readers_field = get_post_meta($post->ID, 'for_readers', true);
		if (!$for_readers_field) return "";
	break;
	default:
		return "";
	endswitch;
	// Генерация файлов для чтения при открытии страницы, если они отсутствуют
	if (get_option('bg_forreaders_while_displayed')) {
		$bg_forreaders->generate ($post->ID);
	}
	
	$zoom = get_option('bg_forreaders_zoom');
	$forreaders = "";
	foreach ($formats as $type => $document_type) {
		// Сначала проверяем наличие защищенного файла
		$filename = $post->post_name."_".$post->ID."p.".$type;
		if (!file_exists(BG_FORREADERS_STORAGE_PATH."/".$filename)) $filename = $post->post_name."_".$post->ID.".".$type;
		// Если такового нет, проверяем наличие обычного файла
		if (file_exists(BG_FORREADERS_STORAGE_PATH."/".$filename)) {
			if (get_option('bg_forreaders_'.$type) == 'on') {
				$title = sprintf(__('Download &#171;%s&#187; as %s','bg-forreaders'), $post->post_title, $document_type);
				$link_type = get_option('bg_forreaders_links');
				if ($link_type == 'php') $href = BG_FORREADERS_STORAGE_URL."?file=".$filename;
				else $href = BG_FORREADERS_STORAGE_URL."/".$filename;
				$download = ($link_type == 'html5')? ' download':'';
				if ($zoom) {
					$forreaders .= sprintf ('<div><a class="%s" href="%s" title="%s"%s></a></div>', $type, $href, $title, $download);
				} else {
				$forreaders .= sprintf ('<span><a href="%s" title="%s"%s>%s</a></span><br>', $href, $title, $download, sprintf(__('Download as %s','bg-forreaders'), $document_type));
				}
			}
		}
	}
	if ($forreaders) 
		$forreaders = get_option('bg_forreaders_prompt').'<div class="bg_forreaders">'.$forreaders.'</div>'.get_option('bg_forreaders_separator');
	
	return $forreaders;
}

// Функция генерации файлов для чтения при сохранении поста
function bg_forreaders_save( $id ) {
	global $formats;

	$post = get_post($id);
	if( isset($post) && ($post->post_type == 'post' || $post->post_type == 'page') ) { 			// убедимся что мы редактируем нужный тип поста
		switch (get_current_screen()->id) :										// убедимся что мы на нужной странице админки
		case 'post' :
			$ex_cats = explode ( ',' , get_option('bg_forreaders_excat') );		// если запрещены некоторые категории
			foreach($ex_cats as $cat) {
				if (get_option('bg_forreaders_cats') == 'excluded') {
					foreach((get_the_category()) as $category) { 
						if (trim($cat) == $category->category_nicename) return;
					}
				} else {
					foreach((get_the_category()) as $category) { 
						if (trim($cat) == $category->category_nicename) break 2;
					}
					return;
				}
			}
			$for_readers_field = get_post_meta($post->ID, 'for_readers', true);
			if (!$for_readers_field) return;
		break;
		case 'page' :
			$for_readers_field = get_post_meta($post->ID, 'for_readers', true);
			if (!$for_readers_field) return;
		break;
		default:
			return;
		endswitch;
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE  ) return; 			// пропустим если это автосохранение
		if ( ! current_user_can('edit_post', $id ) ) return; 					// убедимся что пользователь может редактировать запись
	
	// 	Генерация файлов для чтения
		if (get_option('bg_forreaders_while_saved')) {				// Сразу
			$bg_forreaders = new BgForReaders();
			$bg_forreaders->generate ($id);
		} elseif (get_option('bg_forreaders_offline_query')) {		// Ставим в очередь
			$stack = get_option ('bg_forreaders_stack');
			$stack[] = $id;
			$stack = array_unique ($stack);
			update_option('bg_forreaders_stack', $stack);
		}
	}
}
add_action( 'save_post', 'bg_forreaders_save', 10 );
//add_action('wp_insert_post_data', 'bg_forreaders_save', 20, 2 );

// Hook for adding admin menus
if ( is_admin() ){ 				// admin actions
	add_action('admin_menu', 'bg_forreaders_add_pages');
}
// action function for above hook
function  bg_forreaders_add_pages() {
    // Add a new submenu under Options:
    add_options_page(__('Plugin\'s &#171;For Readers&#187; settings', 'bg-forreaders'), __('For Readers', 'bg-forreaders'), 'manage_options', __FILE__, 'bg_forreaders_options_page');
}

// Версия плагина
function bg_forreaders_version() {
	$plugin_data = get_plugin_data( __FILE__  );
	return $plugin_data['Version'];
}

/*****************************************************************************************
	Добавляем блок в боковую колонку на страницах редактирования страниц
	
******************************************************************************************/
add_action('admin_init', 'bg_forreaders_extra_fields', 1);
// Создание блока
function bg_forreaders_extra_fields() {
    add_meta_box( 'bg_forreaders_extra_fields', __('For Readers', 'bg-forreaders'), 'bg_forreaders_extra_fields_box_func', array('post', 'page'), 'side', 'low'  );
}
// Добавление полей
function bg_forreaders_extra_fields_box_func( $post ){
	wp_nonce_field( basename( __FILE__ ), 'bg_forreaders_extra_fields_nonce' );
	if ($post->post_type == 'page') $meta_value = (get_option ('bg_forreaders_type_page')== 'on');
	elseif ($post->post_type == 'post') $meta_value = (get_option ('bg_forreaders_type_post')== 'on');
	else $meta_value = false;
	add_post_meta($post->ID, 'for_readers', $meta_value, true );
	$html = '<label><input type="checkbox" name="bg_forreaders_for_readers"';
	$html .= (get_post_meta($post->ID, 'for_readers',true)) ? ' checked="checked"' : '';
	$html .= ' /> '.__('create files for readers', 'bg-forreaders').'</label>';
 
	echo $html;
}
// Сохранение значений произвольных полей при сохранении поста
add_action('save_post', 'bg_forreaders_extra_fields_update', 0);

// Сохранение значений произвольных полей при сохранении поста
function bg_forreaders_extra_fields_update( $post_id ){

	// проверяем, пришёл ли запрос со страницы с метабоксом
	if ( !isset( $_POST['bg_forreaders_extra_fields_nonce'] )
	|| !wp_verify_nonce( $_POST['bg_forreaders_extra_fields_nonce'], basename( __FILE__ ) ) ) return $post_id;
	// проверяем, является ли запрос автосохранением
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $post_id;
	// проверяем, права пользователя, может ли он редактировать записи
	if ( !current_user_can( 'edit_post', $post_id ) ) return $post_id;
	update_post_meta($post_id, 'for_readers', $_POST['bg_forreaders_for_readers']);
}

/*****************************************************************************************
	Запуск WP-CRON
	
******************************************************************************************/
// Устанавливаем дополнительные интервалы (Стандарт: 'hourly', 'twicedaily', and 'daily').
add_filter( 'cron_schedules', 'bg_forreaders_add_cron_schedule' );
function bg_forreaders_add_cron_schedule( $schedules ) {
	$schedules['never'] = array(
		'interval' => false, // никогда 
		'display'  => __( 'Never', 'bg-forreaders' ),
	);
	$schedules['once'] = array(
		'interval' => 0, // однажды 
		'display'  => __( 'Once', 'bg-forreaders' ),
	);
	$schedules['minutely'] = array(
		'interval' => 60, // каждую минуту
		'display'  => __( 'Minutely', 'bg-forreaders' ),
	);
	$schedules['every5min'] = array(
		'interval' => 300, // каждые 5 минут
		'display'  => __( 'Every 5 minutes', 'bg-forreaders' ),
	);
	$schedules['every15min'] = array(
		'interval' => 900, // каждые 15 минут
		'display'  => __( 'Every 15 minutes', 'bg-forreaders' ),
	);
	$schedules['twicehourly'] = array(
		'interval' => 1800, // каждые 30 минут
		'display'  => __( 'Twice Hourly', 'bg-forreaders' ),
	);
	$schedules['every3hour'] = array(
		'interval' => 10800, // каждые 3 часа 
		'display'  => __( 'Every 3 hours', 'bg-forreaders' ),
	);
 	$schedules['every6hour'] = array(
		'interval' => 21600, // каждые 6 часов  
		'display'  => __( 'Every 6 hours', 'bg-forreaders' ),
	);
 	$schedules['weekly'] = array(
		'interval' => 604800, // каждые 6 часов  
		'display'  => __( 'Weekly', 'bg-forreaders' ),
	);
  
	return $schedules;
}

// Если обновлены настройки WP Cron, сбросить все расписания
if (get_option('bg_forreaders_cron_updated') == 'update') {
	bg_forreaders_unschedule();
	update_option('bg_forreaders_cron_updated', '');	// Все расписания сброшены
}

// Если включена обработка через стек
if(get_option('bg_forreaders_offline_query')) {
	// Обрабатываем содержимое стека каждый заданный интервал времени
	if ( !wp_next_scheduled( 'bg_forreaders_stack_cron_action' ) ) {
		if (get_option ('bg_forreaders_stack_interval') == 'once') {
			wp_schedule_single_event(time(), 'bg_forreaders_stack_cron_action');
			update_option ('bg_forreaders_stack_interval', 'never');
		}
		elseif (get_option ('bg_forreaders_stack_interval') != 'never') wp_schedule_event( time(), get_option ('bg_forreaders_stack_interval'), 'bg_forreaders_stack_cron_action' );
	}
	add_action( 'bg_forreaders_stack_cron_action', 'bg_forreaders_create_from_stack' );
} else {
	if( false !== ( $time = wp_next_scheduled( 'bg_forreaders_stack_cron_action' ) ) ) { 
		wp_unschedule_event( $time, 'bg_forreaders_stack_cron_action' ); 
	}
}

// Обработка всех разрешенных постов
if ( !wp_next_scheduled( 'bg_forreaders_all_cron_action' ) ) {
	$time = strtotime( date('j-m-Y ').get_option('bg_forreaders_all_checktime') );
	if ($time < time() ) $time += 60*60*24;
	if (get_option ('bg_forreaders_all_interval') == 'once') {
		wp_schedule_single_event($time, 'bg_forreaders_all_cron_action');
		update_option ('bg_forreaders_all_interval', 'never');
	}
	elseif (get_option ('bg_forreaders_all_interval') != 'never') wp_schedule_event( $time, get_option ('bg_forreaders_all_interval'), 'bg_forreaders_all_cron_action' );
}
add_action( 'bg_forreaders_all_cron_action', 'bg_forreaders_create_all' );

// Обновляем журнал каждый заданный интервал времени
if ( !wp_next_scheduled( 'bg_forreaders_log_cron_action' ) ) {
	$time = strtotime( date('j-m-Y ').get_option('bg_forreaders_log_checktime') );
	if ($time < time() ) $time += 60*60*24;
	if (get_option ('bg_forreaders_log_interval') == 'once') {
		wp_schedule_single_event($time, 'bg_forreaders_log_cron_action');
		update_option ('bg_forreaders_log_interval', 'never');
	}
	elseif (get_option ('bg_forreaders_log_interval') != 'never') wp_schedule_event( $time, get_option ('bg_forreaders_log_interval'), 'bg_forreaders_log_cron_action' );
}
add_action( 'bg_forreaders_log_cron_action', 'bg_forreaders_update_debug_file' );

// Сброс всех расписаний
function bg_forreaders_unschedule() {
	if( false !== ( $time = wp_next_scheduled( 'bg_forreaders_stack_cron_action' ) ) ) { 
		wp_unschedule_event( $time, 'bg_forreaders_stack_cron_action' ); 
	}
	if( false !== ( $time = wp_next_scheduled( 'bg_forreaders_all_cron_action' ) ) ) { 
		wp_unschedule_event( $time, 'bg_forreaders_all_cron_action' ); 
	}
	if( false !== ( $time = wp_next_scheduled( 'bg_forreaders_log_cron_action' ) ) ) { 
		wp_unschedule_event( $time, 'bg_forreaders_log_cron_action' ); 
	}
}

/*****************************************************************************************
	Функции пакетной обработки
	
******************************************************************************************/
// Функция обрабатывает пост из стека
function bg_forreaders_create_from_stack() {
	$errors = array (
		'1' => 'category banned',
		'2' => 'categories not allowed',
		'3' => 'field "for_readers" not checked'
	);
	$bg_forreaders = new BgForReaders();
	
	$stack = get_option ('bg_forreaders_stack');
	if (isset($stack) && count($stack)){
		$post_id = array_shift($stack);
		update_option('bg_forreaders_stack', $stack);
		$post = get_post($post_id);
		if ($post) {
			if (false === ($err = bg_forreaders_check_exceptions ($post))) {
				error_log( PHP_EOL . "Stack(".(count($stack)+1)."): ".date ("j-m-Y H:i"). " ".$post->ID. " ".$post->post_name, 3, BG_FORREADERS_DEBUG_FILE);
				$the_time =  microtime(true);
				$bg_forreaders->generate ($post->ID);
				error_log(" - files generated in ".round((microtime(true)-$the_time)*1000, 1)." msec.", 3, BG_FORREADERS_DEBUG_FILE);
			} else error_log( PHP_EOL . "Stack(".(count($stack)+1)."): ".date ("j-m-Y H:i"). " ".$post->ID. " ".$post->post_name." - ". $errors[$err], 3, BG_FORREADERS_DEBUG_FILE);
		}
	}
}

// Функция обрабатывает все посты в пакетном режиме	
function bg_forreaders_create_all($start=0, $finish=false) {
	$errors = array (
		'1' => 'category banned',
		'2' => 'categories not allowed',
		'3' => 'field "for_readers" not checked'
	);
	error_log( PHP_EOL . date ("j-m-Y H:i"). " ===================== Start the batch mode =====================", 3, BG_FORREADERS_DEBUG_FILE);
	$bg_forreaders = new BgForReaders();
	$starttime =  microtime(true);
	$cnt = wp_count_posts('post')->publish + wp_count_posts('page')->publish;
	if(!$finish) $finish = $cnt;
	error_log( PHP_EOL . " All posts (".$cnt."): Start=".$start.", Finish=".$finish, 3, BG_FORREADERS_DEBUG_FILE);
	for ($i = 0; $i < $cnt; $i++){
		if ($i < $start-1) continue;
		if ($i > $finish-1) break;
		$args = array('post_type' => array( 'post', 'page'), 'post_status' => 'publish', 'numberposts' => 1, 'offset' => $i, 'orderby' => 'ID');
		$posts_array = get_posts($args);
		$post = $posts_array[0];
		error_log( PHP_EOL . ($i+1).". ".date ("j-m-Y H:i"). " ".$post->ID. " ".$post->post_name. "  (".$post->post_type. ") ", 3, BG_FORREADERS_DEBUG_FILE);

		if (false === ($err = bg_forreaders_check_exceptions ($post))) {
			$the_time =  microtime(true);
			$bg_forreaders->generate ($post->ID);
			error_log(" - files generated in ".round((microtime(true)-$the_time)*1000, 1)." msec.", 3, BG_FORREADERS_DEBUG_FILE);
		} else error_log( " - ". $errors[$err], 3, BG_FORREADERS_DEBUG_FILE);
	}
	error_log( PHP_EOL . "TOTAL TIME: ".round((microtime(true)-$starttime), 1)." sec.", 3, BG_FORREADERS_DEBUG_FILE);
	error_log( PHP_EOL . date ("j-m-Y H:i"). " ===================== Finish the batch mode =====================", 3, BG_FORREADERS_DEBUG_FILE);
} 

// Функция проверяет исключения
// Возвращает false - если нет исключения,
// или код ошибки:
//    1 - category banned
//    2 - categories not allowed
//    3 - field 'for_readers' not checked
function bg_forreaders_check_exceptions ($post) {
	if ($post->post_type == 'post') {
		// Исключения - категории
		$ex_cats = explode ( ',' , get_option('bg_forreaders_excat') );		
		foreach($ex_cats as $cat) {
			if (get_option('bg_forreaders_cats') == 'excluded') {	// если запрещены некоторые категории
				foreach((get_the_category($post->ID)) as $category) { 
					if (trim($cat) == $category->category_nicename) return 1;
				}
			} else {												// если разрешены некоторые категории
				foreach((get_the_category($post->ID)) as $category) { 
					if (trim($cat) == $category->category_nicename) return false;
				}
				return 2;
			}
		}
		// Исключение - произвольное поле not_for_readers
		$for_readers = get_post_meta($post->ID, 'for_readers', true);
		if (!$for_readers) return 3;
	} elseif ($post->post_type == 'page') {
		// Исключение - произвольное поле not_for_readers
		$for_readers = get_post_meta($post->ID, 'for_readers', true);
		if (!$for_readers) return 3;
	}
	return false;
}

// Функция обновляет файл журнала
function bg_forreaders_update_debug_file() {
	if (file_exists (BG_FORREADERS_DEBUG_FILE) ) {
		@unlink (BG_FORREADERS_DEBUG_OLD);								// Удаляем старый лог
		@rename ( BG_FORREADERS_DEBUG_FILE, BG_FORREADERS_DEBUG_OLD );	// Переименовываем текущий лог в старый
	}
}

/*****************************************************************************************
	Параметры плагина
	
******************************************************************************************/
function bg_forreaders_add_options (){

	delete_option('bg_forreaders_while_starttime');
	add_option('bg_forreaders_pdf', 'on');
	add_option('bg_forreaders_epub', 'on');
	add_option('bg_forreaders_mobi', 'on');
	add_option('bg_forreaders_fb2', 'on');
	add_option('bg_forreaders_links', 'php');
	add_option('bg_forreaders_before', 'on');
	add_option('bg_forreaders_after', '');
	add_option('bg_forreaders_prompt', '');
	add_option('bg_forreaders_separator', '');
	add_option('bg_forreaders_zoom', '1');
	add_option('bg_forreaders_single', 'on');
	add_option('bg_forreaders_cats', 'excluded');
	add_option('bg_forreaders_excat', '');
	add_option('bg_forreaders_type_page', '');
	add_option('bg_forreaders_type_post', 'on');
	add_option('bg_forreaders_author_field', 'post');
	add_option('bg_forreaders_publishing_year', 'post');
	add_option('bg_forreaders_genre', 'genre');
	add_option('bg_forreaders_add_title', 'on');
	add_option('bg_forreaders_add_author', 'on');
	add_option('bg_forreaders_cover_title', 'on');
	add_option('bg_forreaders_cover_author', 'on');
	add_option('bg_forreaders_cover_site', 'on');
	add_option('bg_forreaders_cover_year', 'on');
	add_option('bg_forreaders_cover_thumb', 'on');
	add_option('bg_forreaders_cover_image', '');
	add_option('bg_forreaders_text_color', '#000000');
	add_option('bg_forreaders_bg_color', '#ffffff');
	add_option('bg_forreaders_left_offset', '140');
	add_option('bg_forreaders_right_offset', '100');
	add_option('bg_forreaders_top_offset', '200');
	add_option('bg_forreaders_bottom_offset', '80');
	add_option('bg_forreaders_while_displayed', '');
	add_option('bg_forreaders_while_saved', 'on');
	add_option('bg_forreaders_offline_query', '');
	add_option('bg_forreaders_memory_limit', '1024');
	add_option('bg_forreaders_time_limit', '900');

	add_option('bg_forreaders_cron_updated', '');	// Все расписания сброшены
	add_option('bg_forreaders_stack_interval', 'every5min');
	add_option('bg_forreaders_all_interval', 'never');
	add_option('bg_forreaders_all_checktime', '00:00');
	add_option('bg_forreaders_log_interval', 'daily');
	add_option('bg_forreaders_log_checktime', '00:00');
	
	add_option('bg_forreaders_css', BG_FORREADERS_CSS);
	add_option('bg_forreaders_tags', BG_FORREADERS_TAGS);
	add_option('bg_forreaders_extlinks', 'on');
	
	add_option('bg_forreaders_stack', array());

}
function bg_forreaders_delete_options (){

	delete_option('bg_forreaders_pdf');
	delete_option('bg_forreaders_epub');
	delete_option('bg_forreaders_mobi');
	delete_option('bg_forreaders_fb2');
	delete_option('bg_forreaders_links');
	delete_option('bg_forreaders_before');
	delete_option('bg_forreaders_after');
	delete_option('bg_forreaders_prompt');
	delete_option('bg_forreaders_separator');
	delete_option('bg_forreaders_zoom');
	delete_option('bg_forreaders_single');
	delete_option('bg_forreaders_cats');
	delete_option('bg_forreaders_excat');
	delete_option('bg_forreaders_type_page');
	delete_option('bg_forreaders_type_post');
	delete_option('bg_forreaders_author_field');
	delete_option('bg_forreaders_publishing_year');
	delete_option('bg_forreaders_genre');
	delete_option('bg_forreaders_add_title');
	delete_option('bg_forreaders_add_author');
	delete_option('bg_forreaders_cover_title');
	delete_option('bg_forreaders_cover_author');
	delete_option('bg_forreaders_cover_site');
	delete_option('bg_forreaders_cover_year');
	delete_option('bg_forreaders_cover_thumb');
	delete_option('bg_forreaders_cover_image');
	delete_option('bg_forreaders_text_color');
	delete_option('bg_forreaders_bg_color');
	delete_option('bg_forreaders_left_offset');
	delete_option('bg_forreaders_right_offset');
	delete_option('bg_forreaders_top_offset');
	delete_option('bg_forreaders_bottom_offset');
	delete_option('bg_forreaders_while_displayed');
	delete_option('bg_forreaders_while_saved');
	delete_option('bg_forreaders_offline_query');
	delete_option('bg_forreaders_memory_limit');
	delete_option('bg_forreaders_time_limit');

	delete_option('bg_forreaders_cron_updated');
	delete_option('bg_forreaders_stack_interval');
	delete_option('bg_forreaders_all_interval');
	delete_option('bg_forreaders_all_checktime');
	delete_option('bg_forreaders_log_interval');
	delete_option('bg_forreaders_log_checktime');
	
	delete_option('bg_forreaders_css');
	delete_option('bg_forreaders_tags');
	delete_option('bg_forreaders_extlinks');
	
	delete_option('bg_forreaders_stack');
}
