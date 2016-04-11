<?php
/*****************************************************************************************
	����� �������
	
******************************************************************************************/
class BgForReaders {
	
// �������� ������ ��� ������
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
		// ��������� ��� ����-����
		$content = do_shortcode ( $content );
		// ������� �������� �� ������� �������� � ������� � �������
		$content = preg_replace("/". preg_quote( $plink, '/' ).'.*?#/is', '#', $content);
		// ���������� �����������-��������� XHTML (HTML) ����
		$content = balanceTags( $content, true );	

//		������� ����� �� ������ ����� ��������
		$�html = new BgClearHTML();
		// ������ ����������� ����� � ���������
		$allow_attributes = $�html->strtoarray (get_option('bg_forreaders_tags'));
		// ��������� � ������ ������ ����������� ���� � ��������
		$content = $�html->prepare ($content, $allow_attributes);
		$content = $this->idtoname($content);
		$content = $this->clearanchor($content);
		if (!get_option('bg_forreaders_extlinks')) $content = $this->removehref($content);
		// ���������� �����������-��������� XHTML (HTML) ����
		$content = balanceTags( $content, true );	

		if (get_option('bg_forreaders_author_field') == 'post') {
			// ����� - ����� �����
			$author_id = get_user_by( 'ID', $post->post_author ); 	// Get user object
			$author = $author_id->display_name;						// Get user display name
		} else {
			// ����� ������ � ������������ ����
			$author = get_post_meta($post->ID, get_option('bg_forreaders_author_field'), true);
		}
		if (get_option('bg_forreaders_genre') == 'genre') {
			// ���� ������ � ������������ ����
			$genre = get_post_meta($post->ID, 'genre', true);
		} else $genre = get_option('bg_forreaders_genre');
		
		// ���������� ���� �����
		$lang = get_bloginfo('language');	
		$lang = substr($lang,0, 2);
		
		// bg_forreaders_publishing_year
		if (get_option('bg_forreaders_publishing_year') == 'post') {
			// ��� �������  - ��� ����������� �����
			$publishing_year = substr( $post->post_modified, 0, 4); 
		} else {
			// ��� ������� ������ � ������������ ����
			$publishing_year = get_post_meta($post->ID, get_option('bg_forreaders_publishing_year'), true);
		}
		$publisher = get_bloginfo( 'name' )." ".$publishing_year;
		// ��������� �����
		$upload_dir = wp_upload_dir();
		$attachment_data = wp_get_attachment_metadata(get_post_thumbnail_id($post->ID, 'full'));
		if ((get_option('bg_forreaders_cover_thumb')=='on') && $attachment_data && $attachment_data['file']) 
			$image_path = $upload_dir['basedir'] . '/' . $attachment_data['file'];
		else {
			// ��������� ������� ���� � �����
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
				// ������� ������ �����������
				$im  = imagecreatetruecolor(840, 1188);
				// ������� � ������� ���� ����
				list($r, $g, $b) = $this->hex2rgb( get_option('bg_forreaders_bg_color') );
				$bkcolor = imageColorAllocate($im, $r, $g, $b);
				imagefilledrectangle($im, 0, 0, 840, 1188, $bkcolor);
			}

			// ������� � ������� ���� ������
			list($r, $g, $b) = $this->hex2rgb( get_option('bg_forreaders_text_color') );
			$color = imageColorAllocate($im, $r, $g, $b);
			// ���������� �����
			$font = dirname(__file__)."/fonts/BOOKOSB.TTF";

			$dx1 = get_option('bg_forreaders_left_offset');
			$dx2 = get_option('bg_forreaders_right_offset');
			// ������� ������ �������� �����
			$this->multiline ($post->post_title, $im, 'middle', $dx1, $dx2, $font, 24, $color);
			// ������� ��� ������
			$this->multiline ($author, $im, get_option('bg_forreaders_top_offset'), $dx1, $dx2, $font, 16, $color);
			// ������� �������� �����
			$this->multiline ($publisher, $im, -get_option('bg_forreaders_bottom_offset'), $dx1, $dx2, $font, 12, $color);
			// ������� ���������� ���� ����������� �������
			imagepng ($im, 'tmp_cover.png', 9); 
			// � ����� ����������� ������, ������� ���������.
			imageDestroy($im);
			$image_path = 'tmp_cover.png';
		}
		
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


		unset($�html);
		$�html=NULL;
		if (file_exists('tmp_cover.png')) unlink ('tmp_cover.png');	// ������� ��������� ����
		
		return;
	}

// ��������� ������������� ���������� �����
	function file_updated ($filename, $type, $check_time) {
		// ���� �������� ������ ��� �����
		if (get_option('bg_forreaders_'.$type) == 'on') {
			// ��������� ��� �� ����������� �� ���������� �����?
			if (file_exists ($filename."p.".$type)) return true;
			// ��������� ���� �� ������� ���� � ��������� �� ��?
			if (!file_exists ($filename.".".$type) ||
				($check_time > date('Y-m-d H:i:s', filemtime($filename.".".$type)))) return false;
		}
		return true;
	}

	// ������� ��������� �� ����������� ������������� �����
	function multiline ($text, $im, $dy, $dx1, $dx2, $font, $font_size, $color) {
		$width = imageSX($im)-$dx1-$dx2;
		// ��������� ��� ����� �� ������ ����
		$arr = explode(' ', $text);
		$ret = "";
		// ���������� ��� ������ ����
		foreach($arr as $word)	{
			// ��������� ������, ��������� � ��� �����
			$tmp_string = $ret.' '.$word;

			// ��������� ���������� ����� ����������� �����, �.�. ������ ��������� ������ 
			$textbox = imagettfbbox($font_size, 0, $font, $tmp_string);
			
			// ���� ��������� ������ �� ������������ � ������ ��� �������, �� ������ ������� ������, ����� ��������� ��� ���� �����
			if($textbox[2]-$textbox[0] > $width)
				$ret.=($ret==""?"":"\n").$word;
			else
				$ret.=($ret==""?"":" ").$word;
		}
		$ret=str_replace("\n", "|", $ret);
		$lines = explode('|', $ret);
		$cnt = count ($lines);

		// ��������� ���������� ����� ����������� �����, �.�. ������ ��������� ������ 
		$textbox = imagettfbbox($font_size, 0, $font, $ret);
		$height = abs($textbox[5] - $textbox[1]);
		if ($dy == 'middle') {	// ��������� - �� ������
			$y = (imageSY($im)+$cnt*$height)/2;
		} elseif ($dy >= 0)  {	// ������ - ������
			$y = $dy+$cnt*$height;
		} else {				// �������� ����� - �����
			$y = imageSY($im)+$dy;
		}

		// ����������� ����������� ������������� ����� �� �����������
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

	// ������� ������� ���������� ������ � �������� id � name �� �� ��������-�������� ��������
	//	$html = $this->clearanchor($html);
	function clearanchor($html) {
		$html = preg_replace_callback('/href\s*=\s*([\"\'])(.*?)(\1)/is',
		function ($match) {
			if($match[2][0] == '#') {	// ���������� ������
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
	// ������� ������� ��� ������� ������
	function removehref($html) {
		$html = preg_replace_callback('/href\s*=\s*([\"\'])(.*?)(\1)/is',
		function ($match) {
			if($match[2][0] == '#') {	// ���������� ������
				return 'href="'.$match[2].'"';
			} else return '';			// ������� ������� ������
		} ,$html);
		// ������� ������ ���� <a>
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
// ������� ��������� � ������ ������ ��������-�������� �������, 
// ������� �������, ���� + � ������ ������� �� _ 
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