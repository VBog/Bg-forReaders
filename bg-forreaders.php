<?php
/*
Plugin Name: Bg forReaders
Plugin URI: https://bogaiskov.ru/bg_forreaders
Description: Конвертирует контент страницы в популярные форматы для чтения и выводит на экран форму для скачивания.
Version: 0.8.0
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
define( 'BG_FORREADERS_VERSION', '0.8.0' );
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
		$filename = $post->post_name."_".$post->ID.".".$type;
		if (get_option('bg_forreaders_'.$type) == 'on' && file_exists(BG_FORREADERS_STORAGE."/".$filename)) {
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
	Класс плагина
	
******************************************************************************************/
class BgForReaders {
	
// Создание файлов для чтения
	public function generate ($id) {
		
		ini_set("pcre.backtrack_limit","3000000");

		$the_memory_limit = (int) ini_get('memory_limit');
		$memory_limit = trim(get_option('bg_forreaders_memory_limit'));
		if (!empty($memory_limit)) ini_set("memory_limit", $memory_limit."M");
//		ini_set("memory_limit", "1G");

		$the_time_limit = (int) ini_get('max_execution_time') ;
		$the_time_limit = empty($the_time_limit) ? 30 : $the_time_limit;
		$time_limit = trim(get_option('bg_forreaders_time_limit'));		
		if (!empty($time_limit)) set_time_limit ( intval($time_limit) );
//		set_time_limit ( get_option('bg_forreaders_time_limit') );
		
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

//		Очищаем текст от лишних тегов разметки
		$сhtml = new BgClearHTML();
		// Массив разрешенных тегов и атрибутов
		$allow_attributes = $сhtml->strtoarray (get_option('bg_forreaders_tags'));
		// Оставляем в тексте только разрешенные теги и атрибуты
		$content = $сhtml->prepare ($content, $allow_attributes);
		$content = $this->idtoname($content);
		$content = $this->clearanchor($content);
		if (!get_option('bg_forreaders_extlinks')) $content = $this->removehref($content);
		// Исправляем неправильно-введенные XHTML (HTML) теги
		$content = balanceTags( $content, true );	


		$filename = BG_FORREADERS_STORAGE_URI."/".$post->post_name."_".$post->ID;
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
		
		// Определяем язык блога
		$lang = get_bloginfo('language');	
		$lang = substr($lang,0, 2);
		
		// Миниатюра поста
		$upload_dir = wp_upload_dir();
		$attachment_data = wp_get_attachment_metadata(get_post_thumbnail_id($post->ID, 'full'));
		if ($attachment_data && $attachment_data['file']) $image_path = $upload_dir['basedir'] . '/' . $attachment_data['file'];
		else {
			// Загружаем рисунок фона с диска
			$template = get_option('bg_forreaders_cover_image');
			$ext = substr(strrchr($template, '.'), 1);
			switch ($ext) {
				case 'jpg':
				case 'jpeg':
					 $im = @imageCreateFromJpeg($template);
					 break;
				case 'gif':
					 $im = @imageCreateFromGif($template);
					 break;
				case 'png':
					 $im = @imageCreateFromPng($template);
					 break;
				default:
					return $im = false;
			}
			
			if (!$im) {
				// Создаем пустое изображение
				$im  = imagecreatetruecolor(840, 1188);
				// Создаем в палитре цвет фона
				list($r, $g, $b) = $this->hex2rgb( get_option('bg_forreaders_bg_color') );
				$bkcolor = imageColorAllocate($im, $r, $g, $b);
				imagefilledrectangle($im, 0, 0, 840, 1188, $bkcolor);
			}

			// Создаем в палитре цвет текста
			list($r, $g, $b) = $this->hex2rgb( get_option('bg_forreaders_text_color') );
			$color = imageColorAllocate($im, $r, $g, $b);
			// Подгружаем шрифт
			$font = dirname(__file__)."/fonts/BOOKOSB.TTF";
			// Выводим строки названия книги
			$this->multiline ($post->post_title, $im, 'middle', $font, 48, $color);
			// Выводим строки названия книги
			$this->multiline ($author, $im, 220, $font, 24, $color);
			// Создаем воременный файл изображения обложки
			imagepng ($im, 'tmp_cover.png', 9); 
			// В конце освобождаем память, занятую картинкой.
			imageDestroy($im);
			$image_path = 'tmp_cover.png';
		}
		
		$options = array(
			"title"=> $post->post_title,
			"author"=> $author,
			"guid"=>$post->guid,
			"url"=>$post->guid,
			"thumb"=>$image_path,
//			"cover"=>get_option('bg_forreaders_cover_image'),
			"filename"=>$filename,
			"lang"=>$lang,
			"genre"=>$genre,
			"subject" => (count(wp_get_post_categories($post->ID))) ? 
						implode(' ,',array_map("get_cat_name", wp_get_post_categories($post->ID))) :
						__("Unknown subject")			
		);
		if (get_option('bg_forreaders_add_author')) $content = '<p><em>'.$author.'</em></p>'.$content;
		if (get_option('bg_forreaders_add_title')) $content = '<h1>'.$post->post_title.'</h1>'.$content;

		if (!$this->file_updated ($filename, "pdf", $post->post_modified_gmt)) $this->topdf($content, $options);
		if (!$this->file_updated ($filename, "epub", $post->post_modified_gmt)) $this->toepub($content, $options);
		if (!$this->file_updated ($filename, "mobi", $post->post_modified_gmt)) $this->tomobi($content, $options);
		if (!$this->file_updated ($filename, "fb2", $post->post_modified_gmt)) $this->tofb2($content, $options);

		// Восстанавливаем настройки системных параметров
		if (!empty($memory_limit) && !empty($the_memory_limit)) ini_set("memory_limit", $the_memory_limit."M");
		if (!empty($time_limit) && !empty($the_time_limit)) set_time_limit ( $the_time_limit );

		unset($сhtml);
		$сhtml=NULL;
		if (file_exists('tmp_cover.png')) unlink ('tmp_cover.png');	// Удаляем временный файл
		
		return;
	}

// Проверяем необходимость обновления файла
	function file_updated ($filename, $type, $check_time) {
		if (get_option('bg_forreaders_'.$type) == 'on') {
			if (!file_exists ($filename.".".$type) ||
				($check_time > date('Y-m-d H:i:s', filemtime($filename.".".$type)))) return false;
		}
		return true;
	}

	// Функция добавляет на изображение многострочный текст
	function multiline ($text, $im, $h, $font, $font_size, $color) {
		$width = imageSX($im);
		// Разбиваем наш текст на массив слов
		$arr = explode(' ', $text);
		$ret = "";
		// Перебираем наш массив слов
		foreach($arr as $word)	{
			// Временная строка, добавляем в нее слово
			$tmp_string = $ret.' '.$word;

			// Получение параметров рамки обрамляющей текст, т.е. размер временной строки 
			$textbox = imagettfbbox($font_size, 0, $font, $tmp_string);
			
			// Если временная строка не укладывается в нужные нам границы, то делаем перенос строки, иначе добавляем еще одно слово
			if($textbox[2]-$textbox[0] > $width)
				$ret.=($ret==""?"":"\n").$word;
			else
				$ret.=($ret==""?"":" ").$word;
		}
		$ret=str_replace("\n", "|", $ret);
		$lines = explode('|', $ret);
		$cnt = count ($lines);

		// Получение параметров рамки обрамляющей текст, т.е. размер временной строки 
		$textbox = imagettfbbox($font_size, 0, $font, $ret);
		$height = abs($textbox[5] - $textbox[1]);
		if ($h == 'middle')
			$y = (imageSY($im)+$cnt*$height)/2;
		else
			$y = $h+$cnt*$height;

		// Накладываем возращенный многострочный текст на изображение
		for ($i=0; $i<$cnt; $i++) {
			$textbox = imagettfbbox($font_size, 0, $font, $lines[$i]);
			$width = abs($textbox[4] - $textbox[0]);
			$px = (imageSX($im)-$width)/2;
			$py = $y - $height*($cnt-$i);
			imagettftext($im, $font_size ,0, $px, $py, $color, $font, $lines[$i]);
		}
		return;
	}
	// Convert Hex Color to RGB
	function hex2rgb( $colour ) {
        if ( $colour[0] == '#' ) {
                $colour = substr( $colour, 1 );
        }
        if ( strlen( $colour ) == 6 ) {
                list( $r, $g, $b ) = array( $colour[0] . $colour[1], $colour[2] . $colour[3], $colour[4] . $colour[5] );
        } elseif ( strlen( $colour ) == 3 ) {
                list( $r, $g, $b ) = array( $colour[0] . $colour[0], $colour[1] . $colour[1], $colour[2] . $colour[2] );
        } else {
                return false;
        }
        $r = hexdec( $r );
        $g = hexdec( $g );
        $b = hexdec( $b );
        return array( $r, $g, $b );
}

// Portable Document Format (PDF)
	function topdf ($html, $options) {

		require_once "lib/mpdf60/mpdf.php";
		$filepdf = $options["filename"] . '.pdf';
		
		$pdf = new mPDF();
		$pdf->SetTitle($options["title"]);
		$pdf->SetAuthor($options["author"]);
		$pdf->SetSubject($options["subject"]);
		$pdf->h2bookmarks = array('H1'=>0, 'H2'=>1, 'H3'=>2);
		$cssData = get_option('bg_forreaders_css');
		$pdf->AddPage('','','','','on');
		if ($cssData != "") {
			$pdf->WriteHTML($cssData,1);	// The parameter 1 tells that this is css/style only and no body/html/text
		}
		if ($options["thumb"]) {
			$pdf->WriteHTML('<div style="position: absolute; left:0; right: 0; top: 0; bottom: 0; width: 210mm; height: 297mm; '.
			'background: url('.$options["thumb"].') no-repeat center; background-size: contain;"></div>');
		}
		//$pdf->showImageErrors = true;
		$pdf->AddPage('','','','','on');
		$pdf->WriteHTML($html);
		$pdf->Output($filepdf, 'F');
		unset($pdf);
		$pdf=NULL;
		return;
	}
// Electronic Publication (ePub)
	function toepub ($html, $options) {
		
// ePub uses XHTML 1.1, preferably strict.
		require_once "lib/PHPePub/EPub.php";
		$fileepub = $options["filename"] . '.epub';
		$cssData = get_option('bg_forreaders_css');

// The mandatory fields		
		$epub = new EPub();
		$epub->setTitle($options["title"]); 
		$epub->setLanguage($options["lang"]);			
		$epub->setIdentifier($options["guid"], EPub::IDENTIFIER_URI); 
// The additional optional fields
		$epub->setAuthor($options["author"], ""); // "Firstname, Lastname"
		$epub->setPublisher(get_bloginfo( 'name' ), get_bloginfo( 'url' ));
		$epub->setSourceURL($options["url"]);
		
		if ($options["thumb"]) $epub->setCoverImage($options["thumb"]);
		
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
		file_put_contents($fileepub, $epub->getBook());
		unset($epub);
		$epub=NULL;
		return;
	}
// Mobile (mobi)
	function tomobi ($html, $options) {

		require_once "lib/phpMobi/MOBIClass/MOBI.php";
		$filemobi = $options["filename"] . '.mobi';

		$html =
		"<?xml version=\"1.0\" encoding=\"utf-8\"?>\n"
		. "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"\n"
		. "    \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n"
		. "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n"
		. "<head>"
		. "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n"
		. "<style type=\"text/css\">\n"
		. get_option('bg_forreaders_css')
		. "</style>\n"
		. "<title>" . $options["title"] . "</title>\n"
		. "</head>\n"
		. "<body>\n"
		. $html
		."\n</body>\n</html>\n";
		$mobi = new MOBI();
		$mobi_content = new MOBIFile();
		$mobi_content->set("title", $options["title"]);
		$mobi_content->set("author", $options["author"]);
		$mobi_content->set("publishingdate", date('d-m-Y'));

		$mobi_content->set("source", $options["url"]);
		$mobi_content->set("publisher", get_bloginfo( 'name' ), get_bloginfo( 'url' ));
		$mobi_content->set("subject", $options["subject"]);
		if ($options["thumb"]) {
			$mobi_content->appendImage($this->imageCreateFrom($options["thumb"]));
			$mobi_content->appendPageBreak();
		}
		$mobi->setContentProvider($mobi_content);
		$mobi->setData($html);
		$mobi->save($filemobi);		
		unset($mobi);
		$mobi=NULL;
		return;
	}
// FistonBook (fb2)
	function tofb2 ($html, $options) {

		require_once "lib/phpFB2/bgFB2.php";
		$filefb2 = $options["filename"] . '.fb2';
									
		$opt = array(
			"title"=> $options["title"],
			"author"=> $options["author"],
			"genre"=> $options["genre"],
			"lang"=> $options["lang"],
			"version"=> '1.0',
			"cover"=> $options["thumb"],
			"publisher"=>get_bloginfo( 'name' )." ".get_bloginfo( 'url' ),
			"css"=> get_option('bg_forreaders_css'), 
			"tags"=> BG_FORREADERS_FB2_TAGS,
			"entities" => BG_FORREADERS_FB2_ENTITIES
		);

		$fb2 = new bgFB2();
		$html = $fb2->prepare($html, $opt);
		$fb2->save($filefb2, $html);
		unset($fb2);
		$fb2=NULL;
		return;
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
	function imageCreateFrom($filepath) {
		$type = substr(strrchr($filepath, '.'), 1);
	    switch ($type) {
	        case 'gif' :
	            $im = imageCreateFromGif($filepath);
	        break;
	        case 'jpg' :
	        case 'jpeg' :
	            $im = imageCreateFromJpeg($filepath);
	        break;
	        case 'png' :
	            $im = imageCreateFromPng($filepath);
	        break;
			default:
	        return false;
	    }
	    return $im;
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
	add_option('bg_forreaders_separator', '');
	add_option('bg_forreaders_zoom', '1');
	add_option('bg_forreaders_single', 'on');
	add_option('bg_forreaders_cats', 'excluded');
	add_option('bg_forreaders_excat', '');
	add_option('bg_forreaders_author_field', 'post');
	add_option('bg_forreaders_genre', 'genre');
	add_option('bg_forreaders_cover_image', '');
	add_option('bg_forreaders_text_color', '#000000');
	add_option('bg_forreaders_bg_color', '#ffffff');
	add_option('bg_forreaders_add_title', 'on');
	add_option('bg_forreaders_add_author', 'on');
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
	delete_option('bg_forreaders_genre');
	delete_option('bg_forreaders_cover_image');
	delete_option('bg_forreaders_text_color');
	delete_option('bg_forreaders_bg_color');
	delete_option('bg_forreaders_add_title');
	delete_option('bg_forreaders_add_author');
	delete_option('bg_forreaders_while_displayed');
	delete_option('bg_forreaders_while_saved');
	delete_option('bg_forreaders_memory_limit');
	delete_option('bg_forreaders_time_limit');

	delete_option('bg_forreaders_css');
	delete_option('bg_forreaders_tags');
	delete_option('bg_forreaders_extlinks');
}
