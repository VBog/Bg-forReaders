<?php
/*
Plugin Name: Bg forReaders
Plugin URI: https://bogaiskov.ru/bg_forreaders
Description: Конвертирует контент страницы в популярные форматы для чтения и выводит на экран форму для скачивания.
Version: 0.5
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
define( 'BG_FORREADERS_VERSION', '0.5' );
define( 'BG_FORREADERS_STORAGE', 'bg_forreaders' );
define( 'BG_FORREADERS_STORAGE_URI', trailingslashit( ABSPATH ) . 'bg_forreaders' );
define( 'BG_FORREADERS_URI', plugin_dir_path( __FILE__ ) );
define( 'BG_FORREADERS_STORAGE_PATH', str_replace ( ABSPATH , '' , BG_FORREADERS_STORAGE_URI  ) );
define( 'BG_FORREADERS_PATH', str_replace ( ABSPATH , '' , BG_FORREADERS_URI ) );

define( 'BG_FORREADERS_ALLOWED_TAGS',
"div,
h1[align],
h2[align],
h3[align],
h4[align],
h5[align],
h6[align],
p[align],
br,
ol,
table,
a[href|name|id],
b,
strong,
i,
em,
u,
sub,
sup");

$bg_forreaders_start_time = microtime(true);
$bg_forreaders_debug_file = dirname(__FILE__ )."/forreaders.log";
$formats = array(
	'pdf'  => 'PDF',
	'epub' => 'ePub',
	'mobi' => 'mobi',
	'fb2' => 'fb2'
);

// Задаем начальные значения параметров
bg_forreaders_add_options ();

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
	if (!is_object($post)) return $content;										// если не пост
	if (get_option('bg_forreaders_single') && !is_single() ) return $content;	// если не одиночная статья (опция)
	$ex_cats = explode ( ',' , get_option('bg_forreaders_excat') );				// если запрещены некоторые категории
	foreach($ex_cats as $cat) {
		foreach((get_the_category()) as $category) { 
			if (trim($cat) == $category->category_nicename) return $content;
		}
	}

	// Генерация файлов для чтения при открытии страницы, если они отсутствуют
	if (get_option('bg_forreaders_while_displayed')) {
		$bg_forreaders->generate ($post->ID);
	}
	
	$zoom = get_option('bg_forreaders_zoom');
	$forreaders = get_option('bg_forreaders_prompt');
	$forreaders .= '<div class="bg_forreaders">';
	foreach ($formats as $type => $document_type) {
		if (get_option('bg_forreaders_'.$type) == 'on') {
			$title = sprintf(__('Download &#171;%s&#187; as %s','bg-forreaders'), $post->post_title, $document_type);
			if (get_option('bg_forreaders_links') == 'php') $href = trailingslashit( home_url() ).BG_FORREADERS_STORAGE."?file=".$post->post_name.".".$type;
			else $href = trailingslashit( home_url() ).BG_FORREADERS_STORAGE."/".$post->post_name.".".$type;
			if ($zoom) {
				$forreaders .= sprintf ('<div><a class="%s" href="%s" title="%s" download></a></div>', $type, $href, $title);
			} else {
				$forreaders .= sprintf ('<span><a href="%s" title="%s" download>%s</a></span><br>', $href, $title, sprintf(__('Download as %s','bg-forreaders'), $document_type));
			}
		}
	}
	$forreaders .= '</div>';
	
	$content = (get_option('bg_forreaders_before') ? $forreaders : '') .$content. (get_option('bg_forreaders_after') ? $forreaders : '');
	return $content;
}

// Функция генерации файлов для чтения при сохранении поста
function bg_forreaders_save( $id ) {
	global $formats;

	$post = get_post($id);
	if( isset($post) && ($post->post_type == 'post' || $post->post_type == 'page') ) { 		// убедимся что мы редактируем нужный тип поста
		if( get_current_screen()->id != 'post' && get_current_screen()->id != 'post') return; 	// убедимся что мы на нужной странице админки
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE  ) return; 							// пропустим если это автосохранение
		if ( ! current_user_can('edit_post', $id ) ) return; 									// убедимся что пользователь может редактировать запись
	
	// 	Удаление старых версий файлов для чтения
		$filename = BG_FORREADERS_STORAGE_URI."/".$post->post_name;
		foreach ($formats as $type => $document_type) {
			if (file_exists ($filename.".".$type)) unlink ($filename.".".$type);
		}
		if (file_exists ($filename.".html")) unlink ($filename.".html");
	// 	Генерация файлов для чтения
		if (get_option('bg_forreaders_while_saved')) {
			$bg_forreaders = new BgForReaders();
			$bg_forreaders->generate ($id);
		}
	}
}
add_action( 'save_post', 'bg_forreaders_save' );
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
	Класс плагина
	
******************************************************************************************/
class BgForReaders
{
// Подготовка контента к публикации
	public function prepare ($content) {

		require_once "lib/BgClearHTML.php";
		$сhtml = new BgClearHTML();
		
		// Массив разрешенных тегов и атрибутов
		$allow_attributes = array ();
		$allow_attributes = $сhtml->strtoarray (get_option('bg_forreaders_allowed_tags'));

		// Выполнить все шорт-коды
		$content = do_shortcode ( $content );

		// Оставляем в тексте только разрешенные теги и итрибуты
		$content = $сhtml->prepare ($content, $allow_attributes);

		// Дополнительная обработка тега <a>
//		$content = preg_replace('/(<a.*?)id=(.*?\>)/is', "\\1name=\\2", $content);

		// Заменяем <br> на <br /> 	
		$content = str_replace('<br>', '<br />', $content);
		
		// Исправляем неправильно-введенные XHTML (HTML) теги
		$content = balanceTags( $content, true );	
		
		// Делаем текст кода читабельным 
		$content = $сhtml->addEOL ($content);

		return $content;
	}

// Portable Document Format (PDF)
	public function topdf ($html, $options) {

		ini_set("pcre.backtrack_limit","3000000");
		ini_set("memory_limit", "256M");
		require_once "lib/mpdf60/mpdf.php";
		$filepdf = $options["filename"] . '.pdf';
		
		$pdf = new mPDF();
		$pdf->SetTitle($options["title"]);
		$pdf->SetAuthor($options["author"]);
		//$pdf->showImageErrors = true;
		$cssData = file_get_contents(dirname(__FILE__).'/css/pdf.css'); /*подключаем css*/
		if ($cssData != "") {
			$pdf->WriteHTML($cssData,1);	// The parameter 1 tells that this is css/style only and no body/html/text
		}
		$pdf->WriteHTML($html);
		$pdf->Output($filepdf, 'F');
		return;
	}
// Electronic Publication (ePub)
	public function toepub ($html, $options) {
		
		require_once "lib/PHPePub/EPub.php";
		$title = $options["title"];
		$author = $options["author"];
		$fileepub = $options["filename"] . '.epub';
		$guid = $options["guid"];
		$sourceURL = $options["url"];
		
		$epub = new EPub();
		$html = preg_replace("/\<img.*?\>/is", "", $html);
		$html = preg_replace("/\<hr.*?\>/is", "", $html);
		$html = preg_replace("/align\s?=\s?\"?(center|left|right|justify)\"?/is", "", $html);
		//$html = preg_replace('#\<a\s*\t*\r?\n?href=\"[^\#](.*?)\".*?\>(.*?)\<\/a\>#si', "<a l:href=\"$1\">$2</a>", $html);
		$epub->setGenerator('DrUUID RFC4122 library for PHP5 by J. King (http://jkingweb.ca/)');
		$epub->setTitle($title); //setting specific options to the EPub library
		$epub->setIdentifier($guid, EPub::IDENTIFIER_URI); 
		$iso6391 = ( '' == get_locale() ) ? 'en' : strtolower( substr(get_locale(), 0, 2) ); // only ISO 639-1	
		$epub->setLanguage($iso6391);			
		$epub->setAuthor($author, $author); // "Firstname, Lastname", "Lastname, First names"
		$epub->setPublisher(get_bloginfo( 'name' ), get_bloginfo( 'url' ));
		$epub->setSourceURL($sourceURL);
		
		$epub->addCSSFile("styles.css", "css1", $cssData);			
		$content_start =
		"<?xml version=\"1.0\" encoding=\"utf-8\"?>\n"
		. "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"\n"
		. "    \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n"
		. "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n"
		. "<head>"
		. "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n"
		. "<link rel=\"stylesheet\" type=\"text/css\" href=\"styles.css\" />\n"
		. "<title>" . $title . "</title>\n"
		. "</head>\n"
		. "<body>\n";
		
		$content_end = "\n</body>\n</html>\n";
		
		//$epub->setCoverImage("wp-content/themes/twentyten/images/headers/path.jpg");
		
		$epub->addChapter("Body", "Body.html", $content_start . $html . $content_end);
		$epub->finalize();
		$put = file_put_contents($fileepub, $epub->getBook());
		return $put;
	}
// Mobile (mobi)
	public function tomobi ($html, $options) {

		require_once "lib/phpMobi/MOBIClass/MOBI.php";
		$filemobi = $options["filename"] . '.mobi';

		$mobi = new MOBI();
		$opt = array(
			"title"=>  $options["title"],
			"author"=> $options["author"],
			"subject"=> $options["subject"]
		);
		$mobi->setOptions($opt);				
		$mobi->setData($html);
		$mobi->save($filemobi);		
		return;
	}
// FistonBook (fb2)
	public function tofb2 ($html, $options) {

		require_once "lib/phpFB2/sFB2.php";
		$filefb2 = $options["filename"] . '.fb2';
									
		$opt = array(
			"title"=> $options["title"],
			"author"=> $options["author"]
		);
		
		$fb2 = new sFB2();
		$html = $fb2->prepare($html, $opt);
		$put = $fb2->save($filefb2, $html);
		return $put;
	}
	
// Упрощенный html (html)
	public function tohtml ($html, $options) {

		$filehtml = $options["filename"] . '.html';
									
		$opt = array(
			"title"=> $options["title"],
			"author"=> $options["author"]
		);
		$html = "<html>\n"
		. "<head>"
		. "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n"
		. "<title>" . $options["title"] . "</title>\n"
		. "</head>\n"
		. "<body>\n"
		. "<p>" .$options["author"]. "</p>\n"
		. $html
		."</body></html>";

		$put = file_put_contents($filehtml, $html);
		return $put;
	}
	
// Создание файлов для чтения
	public function generate ($id) {
		
		$post = get_post($id);
		$plink = get_permalink($id);
		$html = $post->post_content;
		$html = preg_replace("/". preg_quote( $plink, '/' ).'.*?#/is', '#', $html);
		$html = '<h1 class="entry-title">' . $post->post_title . '</h1>'.$html;
		$html = $this->prepare($html);

		
		$filename = BG_FORREADERS_STORAGE_URI."/".$post->post_name;
		if (get_option('bg_forreaders_author_field') == 'post') {
			// Автор - автор поста
			$author_id = get_user_by( 'ID', $post->post_author ); 	// Get user object
			$author = $author_id->display_name;						// Get user display name
		} else {
			// Автор указан в произвольном поле
			$author = get_post_meta($post->ID, get_option('bg_forreaders_author_field'), true);
		}
		
		$options = array(
			"title"=> $post->post_title,
			"author"=> $author,
			"guid"=>$post->guid,
			"url"=>$post->guid,
			"filename"=>$filename,
			"subject" => (count(wp_get_post_categories($post->ID))) ? implode(' ,',array_map("get_cat_name", wp_get_post_categories($post->ID))) : __("Unknown subject")
		);
		set_time_limit ( intval(get_option('bg_forreaders_time_limit')) );
		if (!file_exists ($filename.".pdf") && get_option('bg_forreaders_pdf') == 'on') $this->topdf($html, $options);
		if (!file_exists ($filename.".epub") && get_option('bg_forreaders_epub') == 'on') $this->toepub($html, $options);
		if (!file_exists ($filename.".mobi") && get_option('bg_forreaders_mobi') == 'on') $this->tomobi($html, $options);
		if (!file_exists ($filename.".fb2") && get_option('bg_forreaders_fb2') == 'on') $this->tofb2($html, $options);
//		if (!file_exists ($filename.".html")) $this->tohtml($html, $options);
		$this->tohtml($html, $options);
		return;
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
	add_option('bg_forreaders_zoom', '1');
	add_option('bg_forreaders_single', '');
	add_option('bg_forreaders_excat', '');
	add_option('bg_forreaders_author_field', 'post');
	add_option('bg_forreaders_while_displayed', 'on');
	add_option('bg_forreaders_while_saved', 'on');
	add_option('bg_forreaders_time_limit', '60');

	add_option('bg_forreaders_allowed_tags', BG_FORREADERS_ALLOWED_TAGS);
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
	delete_option('bg_forreaders_zoom');
	delete_option('bg_forreaders_single');
	delete_option('bg_forreaders_excat');
	delete_option('bg_forreaders_author_field');
	delete_option('bg_forreaders_while_displayed');
	delete_option('bg_forreaders_while_saved');
	delete_option('bg_forreaders_time_limit');

	delete_option('bg_forreaders_allowed_tags');
}
