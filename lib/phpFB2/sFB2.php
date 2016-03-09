<?php
/*****************************************************************************************
	sFB2 - простой PHP класс, преобразует HTML в формат FistonBook (fb2)
	Автор алгоритма: неизвестен
******************************************************************************************/
class sFB2
{
	public function prepare ($content, $options) {
		$arName = explode(" ", $options['author']);
		$contents = '<?xml version="1.0" encoding="UTF-8"?>
<FictionBook xmlns="http://www.gribuser.ru/xml/fictionbook/2.0" xmlns:l="http://www.w3.org/1999/xlink">
<stylesheet type="text/css">
	.body{font-family : Verdana, Geneva, Arial, Helvetica, sans-serif;}
	.p{margin:0.5em 0 0 0.3em; padding:0.2em; text-align:justify;}
</stylesheet>
<description>
	<title-info>
		<genre>religion</genre>
		<author><first-name>' . $arName[0] . ' '. $arName[1]. '</first-name>
				<last-name>' . $arName[0] . ' '. $arName[1]. '</last-name>
		</author>
		<book-title>' . $options['title'] . '</book-title>
		<lang>ru</lang>
	</title-info>
	<document-info>
		<id>4bef652469d2b65a5dfee7d5bf9a6d75-AAAA-' . md5($options['title']) . '</id>
		<author><nickname>' . $arName[0] . ' '. $arName[1]. '</nickname></author>
		<date xml:lang="ru">01.01.2014</date>
		<version>1</version>
	</document-info>
</description>
<body>
	<title><p>' . $options['title'] . '</p></title>
	<section>
';
		$contents .= preg_replace('#\<h\d+\>\<\!\-\-more\-\-\>\<\/h\d+\>#s', "", $content);
		//$contents = preg_replace('#\<\!\-\-more\-\-\>[\r\n\s]*#s', "<p>", $contents);
		$contents = preg_replace('#\<img style=\"height:26.*?gif\"\>#s', "<p>", $contents);
		$contents = preg_replace('#\<img src=\r?\n?\s?"(.*?)".*?\>#s', "<image l:href=\"$1\" />", $contents);
		$contents = str_replace(' class="bibref"', '', $contents);
		$contents = str_replace(' class="zam_link"', '', $contents);
		$contents = str_replace('</span>', '', $contents);
		$contents = preg_replace('#\<a\s*\t*\r?\n?href=\"[^\#](.*?)\".*?\>(.*?)\<\/a\>#si', "<a l:href=\"$1\">$2</a>", $contents);
		$contents = preg_replace('#\<span.*?\>#s', "", $contents);
		$contents = preg_replace('#\<hr.*?\>#s', "", $contents);
		$contents = preg_replace('#\s?\r?\n?title\s?\r?\n?=\s?\r?\n?\".*?\"#s', "", $contents);
		$contents = preg_replace('#\r\n\r\n\&nbsp\;\r\n\r\n#s', " ",  $contents);
		$contents = preg_replace('#\<h\d+.*?\>(.*?)\<\/h\d+\>#s', "</section><section><title><p>$1</p></title>",  $contents);
		$contents = preg_replace('#\<i\>#si', "<emphasis>",  $contents);
		$contents = preg_replace('#\<em\>#si', "<emphasis>",  $contents);
		$contents = preg_replace('#\</em\>#si', "</emphasis>",  $contents);
		$contents = preg_replace('#\</i\>#si', "</emphasis>",  $contents);
		$contents = preg_replace('#\<li.*?\>#s', "<p>",  $contents);
		$contents = preg_replace('#\<\/li\>#s', "</p>",  $contents);
		$contents = preg_replace('#\<ol.*?\>#s', '',  $contents);
		$contents = preg_replace('#\<ul.*?\>#s', '',  $contents);
		$contents = preg_replace('#\<\/ol\>#s', '',  $contents);
		$contents = preg_replace('#\<\/ul\>#s', '',  $contents);
		$contents = str_replace('<i>', '<em>',  $contents);
		$contents = str_replace('</i>', '</em>',  $contents);
		$contents = str_replace('<b>', '<strong>',  $contents);
		$contents = str_replace('</b>', '</strong>',  $contents);
		$contents = str_replace('<blockquote>', '<cite>',  $contents);
		$contents = str_replace('</blockquote>', '</cite>',  $contents);
		$contents = str_replace('href="#', 'l:href="#',  $contents);
		$contents = str_replace('name="', 'id="',  $contents);
		$contents = str_replace('name = "', 'id="',  $contents);
		$contents = str_replace('<sup>', '',  $contents);
		$contents = str_replace('</sup>', '',  $contents);
		$contents = str_replace('…', '...',  $contents);
		$contents = str_replace('&hellip;', '...',  $contents);
		$contents = str_replace('&ndash;', '–',  $contents);
		$contents = str_replace('&mdash;', '—',  $contents);
		$contents = str_replace('&nbsp;', ' ',  $contents);
		$contents = str_replace('&oacute;', 'о',  $contents);
		$contents = str_replace('<br clear=all>', '',  $contents);
		$contents = str_replace('<p><p>', '<p>',  $contents);
		$contents = str_replace('</p></p>', '</p>',  $contents);
		$contents = preg_replace('#(type\s?=\s?".*?")\s?type\s?=\s?".*?"#', '$1',$contents);
		$contents = preg_replace_callback("#(\<cite\>.*?\<\/cite\>)#s", 
			function($match){
				$match[0] = str_replace('</p><p>','',$match[0]);
				return $match[0];
			},  $contents);
		$contents .= '';
		return $contents;
	}
	public function save ($file, $content) {
		$put = file_put_contents($file, $content);
		return $put;
	}
}