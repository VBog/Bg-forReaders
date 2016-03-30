<?php
/******************************************************************************************
	Страница настроек плагина
	
*******************************************************************************************/
function bg_forreaders_options_page() {
	global $formats;
	
	bg_forreaders_add_options ();

	$active_tab = 'general';
	if( isset( $_GET[ 'tab' ] ) ) $active_tab = $_GET[ 'tab' ];
	?>
	<div class="wrap">
		<h2><?php _e('Plugin\'s &#171;For Readers&#187; settings', 'bg-forreaders') ?></h2>
		<div id="bg_forreaders_resalt"></div>
		<p><?php printf( __( 'Version', 'bg-forreaders' ).' <b>'.bg_forreaders_version().'</b>' ); ?></p>

		<h2 class="nav-tab-wrapper">
			<a href="?page=bg-forreaders%2Fbg-forreaders.php&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>"><?php _e('General', 'bg-forreaders') ?></a>
			<?php foreach ($formats as $type => $document_type) { ?>
				<a href="?page=bg-forreaders%2Fbg-forreaders.php&tab=<?php echo $type ?>" class="nav-tab <?php echo $active_tab == $type ? 'nav-tab-active' : ''; ?>"><?php echo $document_type ?></a>
			<?php } ?>
<!--			<a href="?page=bg-forreaders%2Fbg-forreaders.php&tab=html" class="nav-tab <?php echo $active_tab == 'html' ? 'nav-tab-active' : ''; ?>"><?php _e('Simple HTML', 'bg-forreaders') ?></a> -->
		</h2>

		<form id="bg_forreaders_options" method="post" action="options.php">
			<?php wp_nonce_field('update-options'); ?>

			<!-- Общие Настройки -->
			<?php if ($active_tab == 'general') { ?>

				<table class="form-table">

				<tr valign="top">
				<th scope="row"><?php _e('File types', 'bg-forreaders') ?></th>
				<td>
				<?php foreach ($formats as $type => $document_type) { ?>
					<input type="checkbox" name="bg_forreaders_<?php echo $type ?>" <?php if(get_option('bg_forreaders_'.$type)) echo "checked" ?> value="on" /> <?php  echo $document_type ?>&nbsp;&nbsp; 
				<?php } ?>
				</td>
				</tr>

				<tr valign="top">
				<th scope="row"><?php _e('Type of download links', 'bg-forreaders') ?></th>
				<td>
				<input type="radio" name="bg_forreaders_links" <?php if(get_option('bg_forreaders_links') == "php") echo "checked" ?> value="php" /> <?php _e('using download php-script', 'bg-forreaders') ?><br /> 
				<input type="radio" name="bg_forreaders_links" <?php if(get_option('bg_forreaders_links') == "html5") echo "checked" ?> value="html5" /> <?php _e('using html5 atribute "download"', 'bg-forreaders') ?><br /> 
				<input type="radio" name="bg_forreaders_links" <?php if(get_option('bg_forreaders_links') == "html") echo "checked" ?> value="html" /> <?php _e('simple html link', 'bg-forreaders') ?>
				</td>
				</tr>
				
				<tr valign="top">
				<th scope="row"><?php _e('Location of download links', 'bg-forreaders') ?></th>
				<td>
				<input type="checkbox" name="bg_forreaders_before" <?php if(get_option('bg_forreaders_before')) echo "checked" ?> value="on" /> <?php _e('before the text', 'bg-forreaders') ?><br /> 
				<input type="checkbox" name="bg_forreaders_after" <?php if(get_option('bg_forreaders_after')) echo "checked" ?> value="on" /> <?php _e('after the text', 'bg-forreaders') ?>
				</td>
				</tr>
				
				<tr valign="top">
				<th scope="row"><?php _e('Prompt to download', 'bg-forreaders') ?></th>
				<td>
				<input type="text" name="bg_forreaders_prompt" value="<?php echo get_option('bg_forreaders_prompt'); ?>" size="60" />
				</td>
				</tr>

				<tr valign="top">
				<th scope="row"><?php _e('Icon size', 'bg-forreaders') ?></th>
				<td>
				<input type="range" name="bg_forreaders_zoom" min="0" max="1" step="0.2" value="<?php echo get_option('bg_forreaders_zoom'); ?>" onchange="document.getElementById('bg_forreaders_zoom_value').innerHTML=100*this.value;" /> <span id="bg_forreaders_zoom_value"><?php echo (100*get_option('bg_forreaders_zoom')); ?></span>%</td>
				</tr>

				<tr valign="top">
				<th scope="row"><?php _e('Show icons on the single post only', 'bg-forreaders') ?></th>
				<td>
				<input type="checkbox" name="bg_forreaders_single" <?php if(get_option('bg_forreaders_single')) echo "checked" ?> value="on" /> 
				</td>
				</tr>
				
				<tr valign="top">
				<th scope="row"><?php _e('Exclude categories', 'bg-forreaders') ?></th>
				<td>
				<input type="text" name="bg_forreaders_excat" value="<?php echo get_option('bg_forreaders_excat'); ?>" size="60" /><br>
				<i><?php _e('(to exclude enter the category nicenames separated by commas)', 'bg-forreaders') ?></i>
				</td>
				</tr>

				<tr valign="top">
				<th scope="row"><?php _e('Custom field for author name', 'bg-forreaders') ?></th>
				<td>
				<input type="text" name="bg_forreaders_author_field" value="<?php echo get_option('bg_forreaders_author_field'); ?>" size="60" /><br>
				<i><?php _e('(if you specify as "post", author is post author)', 'bg-forreaders') ?></i>
				</td>
				</tr>

				<tr valign="top">
				<th scope="row"><?php _e('Genre', 'bg-forreaders') ?></th>
				<td>
				<input type="text" name="bg_forreaders_genre" value="<?php echo get_option('bg_forreaders_genre'); ?>" size="60" /><br>
				<i><?php _e('(if you specify as "genre", genre is content of custom fields "genre")', 'bg-forreaders') ?></i>
				</td>
				</tr>

				<tr valign="top">
				<th scope="row"><?php _e('When will created files for readers?', 'bg-forreaders') ?></th>
				<td>
				<input type="checkbox" name="bg_forreaders_while_displayed" <?php if(get_option('bg_forreaders_while_displayed')) echo "checked" ?> value="on" /> <?php _e('while current post is displayed', 'bg-forreaders') ?><br /> 
				<input type="checkbox" name="bg_forreaders_while_saved" <?php if(get_option('bg_forreaders_while_saved')) echo "checked" ?> value="on" /> <?php _e('while current post is saved', 'bg-forreaders') ?>
				</td>
				</tr>
				
				<tr valign="top">
				<th scope="row"><?php _e('Time limit', 'bg-forreaders') ?></th>
				<td>
				<input type="number" name="bg_forreaders_time_limit" value="<?php echo get_option('bg_forreaders_time_limit'); ?>" min="0" /> <?php _e('sec.', 'bg-forreaders') ?>
				</td>
				</tr>

				<tr valign="top">
				<th scope="row"><?php _e('Batch mode', 'bg-forreaders') ?></th>
				<td>
				<?php printf (__('You can use script %s<br>to generate files for readers in batch mode. ', 'bg-forreaders'), '<span style="background: gray; color: white">'.plugins_url( 'forreaders.php', dirname(__FILE__) ).'</span>') ?>
				</td>
				</tr>

				</table>

				<input type="hidden" name="action" value="update" />
				<input type="hidden" name="page_options" value="bg_forreaders_pdf, bg_forreaders_epub, bg_forreaders_mobi,	bg_forreaders_fb2, 
							bg_forreaders_links, bg_forreaders_before, bg_forreaders_after, bg_forreaders_prompt, bg_forreaders_zoom, 
							bg_forreaders_single, bg_forreaders_excat, bg_forreaders_author_field, bg_forreaders_genre, 
							bg_forreaders_while_displayed, bg_forreaders_while_saved, bg_forreaders_time_limit" />

				<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
				</p>

				<!-- Файл PDF -->
			<?php } elseif ($active_tab == 'pdf') { ?>
				<table class="form-table">

				<tr valign="top">
				<th scope="row"><?php _e('CSS styles table for PDF', 'bg-forreaders') ?></th>
				<td>
				<textarea name="bg_forreaders_pdf_css" rows="10" cols="60"><?php echo get_option('bg_forreaders_pdf_css'); ?></textarea><br>
				<i><?php _e('Enter the css styling table for display text in PDF-reader.', 'bg-forreaders') ?></i>
				</td>
				</tr>

				<tr valign="top">
				<th scope="row"><?php _e('Allowed tags and attributes', 'bg-forreaders') ?></th>
				<td>
				<textarea name="bg_forreaders_pdf_tags" rows="10" cols="60"><?php echo get_option('bg_forreaders_pdf_tags'); ?></textarea><br>
				<i><?php _e('Enter the allowed tags separated by commas.', 'bg-forreaders') ?></i><br>
				<i><?php _e('Near in brackets enter the allowed attributes separated by vertical bar.', 'bg-forreaders') ?></i><br>
				<i><?php _e('(For example, a[href|name|id],b,strong,i,em,u)', 'bg-forreaders') ?></i>
				</td>
				</tr>

				<tr valign="top">
				<th scope="row"><?php _e('External links', 'bg-forreaders') ?></th>
				<td>
				<input type="checkbox" name="bg_forreaders_pdf_extlinks" <?php if(get_option('bg_forreaders_pdf_extlinks')) echo "checked" ?> value="on" />
				</td>
				</tr>
				
				</table>

				<input type="hidden" name="action" value="update" />
				<input type="hidden" name="page_options" value="bg_forreaders_pdf_css, bg_forreaders_pdf_tags, bg_forreaders_pdf_extlinks" />

				<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
				</p>
					
				<!-- Файл ePub -->
			<?php } elseif ($active_tab == 'epub') { ?>
				<table class="form-table">

				<tr valign="top">
				<th scope="row"><?php _e('CSS styles table for ePub', 'bg-forreaders') ?></th>
				<td>
				<textarea name="bg_forreaders_epub_css" rows="10" cols="60"><?php echo get_option('bg_forreaders_epub_css'); ?></textarea><br>
				<i><?php _e('Enter the css styling table for display text in ePub-reader.', 'bg-forreaders') ?></i>
				</td>
				</tr>

				<tr valign="top">
				<th scope="row"><?php _e('Allowed tags and attributes', 'bg-forreaders') ?></th>
				<td>
				<textarea name="bg_forreaders_epub_tags" rows="10" cols="60"><?php echo get_option('bg_forreaders_epub_tags'); ?></textarea><br>
				<i><?php _e('Enter the allowed tags separated by commas.', 'bg-forreaders') ?></i><br>
				<i><?php _e('Near in brackets enter the allowed attributes separated by vertical bar.', 'bg-forreaders') ?></i><br>
				<i><?php _e('(For example, a[href|name|id],b,strong,i,em,u)', 'bg-forreaders') ?></i>
				</td>
				</tr>

				<tr valign="top">
				<th scope="row"><?php _e('External links', 'bg-forreaders') ?></th>
				<td>
				<input type="checkbox" name="bg_forreaders_epub_extlinks" <?php if(get_option('bg_forreaders_epub_extlinks')) echo "checked" ?> value="on" />
				</td>
				</tr>

				</table>

				<input type="hidden" name="action" value="update" />
				<input type="hidden" name="page_options" value="bg_forreaders_epub_css, bg_forreaders_epub_tags, bg_forreaders_epub_extlinks" />

				<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
				</p>
			<!-- Файл mobi -->
			<?php } elseif ($active_tab == 'mobi') { ?>
				<table class="form-table">

				<tr valign="top">
				<th scope="row"><?php _e('CSS styles table for mobi', 'bg-forreaders') ?></th>
				<td>
				<textarea name="bg_forreaders_mobi_css" rows="10" cols="60"><?php echo get_option('bg_forreaders_mobi_css'); ?></textarea><br>
				<i><?php _e('Enter the css styling table for display text in mobi-reader.', 'bg-forreaders') ?></i>
				</td>
				</tr>

				<tr valign="top">
				<th scope="row"><?php _e('Allowed tags and attributes', 'bg-forreaders') ?></th>
				<td>
				<textarea name="bg_forreaders_mobi_tags" rows="10" cols="60"><?php echo get_option('bg_forreaders_mobi_tags'); ?></textarea><br>
				<i><?php _e('Enter the allowed tags separated by commas.', 'bg-forreaders') ?></i><br>
				<i><?php _e('Near in brackets enter the allowed attributes separated by vertical bar.', 'bg-forreaders') ?></i><br>
				<i><?php _e('(For example, a[href|name|id],b,strong,i,em,u)', 'bg-forreaders') ?></i>
				</td>
				</tr>

				<tr valign="top">
				<th scope="row"><?php _e('External links', 'bg-forreaders') ?></th>
				<td>
				<input type="checkbox" name="bg_forreaders_mobi_extlinks" <?php if(get_option('bg_forreaders_mobi_extlinks')) echo "checked" ?> value="on" />
				</td>
				</tr>

				</table>

				<input type="hidden" name="action" value="update" />
				<input type="hidden" name="page_options" value="bg_forreaders_mobi_css, bg_forreaders_mobi_tags, bg_forreaders_mobi_extlinks" />

				<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
				</p>

			<!-- Файл fb2 -->
			<?php } elseif ($active_tab == 'fb2') { ?>
				<table class="form-table">

				<tr valign="top">
				<th scope="row"><?php _e('CSS styles table for Fiction Books', 'bg-forreaders') ?></th>
				<td>
				<textarea name="bg_forreaders_fb2_css" rows="10" cols="60"><?php echo get_option('bg_forreaders_fb2_css'); ?></textarea><br>
				<i><?php _e('Enter the css styling table for display text in fb2-reader.', 'bg-forreaders') ?></i>
				</td>
				</tr>

				<tr valign="top">
				<th scope="row"><?php _e('Allowed tags and attributes', 'bg-forreaders') ?></th>
				<td>
				<textarea name="bg_forreaders_fb2_tags" rows="10" cols="60"><?php echo get_option('bg_forreaders_fb2_tags'); ?></textarea><br>
				<i><?php _e('Enter the allowed tags separated by commas.', 'bg-forreaders') ?></i><br>
				<i><?php _e('Near in brackets enter the allowed attributes separated by vertical bar.', 'bg-forreaders') ?></i><br>
				<i><?php _e('(For example, a[href|name|id],b,strong,i,em,u)', 'bg-forreaders') ?></i>
				</td>
				</tr>

				<tr valign="top">
				<th scope="row"><?php _e('Allowed html-entities', 'bg-forreaders') ?></th>
				<td>
				<textarea name="bg_forreaders_fb2_entities" rows="10" cols="60"><?php echo htmlentities( get_option('bg_forreaders_fb2_entities') ); ?></textarea><br>
				<i><?php _e('Enter the allowed html-entities separated by commas.', 'bg-forreaders') ?></i><br>
				<i><?php _e('Near in brackets enter the any symbols for replaced it in text.', 'bg-forreaders') ?></i><br>
				<i><?php _e('(For example, &amp;quot;,&amp;nbsp;[ ],&amp;hellip;[...])', 'bg-forreaders') ?></i>
				</td>
				</tr>

				<tr valign="top">
				<th scope="row"><?php _e('External links', 'bg-forreaders') ?></th>
				<td>
				<input type="checkbox" name="bg_forreaders_fb2_extlinks" <?php if(get_option('bg_forreaders_fb2_extlinks')) echo "checked" ?> value="on" />
				</td>
				</tr>

				</table>

				<input type="hidden" name="action" value="update" />
				<input type="hidden" name="page_options" value="bg_forreaders_fb2_css, bg_forreaders_fb2_tags, bg_forreaders_fb2_entities, bg_forreaders_fb2_extlinks" />

				<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
				</p>

				<!-- Подготовка HTML -->
			<?php } elseif ($active_tab == 'html') { ?>
				<table class="form-table">

				<tr valign="top">
				<th scope="row"><?php _e('Allowed tags and attributes', 'bg-forreaders') ?></th>
				<td>
				<textarea name="bg_forreaders_allowed_tags" rows="10" cols="60"><?php echo get_option('bg_forreaders_allowed_tags'); ?></textarea><br>
				<i><?php _e('Enter the allowed tags separated by commas.', 'bg-forreaders') ?></i><br>
				<i><?php _e('Near in brackets enter the allowed attributes separated by vertical bar.', 'bg-forreaders') ?></i><br>
				<i><?php _e('(For example, a[href|name|id],b,strong,i,em,u)', 'bg-forreaders') ?></i>
				</td>
				</tr>
				</table>

				<input type="hidden" name="action" value="update" />
				<input type="hidden" name="page_options" value="bg_forreaders_allowed_tags" />

				<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
				</p>
			<?php } ?>
			
		</form>
	</div>
	<?php 
}
