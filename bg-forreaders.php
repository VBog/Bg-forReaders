<?php
/*
Plugin Name: Bg forReaders
Plugin URI: https://bogaiskov.ru/bg_forreaders
Description: Конвертирует контент страницы в популярные форматы для чтения и выводит на экран форму для скачивания.
Version: 0.6.2
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
define( 'BG_FORREADERS_VERSION', '0.6.2' );
define( 'BG_FORREADERS_STORAGE', 'bg_forreaders' );
define( 'BG_FORREADERS_STORAGE_URI', trailingslashit( ABSPATH ) . 'bg_forreaders' );
define( 'BG_FORREADERS_URI', plugin_dir_path( __FILE__ ) );
define( 'BG_FORREADERS_STORAGE_PATH', str_replace ( ABSPATH , '' , BG_FORREADERS_STORAGE_URI  ) );
define( 'BG_FORREADERS_PATH', str_replace ( ABSPATH , '' , BG_FORREADERS_URI ) );

define( 'BG_FORREADERS_ALLOWED_TAGS',
"img[src|alt],div[id],blockquote[id],
h1[align|id],h2[align|id],h3[align|id],h4[align|id],h5[align|id],h6[align|id],
hr,p[align|id],br,ol[id],ul[id],li[id],a[href|name|id],
table[id],tr[align],th[id|colspan|rowspan|align],td[id|colspan|rowspan|align],
b,strong,i,em,u,sub,sup,strike,code");

define( 'BG_FORREADERS_PDF_CSS', "");
define( 'BG_FORREADERS_PDF_TAGS',
"img[src|alt],div[id],blockquote[id],
h1[align|id],h2[align|id],h3[align|id],h4[align|id],h5[align|id],h6[align|id],
hr,p[align|id],br,ol[id],ul[id],li[id],a[href|name|id],
table[id],tr[align],th[id|colspan|rowspan|align],td[id|colspan|rowspan|align],
b,strong,i,em,u,sub,sup,strike,code");

define( 'BG_FORREADERS_EPUB_CSS', "");
define( 'BG_FORREADERS_EPUB_TAGS',
"img[src|alt],div[id],blockquote[id],
h1[align|id],h2[align|id],h3[align|id],h4[align|id],h5[align|id],h6[align|id],
hr,p[align|id],br,ol[id],ul[id],li[id],a[href|name|id],
table[id],tr[align],th[id|colspan|rowspan|align],td[id|colspan|rowspan|align],
b,strong,i,em,u,sub,sup,strike,code");

define( 'BG_FORREADERS_MOBI_CSS', "");
define( 'BG_FORREADERS_MOBI_TAGS',
"img[src|alt],div[id],blockquote[id],
h1[align|id],h2[align|id],h3[align|id],h4[align|id],h5[align|id],h6[align|id],
hr,p[align|id],br,ol[id],ul[id],li[id],a[href|name|id],
table[id],tr[align],th[id|colspan|rowspan|align],td[id|colspan|rowspan|align],
b,strong,i,em,u,sub,sup,strike,code");

define( 'BG_FORREADERS_FB2_CSS', "");
define( 'BG_FORREADERS_FB2_TAGS',
"img[src|alt],div[id],blockquote[id],
h1[id],h2[id],h3[id],h4[id],h5[id],h6[id],
hr,p[id],br,ol[id],ul[id],li[id],a[href|name|id],
table[id],tr[align],th[id|colspan|rowspan|align],td[id|colspan|rowspan|align],
b,strong,i,em,u,sub,sup,strike,code");
define( 'BG_FORREADERS_FB2_ENTITIES',
"&amp;,&lt;,&gt;,&apos;,&quot;,&nbsp;[ ],&hellip;[...],&ndash;[-],&mdash;[—],&oacute;[o]");


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
			$link_type = get_option('bg_forreaders_links');
			if ($link_type == 'php') $href = trailingslashit( home_url() ).BG_FORREADERS_STORAGE."?file=".$post->post_name.".".$type;
			else $href = trailingslashit( home_url() ).BG_FORREADERS_STORAGE."/".$post->post_name.".".$type;
			$download = ($link_type == 'html5')? ' download':'';
			if ($zoom) {
				$forreaders .= sprintf ('<div><a class="%s" href="%s" title="%s"%s></a></div>', $type, $href, $title, $download);
			} else {
				$forreaders .= sprintf ('<span><a href="%s" title="%s"%s>%s</a></span><br>', $href, $title, $download, sprintf(__('Download as %s','bg-forreaders'), $document_type));
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
class BgForReaders {
	
// Создание файлов для чтения
	public function generate ($id) {
		
		require_once "lib/BgClearHTML.php";
		
		$post = get_post($id);
		$plink = get_permalink($id);
		$content = $post->post_content;
		// Выполнить все шорт-коды
		$content = do_shortcode ( $content );
		// Удаляем указания на текущую страницу в ссылках с якорями
		$content = preg_replace("/". preg_quote( $plink, '/' ).'.*?#/is', '#', $content);
		// Исправляем неправильно-введенные XHTML (HTML) теги
		$content = balanceTags( $content, true );	

		$filename = BG_FORREADERS_STORAGE_URI."/".$post->post_name;
		if (get_option('bg_forreaders_author_field') == 'post') {
			// Автор - автор поста
			$author_id = get_user_by( 'ID', $post->post_author ); 	// Get user object
			$author = $author_id->display_name;						// Get user display name
		} else {
			// Автор указан в произвольном поле
			$author = get_post_meta($post->ID, get_option('bg_forreaders_author_field'), true);
		}
		if (get_option('bg_forreaders_genre') == 'genre') {
			// Жанр указан в произвольном поле
			$genre = get_post_meta($post->ID, 'genre', true);
		} else $genre = get_option('bg_forreaders_genre');
		
		$lang = get_bloginfo('language');	
		$lang = substr($lang,0, 2);
		
		$options = array(
			"title"=> $post->post_title,
			"author"=> $author,
			"guid"=>$post->guid,
			"url"=>$post->guid,
			"filename"=>$filename,
			"lang"=>$lang,
			"genre"=>$genre,
			"subject" => (count(wp_get_post_categories($post->ID))) ? 
						implode(' ,',array_map("get_cat_name", wp_get_post_categories($post->ID))) :
						__("Unknown subject")			
		);
		set_time_limit ( intval(get_option('bg_forreaders_time_limit')) );
		if (!file_exists ($filename.".pdf") && get_option('bg_forreaders_pdf') == 'on') $this->topdf($content, $options);
		if (!file_exists ($filename.".epub") && get_option('bg_forreaders_epub') == 'on') $this->toepub($content, $options);
		if (!file_exists ($filename.".mobi") && get_option('bg_forreaders_mobi') == 'on') $this->tomobi($content, $options);
		if (!file_exists ($filename.".fb2") && get_option('bg_forreaders_fb2') == 'on') $this->tofb2($content, $options);
//		if (!file_exists ($filename.".html")) $this->tohtml($content, $options);
		return;
	}

// Portable Document Format (PDF)
	function topdf ($html, $options) {

		ini_set("pcre.backtrack_limit","3000000");
		ini_set("memory_limit", "256M");
		require_once "lib/mpdf60/mpdf.php";
		$filepdf = $options["filename"] . '.pdf';
		
//		Очищаем текст от лишних тегов разметки
		$сhtml = new BgClearHTML();
		// Массив разрешенных тегов и атрибутов
		$allow_attributes = $сhtml->strtoarray (get_option('bg_forreaders_pdf_tags'));
		// Оставляем в тексте только разрешенные теги и атрибуты
		$html = $сhtml->prepare ($html, $allow_attributes);
		$html = $this->idtoname($html);
		$html = $this->clearanchor($html);
		if (!get_option('bg_forreaders_pdf_extlinks')) $html = $this->removehref($html);
		// Исправляем неправильно-введенные XHTML (HTML) теги
		$html = balanceTags( $html, true );	

		$pdf = new mPDF();
		$pdf->SetTitle($options["title"]);
		$pdf->SetAuthor($options["author"]);
		//$pdf->showImageErrors = true;
		$cssData = get_option('bg_forreaders_pdf_css');
		if ($cssData != "") {
			$pdf->WriteHTML($cssData,1);	// The parameter 1 tells that this is css/style only and no body/html/text
		}
		$pdf->WriteHTML($html);
		$pdf->Output($filepdf, 'F');
		return;
	}
// Electronic Publication (ePub)
	function toepub ($html, $options) {
		
// ePub uses XHTML 1.1, preferably strict.
		require_once "lib/PHPePub/EPub.php";
		$fileepub = $options["filename"] . '.epub';
		$cssData = get_option('bg_forreaders_epub_css');

//		Очищаем текст от лишних тегов разметки
		$сhtml = new BgClearHTML();
		// Массив разрешенных тегов и атрибутов
		$allow_attributes = $сhtml->strtoarray (get_option('bg_forreaders_epub_tags'));
		// Оставляем в тексте только разрешенные теги и атрибуты
		$html = $сhtml->prepare ($html, $allow_attributes);
		$html = $this->idtoname($html);
		$html = $this->clearanchor($html);
		if (!get_option('bg_forreaders_epub_extlinks')) $html = $this->removehref($html);
		// Исправляем неправильно-введенные XHTML (HTML) теги
		$html = balanceTags( $html, true );	

// The mandatory fields		
		$epub = new EPub();
		$epub->setTitle($options["title"]); 
		$epub->setLanguage($options["lang"]);			
		$epub->setIdentifier($options["guid"], EPub::IDENTIFIER_URI); 
// The additional optional fields
		$epub->setAuthor($options["author"], ""); // "Firstname, Lastname"
		$epub->setPublisher(get_bloginfo( 'name' ), get_bloginfo( 'url' ));
		$epub->setSourceURL($options["url"]);
		
		$epub->addCSSFile("styles.css", "css1", $cssData);			
		$html =
		"<?xml version=\"1.0\" encoding=\"utf-8\"?>\n"
		. "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"\n"
		. "    \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n"
		. "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n"
		. "<head>"
		. "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n"
		. "<link rel=\"stylesheet\" type=\"text/css\" href=\"styles.css\" />\n"
		. "<title>" . $options["title"] . "</title>\n"
		. "</head>\n"
		. "<body>\n"
		. $html
		."\n</body>\n</html>\n";
		
		$epub->addChapter("Book", "Book.html", $html, false, EPub::EXTERNAL_REF_ADD, '');
		$epub->finalize();
		$put = file_put_contents($fileepub, $epub->getBook());
		return $put;
	}
// Mobile (mobi)
	function tomobi ($html, $options) {

		require_once "lib/phpMobi/MOBIClass/MOBI.php";
		$filemobi = $options["filename"] . '.mobi';

//		Очищаем текст от лишних тегов разметки
		$сhtml = new BgClearHTML();
		// Массив разрешенных тегов и атрибутов
		$allow_attributes = $сhtml->strtoarray (get_option('bg_forreaders_mobi_tags'));
		// Оставляем в тексте только разрешенные теги и атрибуты
		$html = $сhtml->prepare ($html, $allow_attributes);
		$html = $this->idtoname($html);
		$html = $this->clearanchor($html);
		if (!get_option('bg_forreaders_mobi_extlinks')) $html = $this->removehref($html);
		// Исправляем неправильно-введенные XHTML (HTML) теги
		$html = balanceTags( $html, true );	

		$html =
		"<?xml version=\"1.0\" encoding=\"utf-8\"?>\n"
		. "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"\n"
		. "    \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n"
		. "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n"
		. "<head>"
		. "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n"
		. "<style type=\"text/css\">\n"
		. get_option('bg_forreaders_mobi_css')
		. "</style>\n"
		. "<title>" . $options["title"] . "</title>\n"
		. "</head>\n"
		. "<body>\n"
		. $html
		."\n</body>\n</html>\n";
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
	function tofb2 ($html, $options) {

		require_once "lib/phpFB2/sFB2.php";
		$filefb2 = $options["filename"] . '.fb2';
									
		$opt = array(
			"title"=> $options["title"],
			"author"=> $options["author"],
			"genre"=> $options["genre"],
			"lang"=> $options["lang"],
			"css"=> get_option('bg_forreaders_fb2_css'), 
			"tags"=> get_option('bg_forreaders_fb2_tags'),
			"entities" => get_option('bg_forreaders_fb2_entities')
		);

		if (!get_option('bg_forreaders_fb2_extlinks')) $html = $this->removehref($html);

		$fb2 = new sFB2();
		$html = $fb2->prepare($html, $opt);
		$put = $fb2->save($filefb2, $html);
		return $put;
	}
	
// Упрощенный html (html)
	function tohtml ($html, $options) {

		$filehtml = $options["filename"] . '.html';
									
//		Очищаем текст от лишних тегов разметки
		$сhtml = new BgClearHTML();
		// Массив разрешенных тегов и атрибутов
		$allow_attributes = $сhtml->strtoarray (get_option('bg_forreaders_allowed_tags'));
		// Оставляем в тексте только разрешенные теги и атрибуты
		$html = $сhtml->prepare ($html, $allow_attributes);
		$html = $сhtml->addEOL ($html);

		$html = 
		"<?xml version=\"1.0\" encoding=\"utf-8\"?>\n"
		. "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"\n"
		. "    \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n"
		. "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n"
		. "<head>\n"
		. "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n"
		. "<title>" . $options["title"] . "</title>\n"
		. "</head>\n"
		. "<body>\n"
		. $html
		."</body></html>";

		$put = file_put_contents($filehtml, $html);
		return $put;
	}
	
	function idtoname($html) {
			return preg_replace('/<a(.*?)id\s*=/is','<a\1name=',$html);
	}

	// Функция очищает внутренние ссылки и атрибуты id и name от не буквенно-цифровых символов
	//	$html = $this->clearanchor($html);
	function clearanchor($html) {
		$html = preg_replace_callback('/href\s*=\s*([\"\'])(.*?)(\1)/is',
		function ($match) {
			if($match[2][0] == '#') {	// Внутренняя ссылка
				$anhor = mb_substr($match[2],1);
				$anhor = bg_forreaders_clearurl($anhor);
				return 'href="#'.$anhor.'"';
			}else return 'href="'.$match[2].'"';
		} ,$html);
		$html = preg_replace_callback('/(id|name)\s*=\s*([\"\'])(.*?)(\2)/is',
		function ($match) {
			$anhor = bg_forreaders_clearurl($match[3]);
			return $match[1].'="'.$anhor.'"';
		} ,$html);
		
		return $html;
	}
	// Функция удаляет все внешние ссылки
	function removehref($html) {
		$html = preg_replace_callback('/href\s*=\s*([\"\'])(.*?)(\1)/is',
		function ($match) {
			if($match[2][0] == '#') {	// Внутренняя ссылка
				return 'href="'.$match[2].'"';
			} else return '';			// Удаляем внешнюю ссылку
		} ,$html);
		// Удаляем пустые теги <a>
		$html = preg_replace('/<a\s*>(.*?)<\/a>/is','\1',$html);
		
		return $html;
	}
		
}
// Функция оставляет в строке только буквенно-цифровые символы, 
// заменяя пробелы, знак + и другие символы на _ 
function bg_forreaders_clearurl ($str) {
	$str = urldecode($str);
	$str = preg_replace ('/&[a-z0-9]+;/is', '_', $str);
	$str = htmlentities($str);
	$str = preg_replace ('/&[a-z0-9]+;/is', '_', $str);
	$str = preg_replace ('/[\s\+\"\'\&]+/is', '_', $str);
	$str = urlencode($str);
	$str = preg_replace ('/%[\da-f]{2}/is', '_', $str);
	return $str;
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
	add_option('bg_forreaders_genre', 'genre');
	add_option('bg_forreaders_while_displayed', 'on');
	add_option('bg_forreaders_while_saved', 'on');
	add_option('bg_forreaders_time_limit', '60');

	add_option('bg_forreaders_allowed_tags', BG_FORREADERS_ALLOWED_TAGS);
	
	add_option('bg_forreaders_pdf_css', BG_FORREADERS_PDF_CSS);
	add_option('bg_forreaders_pdf_tags', BG_FORREADERS_PDF_TAGS);
	add_option('bg_forreaders_pdf_extlinks', 'on');
	
	add_option('bg_forreaders_epub_css', BG_FORREADERS_EPUB_CSS);
	add_option('bg_forreaders_epub_tags', BG_FORREADERS_EPUB_TAGS);
	add_option('bg_forreaders_epub_extlinks', 'on');
	
	add_option('bg_forreaders_mobi_css', BG_FORREADERS_EPUB_CSS);
	add_option('bg_forreaders_mobi_tags', BG_FORREADERS_MOBI_TAGS);
	add_option('bg_forreaders_mobi_extlinks', 'on');
	
	add_option('bg_forreaders_fb2_css', BG_FORREADERS_FB2_CSS);
	add_option('bg_forreaders_fb2_tags', BG_FORREADERS_FB2_TAGS);
	add_option('bg_forreaders_fb2_entities', BG_FORREADERS_FB2_ENTITIES);
	add_option('bg_forreaders_fb2_extlinks', 'on');

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
	delete_option('bg_forreaders_genre');
	delete_option('bg_forreaders_while_displayed');
	delete_option('bg_forreaders_while_saved');
	delete_option('bg_forreaders_time_limit');

	delete_option('bg_forreaders_allowed_tags');

	delete_option('bg_forreaders_pdf_css');
	delete_option('bg_forreaders_pdf_tags');
	delete_option('bg_forreaders_pdf_extlinks');

	delete_option('bg_forreaders_epub_css');
	delete_option('bg_forreaders_epub_tags');
	delete_option('bg_forreaders_epub_extlinks');

	delete_option('bg_forreaders_mobi_css');
	delete_option('bg_forreaders_mobi_tags');
	delete_option('bg_forreaders_mobi_extlinks');

	delete_option('bg_forreaders_fb2_css');
	delete_option('bg_forreaders_fb2_tags');
	delete_option('bg_forreaders_fb2_entities');
	delete_option('bg_forreaders_fb2_extlinks');
}
