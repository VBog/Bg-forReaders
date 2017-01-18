<?php
/*****************************************************************************************
	Класс плагина
	
******************************************************************************************/
class BgForReaders {
	
// Создание файлов для чтения
	public function generate ($id) {
		
		ini_set("pcre.backtrack_limit","3000000");

		$memory_limit = trim(get_option('bg_forreaders_memory_limit'));
		if (!empty($memory_limit)) ini_set("memory_limit", $memory_limit."M");

		$time_limit = trim(get_option('bg_forreaders_time_limit'));		
		if (!empty($time_limit)) set_time_limit ( intval($time_limit) );
		
		require_once "lib/BgClearHTML.php";
		
		$post = get_post($id);
		$plink = get_permalink($id);
		$content = $post->post_content;
		// Выполнить все шорт-коды
		$content = do_shortcode ( $content );
		// Удаляем указания на текущую страницу в абсолютных ссылках с якорями (включая множественные страницы)
		$content = preg_replace("/". preg_quote( $plink, '/' ).'(\/\d+\/)?#/is', '#', $content);
		// Удаляем  указания на текущую страницу в относительных ссылках с якорями
		$site_url = get_site_url();
		$plink = str_replace ($site_url."/", "", $plink);
		$content = preg_replace("/". preg_quote( $plink, '/' ).'(\/\d+\/)?#/is', '#', $content);

		// Очищаем текст от лишних тегов разметки
		$chtml = new BgClearHTML();
		// Массив разрешенных тегов и атрибутов
		$allow_attributes = $chtml->strtoarray (get_option('bg_forreaders_tags'));
		// Оставляем в тексте только разрешенные теги и атрибуты
		$content = $chtml->prepare ($content, $allow_attributes);
		// Преобразуем атрибуты id в name во внутренних ссылках
		$content = $this->idtoname($content);
		// Очищаем внутренние ссылки и атрибуты id и name от не буквенно-цифровых символов
		$content = $this->clearanchor($content);
		if (!get_option('bg_forreaders_extlinks')) $content = $this->removehref($content);
		// Исправляем не UTF-8 символы
		$content = iconv("UTF-8","UTF-8//IGNORE",$content);
		// Заменяем двойной перенос строки на HTML конструкцию <p>...</p>, а одинарный на <br>.
		$content = wpautop( $content, true);
		// Исправляем неправильно-введенные XHTML (HTML) теги
		$content = balanceTags( $content, true );	

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
		
		// bg_forreaders_publishing_year
		if (get_option('bg_forreaders_publishing_year') == 'post') {
			// Год издания  - год модификации поста
			$publishing_year = substr( $post->post_modified, 0, 4); 
		} else {
			// Год издания указан в произвольном поле
			$publishing_year = get_post_meta($post->ID, get_option('bg_forreaders_publishing_year'), true);
		}
		if (get_option('bg_forreaders_cover_title')=='on') {
			// Миниатюра поста
			$upload_dir = wp_upload_dir();
			$attachment_data = wp_get_attachment_metadata(get_post_thumbnail_id($post->ID, 'full'));
			if ((get_option('bg_forreaders_cover_thumb')=='on') && $attachment_data && $attachment_data['file']) 
				$image_path = $upload_dir['basedir'] . '/' . $attachment_data['file'];
			else {

				// Загружаем рисунок фона с диска
				if (get_option('bg_forreaders_cover_image')) {
					$template = BG_FORREADERS_STORAGE_URI."/".get_option('bg_forreaders_cover_image');
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
							$im = false;
					}
				} else $im = false;
				
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
				$font = dirname(__file__)."/fonts/arialbd.ttf";

				$dx1 = get_option('bg_forreaders_left_offset');
				$dx2 = get_option('bg_forreaders_right_offset');
				// Выводим строки названия книги
				if (get_option('bg_forreaders_cover_title')=='on')
					$this->multiline ($post->post_title, $im, 'middle', $dx1, $dx2, $font, 24, $color);
				// Выводим имя автора
				if (get_option('bg_forreaders_cover_author')=='on')
					$this->multiline ($author, $im, get_option('bg_forreaders_top_offset'), $dx1, $dx2, $font, 16, $color);
				// Выводим название сайта и год
				if (get_option('bg_forreaders_cover_site')=='on' || get_option('bg_forreaders_cover_year')=='on') {
					$publisher = ((get_option('bg_forreaders_cover_site')=='on')?get_bloginfo( 'name' ):"")." ".((get_option('bg_forreaders_cover_year')=='on')?$publishing_year:"");
					$this->multiline ($publisher, $im, -get_option('bg_forreaders_bottom_offset'), $dx1, $dx2, $font, 12, $color);
				}
				// Создаем воременный файл изображения обложки
				imagepng ($im, BG_FORREADERS_TMP_COVER, 9); 
				// В конце освобождаем память, занятую картинкой.
				imageDestroy($im);
				$image_path = BG_FORREADERS_TMP_COVER;
			}
		} else $image_path = "";
		$filename = BG_FORREADERS_STORAGE_URI."/".$post->post_name."_".$post->ID;
		$options = array(
			"title"=> $post->post_title,
			"author"=> $author,
			"guid"=>$post->guid,
			"url"=>$post->guid,
			"thumb"=>$image_path,
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


		unset($chtml);
		$chtml=NULL;
		if (file_exists(BG_FORREADERS_TMP_COVER)) unlink (BG_FORREADERS_TMP_COVER);	// Удаляем временный файл
		
		return;
	}

// Проверяем необходимость обновления файла
	function file_updated ($filename, $type, $check_time) {
		// Если разрешет данный тип файла
		if (get_option('bg_forreaders_'.$type) == 'on') {
			// Проверяем нет ли защищенного от обновления файла?
			if (file_exists ($filename."p.".$type)) return true;
			// Проверяем есть ли обычный файл и неустарел ли он?
			if (!file_exists ($filename.".".$type) ||
				($check_time > date('Y-m-d H:i:s', filemtime($filename.".".$type)))) return false;
		}
		return true;
	}

	// Функция добавляет на изображение многострочный текст
	function multiline ($text, $im, $dy, $dx1, $dx2, $font, $font_size, $color) {
		$width = imageSX($im)-$dx1-$dx2;
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
		if ($dy == 'middle') {	// Заголовок - по центру
			$y = (imageSY($im)+$cnt*$height)/2;
		} elseif ($dy >= 0)  {	// Авторы - сверху
			$y = $dy+$cnt*$height;
		} else {				// Название сайта - снизу
			$y = imageSY($im)+$dy;
		}

		// Накладываем возращенный многострочный текст на изображение
		for ($i=0; $i<$cnt; $i++) {
			$textbox = imagettfbbox($font_size, 0, $font, $lines[$i]);
			$wt = abs($textbox[4] - $textbox[0]);
			$px = ($width-$wt)/2 + $dx1;
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
		$pdf->ignore_invalid_utf8 = true;
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
			$pdf->AddPage('','','','','on');
		}
		//$pdf->showImageErrors = true;
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
			"css"=> get_option('bg_forreaders_css') 
		);

		$fb2 = new bgFB2();
		$html = $fb2->prepare($html, $opt);
		$fb2->save($filefb2, $html);
		unset($fb2);
		$fb2=NULL;
		return;
	}
	
	function idtoname($html) {
			return preg_replace('/<a([^>]*?)id\s*=/is','<a$1name=',$html);
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