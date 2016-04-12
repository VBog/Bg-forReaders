<?php
/*
Plugin Name: Bg forReaders
Plugin URI: https://bogaiskov.ru/bg_forreaders
Description: Конвертирует контент страницы в популярные форматы для чтения и выводит на экран форму для скачивания.
Version: 0.9.1
Author: VBog
Author URI:  https://bogaiskov.ru
License:     GPL2
Text Domain: bg_forreaders
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
define( 'BG_FORREADERS_VERSION', '0.9.1' );
define( 'BG_FORREADERS_STORAGE', 'bg_forreaders' );
define( 'BG_FORREADERS_STORAGE_URI', trailingslashit( ABSPATH ) . 'bg_forreaders' );
define( 'BG_FORREADERS_URI', plugin_dir_path( __FILE__ ) );
define( 'BG_FORREADERS_STORAGE_PATH', str_replace ( ABSPATH , '' , BG_FORREADERS_STORAGE_URI  ) );
define( 'BG_FORREADERS_PATH', str_replace ( ABSPATH , '' , BG_FORREADERS_URI ) );

// Для всех форматов
define( 'BG_FORREADERS_CSS', "");
define( 'BG_FORREADERS_TAGS',
"img[src|alt],div[id],blockquote[id],
h1[align|id],h2[align|id],h3[align|id],h4[align|id],h5[align|id],h6[align|id],
hr,p[align|id],br,ol[id],ul[id],li[id],a[href|name|id],
table[id],tr[align],th[id|colspan|rowspan|align],td[id|colspan|rowspan|align],
b,strong,i,em,u,sub,sup,strike,code");

$bg_forreaders_start_time = microtime(true);
$bg_forreaders_debug_file = dirname(__FILE__ )."/forreaders.log";
$formats = array(
	'pdf'  => 'PDF',
	'epub' => 'ePub',
	'mobi' => 'mobi',
	'fb2' => 'fb2'
);

// Активируем параметры плагина, если они не сохранились
bg_forreaders_activate();

// Загрузка интернационализации
add_action( 'plugins_loaded', 'bg_forreaders_load_textdomain' );
function bg_forreaders_load_textdomain() {
  load_plugin_textdomain( 'bg-forreaders', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 
}

// Динамическая таблица стилей для плагина
function bg_forreaders_frontend_styles () {
	wp_enqueue_style( "bg_forreaders_styles", plugins_url( '/css/style.php', plugin_basename(__FILE__) ), array() , BG_FORREADERS_VERSION  );
}
add_action( 'wp_enqueue_scripts' , 'bg_forreaders_frontend_styles' );

// Функция, исполняемая при активации плагина
function bg_forreaders_activate() {
	if (!file_exists(BG_FORREADERS_STORAGE_URI)) @mkdir( BG_FORREADERS_STORAGE_URI );
	if (!file_exists("../".BG_FORREADERS_STORAGE_PATH.'/index.php')) @copy( "../".BG_FORREADERS_PATH.'/download.php', "../".BG_FORREADERS_STORAGE_PATH.'/index.php' );
	bg_forreaders_add_options ();
}
register_activation_hook( __FILE__, 'bg_forreaders_activate' );

// Функция, исполняемая при удалении плагина
function bg_forreaders_uninstall() {
	removeDirectory(BG_FORREADERS_STORAGE_URI);
	bg_forreaders_delete_options();
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

	$bg_forreaders = new BgForReaders();
	
	// Исключения 
	if (!is_object($post)) return $content;		// если не пост
	
	switch ($post->post_type) :
	case 'post' :
		if (get_option('bg_forreaders_single') && !is_single() ) return $content;	// если не одиночная статья (опция)
		$ex_cats = explode ( ',' , get_option('bg_forreaders_excat') );				// если запрещены некоторые категории
		foreach($ex_cats as $cat) {
			if (get_option('bg_forreaders_cats') == 'excluded') {
				foreach((get_the_category()) as $category) { 
					if (trim($cat) == $category->category_nicename) return $content;
				}
			} else {
				foreach((get_the_category()) as $category) { 
					if (trim($cat) == $category->category_nicename) break 2;
				}
				return $content;
			}
		}
	break;
	case 'page' :
		$for_readers_field = get_post_meta($post->ID, 'for_readers', true);
		if (!$for_readers_field) return $content;
	break;
	default:
		return $content;
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
		if (!file_exists(BG_FORREADERS_STORAGE."/".$filename)) $filename = $post->post_name."_".$post->ID.".".$type;
		// Если такового нет, проверяем наличие обычного файла
		if (file_exists(BG_FORREADERS_STORAGE."/".$filename)) {
			if (get_option('bg_forreaders_'.$type) == 'on') {
				$title = sprintf(__('Download &#171;%s&#187; as %s','bg-forreaders'), $post->post_title, $document_type);
				$link_type = get_option('bg_forreaders_links');
				if ($link_type == 'php') $href = trailingslashit( home_url() ).BG_FORREADERS_STORAGE."?file=".$filename;
				else $href = trailingslashit( home_url() ).BG_FORREADERS_STORAGE."/".$filename;
				$download = ($link_type == 'html5')? ' download':'';
				if ($zoom) {
					$forreaders .= sprintf ('<div><a class="%s" href="%s" title="%s"%s></a></div>', $type, $href, $title, $download);
				} else {
					$forreaders .= sprintf ('<span><a href="%s" title="%s"%s>%s</a></span><br>', $href, $title, $download, sprintf(__('Download as %s','bg-forreaders'), $document_type));
				}
			}
		}
	}
	if ($forreaders) {
		$forreaders = get_option('bg_forreaders_prompt').'<div class="bg_forreaders">'.$forreaders.'</div>'.get_option('bg_forreaders_separator');
		$content = (get_option('bg_forreaders_before') ? $forreaders : '') .$content. (get_option('bg_forreaders_after') ? $forreaders : '');
	}
	return $content;
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
		if (get_option('bg_forreaders_while_saved')) {
			$bg_forreaders = new BgForReaders();
			$bg_forreaders->generate ($id);
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
    add_meta_box( 'bg_forreaders_extra_fields', __('For Readers', 'bg-forreaders'), 'bg_forreaders_extra_fields_box_func', 'page', 'side', 'low'  );
}
// Добавление полей
function bg_forreaders_extra_fields_box_func( $post ){
	wp_nonce_field( basename( __FILE__ ), 'bg_forreaders_extra_fields_nonce' );
	$html .= '<label><input type="checkbox" name="bg_forreaders_for_readers"';
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
	add_option('bg_forreaders_author_field', 'post');
	add_option('bg_forreaders_publishing_year', 'post');
	add_option('bg_forreaders_genre', 'genre');
	add_option('bg_forreaders_add_title', 'on');
	add_option('bg_forreaders_add_author', 'on');
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
	add_option('bg_forreaders_memory_limit', '1024');
	add_option('bg_forreaders_time_limit', '60');

	add_option('bg_forreaders_css', BG_FORREADERS_CSS);
	add_option('bg_forreaders_tags', BG_FORREADERS_TAGS);
	add_option('bg_forreaders_extlinks', 'on');
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
	delete_option('bg_forreaders_author_field');
	delete_option('bg_forreaders_publishing_year');
	delete_option('bg_forreaders_genre');
	delete_option('bg_forreaders_add_title');
	delete_option('bg_forreaders_add_author');
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
	delete_option('bg_forreaders_memory_limit');
	delete_option('bg_forreaders_time_limit');

	delete_option('bg_forreaders_css');
	delete_option('bg_forreaders_tags');
	delete_option('bg_forreaders_extlinks');
}
