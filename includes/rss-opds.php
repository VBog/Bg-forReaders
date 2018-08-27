<?php
/********************************************************

	Template Name: OPDS catalogue Template - Books
 
 ********************************************************/
add_action('init', 'bg_forreaders_opdsRSS');
function bg_forreaders_opdsRSS(){
	add_feed('opds', 'bg_forreaders_opdsRSSFunc');
}
/********************************************************
	Формируем xml файл 
 ********************************************************/
function bg_forreaders_opdsRSSFunc(){

	$include = array();
	$exclude = array();
	$ex_cats = explode ( ',', get_option('bg_forreaders_excat') );			// если запрещены некоторые категории
	$i = 0;
	foreach($ex_cats as $cat) {
		$idObj = get_category_by_slug(trim($cat)); 
		if ($idObj) {
			$exclude[$i] = $idObj->term_id;
			$i++;
		}
	}
	if (get_option('bg_forreaders_cats') != 'excluded') {
		$include = $exclude;
		$exclude = array();
	}
	// Заголовки
	header('Content-Type: '.feed_content_type('rss-http').'; charset='.get_option('blog_charset'), true);
	echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'" ?'.'>'.PHP_EOL; 
?>
<feed xml:lang="ru-RU" xmlns="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/">
	<title><?php _e('OPDS catalogue', 'bg-forreaders') ?> "<?php bloginfo('name'); ?>"</title>
	<link rel="search" title="<?php _e('Search', 'bg-forreaders') ?>" type="application/atom+xml" href="<?php echo OPDS_FEED; ?>?q={searchTerms}"/>
	<updated><?php echo date('c'); ?></updated>
	<author>
		<name><?php bloginfo('name'); ?></name>
		<uri><?php echo get_site_url(); ?></uri>
	</author>
<?php
	if (empty ($_GET)) {					// Стартовая страница: рубрики верхнего уровня
		bg_forreaders_the_folders(0, $include, $exclude);

	} elseif (isset($_GET['cat'])) {		// Подрубрики
		bg_forreaders_the_folders($_GET['cat'], $include, $exclude);

	} elseif (isset($_GET['offset'])) {	// По 10 последних файлов со смещением offset
		$posts = query_posts( array(
			'offset' => $_GET['offset'],
			'category__in' => $include,
			'category__not_in' => $exclude,
			'meta_key'=> 'for_readers',
			'orderby' => 'post_date',
			'order' => 'DESC',
			'posts_per_page' => 10
		));
		bg_forreaders_the_books();
		$next = $_GET['offset']+10;
?>
	<entry>
		<title><?php _e('Next', 'bg-forreaders') ?>  (<?php echo ($next+1)." - ".($next+10); ?>) >></title>
		<id>urn:<?php echo OPDS_NAME; ?>:next</id>
		<link href="<?php echo OPDS_FEED."?offset=". $next; ?>" type="application/atom+xml"/>
	</entry>	
<?php
	} elseif (isset($_GET['q'])) {			// Поисковый запрос
		global $wpdb;
		$postids = $wpdb->get_col("SELECT ID FROM wp_posts WHERE post_title LIKE '%".$_GET['q']."%' ");
		if (!empty($postids)) {
			$posts = query_posts( array(
				'post__in' => $postids,
				'category__in' => $include,
				'category__not_in' => $exclude,
				'posts_per_page' => -1
			));
			bg_forreaders_the_books();
		}
	}
?>
</feed>
<?php 
}

/********************************************************
	Вывод атома категории
 ********************************************************/
function bg_forreaders_the_folders($parent, $include, $exclude) {
	$categories = get_categories(array(
		'hide_empty' => '1',
		'parent' => $parent,
		'orderby' => 'id',
		'order' => 'ASC',
		'include' => implode(",", $include),
		'exclude' => implode(",", $exclude)
	));
	if ($parent == 0):
?>
	<entry>
		<title><?php _e('New', 'bg-forreaders') ?>  (10)</title>
		<id>urn:<?php echo OPDS_NAME; ?>:new</id>
		<link href="<?php echo OPDS_FEED; ?>?offset=0" type="application/atom+xml"/>
		<content type="text/html">(<?php _e('updated', 'bg-forreaders') ?>  <?php echo date('Y-m-d H:i'); ?>)</content>
	</entry>	
<?php
	endif;
	foreach( $categories as $category ):
?>
	<entry>
		<title><?php echo $category->name." (".$category->category_count.")"; ?></title>
		<id>urn:<?php echo OPDS_NAME; ?>:cat:<?php echo $category->term_id; ?></id>
		<link href="<?php echo OPDS_FEED."?cat=".$category->term_id; ?>" type="application/atom+xml"/>
		<content type="text/html"><?php echo strip_tags (html_entity_decode($category->description?$category->description:'---'),"<br>" ); ?></content>
	</entry>
<?php
	endforeach;
	if ($parent) {
		$posts = query_posts( array(
			'category__in' => $parent,
			'meta_key'=> 'for_readers',
			'posts_per_page' => -1
		));
		bg_forreaders_the_books();
	}
}
/********************************************************
	Вывод атома книги
 ********************************************************/
function bg_forreaders_the_books() {
	global $post;
	global $bg_forreaders_mimes;
	
	while(have_posts()) : the_post(); 
		$thumb_id = get_post_thumbnail_id();
		$thumb_url = wp_get_attachment_image_src($thumb_id,'full', true);
		$filename = BG_FORREADERS_STORAGE_URL."/".translit($post->post_name)."_".$post->ID;
		$filepath = BG_FORREADERS_STORAGE_URI."/".translit($post->post_name)."_".$post->ID;
		$shortname = BG_FORREADERS_STORAGE_URL."/".$post->ID;
		$shortpath = BG_FORREADERS_STORAGE_URI."/".$post->ID;
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
?>
	<entry>
		<id>urn:<?php echo OPDS_NAME; ?>:p:<?php the_ID(); ?></id>
		<title><?php the_title(); ?></title>
		<author>
		  <name><?php echo $author; ?></name>
		</author>
		<genre><?php echo $genre; ?></genre>
		<updated><?php the_date('c'); ?></updated>
		<content type="text/html"><?php echo strip_tags (html_entity_decode(get_the_excerpt()),"<br>" ); ?></content>
		<?php foreach ($bg_forreaders_mimes as $ext => $mime) :
			if (file_exists($filepath."p.".$ext)): ?>
		<link href="<?php echo $filename."p.".$ext; ?>" type="<?php echo $mime; ?>" />
			<?php elseif (file_exists($filepath.".".$ext)): ?>
		<link href="<?php echo $filename.".".$ext; ?>" type="<?php echo $mime; ?>" />
			<?php elseif (file_exists($shortpath.".".$ext)): ?>
		<link href="<?php echo $shortname.".".$ext; ?>" type="<?php echo $mime; ?>" />
			<?php endif; ?>
		<?php endforeach; ?>
		<?php if ($thumb_url): ?>
		<link rel="x-stanza-cover-image-thumbnail" href="<?php echo $thumb_url[0]; ?>" type="image/<?php echo substr(strrchr($thumb_url[0], '.'), 1); ?>"/> 
		<link rel="x-stanza-cover-image" href="<?php echo $thumb_url[0]; ?>" type="image/<?php echo substr(strrchr($thumb_url[0], '.'), 1); ?>"/> 
		<?php endif; ?>
		<link href="<?php the_permalink(); ?>" rel="alternate" type="text/html" title="<?php _e('Book on the site', 'bg-forreaders') ?>" />		 
	</entry>
<?php 
	endwhile; 
}
