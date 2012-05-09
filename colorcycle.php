<?php
/*
Plugin Name: ColorCycle
Plugin URI: http://jacksonwhelan.com/plugins/colorcycle/
Description: ColorCycle adds Colorbox for image enlargements, and creates slideshows of attached images using the Cycle plugin for jQuery.
Author: Jackson
Version: 1.4
Author URI: http://jacksonwhelan.com
*/

$ColorCycle = new ColorCycle;

class ColorCycle {

	function install() {
		// Nothing here yet...
    }
    
    function uninstall() {
		// Nothing here yet...
    }
    
    function ColorCycle() {
    	add_action( 'init', array( &$this, 'register_scripts' ) );
    	add_action( 'wp_footer', array( &$this, 'print_scripts') );
    	add_action( 'wp_print_styles', array( &$this, 'add_stylesheets') );
		add_filter( 'attachment_fields_to_edit', array( &$this, 'image_attachment_fields_to_edit' ), 9, 2 );
		add_filter( 'attachment_fields_to_save', array( &$this, 'image_attachment_fields_to_save' ), 9, 2 );    	
		add_filter( 'post_gallery', array( &$this, 'gallery_sc' ), 10, 2 );
		add_filter( 'the_content', array( &$this, 'content_filter' ), 99 );
		add_shortcode( 'slideshow', array( &$this, 'colorcycle_show' ) );
    }
 
	function register_scripts() {
		wp_register_script('cycle', plugins_url( 'colorcycle/jquery.cycle/jquery.cycle.all.min.js' ), array('jquery'), '2.9999.5', true);
		wp_register_script('colorbox', plugins_url( 'colorcycle/colorbox/jquery.colorbox-min.js' ), array('jquery' ), '1.3.19', true);
		wp_register_script('colorcycle', plugins_url( 'colorcycle/colorcycle.js' ), array('jquery', 'colorbox', 'cycle' ), '1.4', true);
	}
	 
	function print_scripts() {
		global $add_cc_scripts, $cc_localize;
	 
		if ( ! $add_cc_scripts )
			return;
	 	
	 	$data = array( 
			'pause' => 0,
			'speed' => 2500,
			'delay' => 1000,
			'timeout' => 1000,
			'thumbs' => false,
			'fx' => 'fade'
		);
		
		if( is_array( $cc_localize ) ) {
			$data = wp_parse_args( $cc_localize, $data );		
		}
		
		wp_localize_script( 'colorcycle', 'color_cycle', $data );
		
		wp_print_scripts( array( 'colorbox', 'cycle', 'colorcycle' ) );
	}
	
	function add_stylesheets() {
        wp_register_style( 'colorbox-css', plugins_url( 'colorcycle/colorbox/colorbox.css' ) );
        wp_register_style( 'color-cycle-css', plugins_url( 'colorcycle/colorcycle.css' ) );	
		wp_enqueue_style( 'colorbox-css' );
        wp_enqueue_style( 'color-cycle-css' );
	}
	
	function image_attachment_fields_to_edit($form_fields, $post) {
		if(get_post_meta($post->ID, "_jw_cc_ss_img", true) == 'yes') {
			$selected = " selected='selected'";
		} else {
			$selected = "";
		}
		$form_fields["jw_cc_ss_img"]["label"] = __("Show in Slideshow?");  
		$form_fields["jw_cc_ss_img"]["input"] = "html";  
		$form_fields["jw_cc_ss_img"]["html"] = "<select name='attachments[{$post->ID}][jw_cc_ss_img]' id='attachments[{$post->ID}][jw_cc_ss_img]'> 
		<option value='no'>No</option> 
		<option value='yes'".$selected.">Yes</option> 
		</select>"; 
		if(get_post_meta($post->ID, "_jw_cc_ss_href", true) != '') {
			$value = get_post_meta($post->ID, "_jw_cc_ss_href", true);
		} else {
			$value = "";
		}
		$form_fields["jw_cc_ss_href"]["label"] = __("Slideshow Link");  
		$form_fields["jw_cc_ss_href"]["input"] = "html";  
		$form_fields["jw_cc_ss_href"]["html"] = "<input name='attachments[{$post->ID}][jw_cc_ss_href]' id='attachments[{$post->ID}][jw_cc_ss_href]' value='$value' type='text' />";
		if(get_post_meta($post->ID, "_jw_cc_ss_group", true) != '') {
			$value = get_post_meta($post->ID, "_jw_cc_ss_group", true);
		} else {
			$value = "";
		}
		$form_fields["jw_cc_ss_group"]["label"] = __("Slideshow Group");  
		$form_fields["jw_cc_ss_group"]["input"] = "html";  
		$form_fields["jw_cc_ss_group"]["html"] = "<input name='attachments[{$post->ID}][jw_cc_ss_group]' id='attachments[{$post->ID}][jw_cc_ss_group]' value='$value' type='text' />";  
		return $form_fields;
	}
	
	function image_attachment_fields_to_save($post, $attachment) {
		if( isset( $attachment['jw_cc_ss_img'] ) ) {
			update_post_meta( $post['ID'], '_jw_cc_ss_img', $attachment['jw_cc_ss_img'] );
		}
		if( isset( $attachment['jw_cc_ss_href'] ) && $attachment['jw_cc_ss_href'] != '' ) {
			update_post_meta( $post['ID'], '_jw_cc_ss_href', $attachment['jw_cc_ss_href'] );
		}
		if( isset( $attachment['jw_cc_ss_group'] ) && $attachment['jw_cc_ss_group'] != '' ) {
			update_post_meta( $post['ID'], '_jw_cc_ss_group', $attachment['jw_cc_ss_group'] );
		}
		return $post;
	}
	
	function gallery_sc( $content, $attr ) {
		
		global $post, $add_cc_scripts;
		
		$add_cc_scripts = true;
		
		static $instance = 0;
		$instance++;
	
		if ( isset( $attr['orderby'] ) ) {
			$attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
			if ( !$attr['orderby'] )
				unset( $attr['orderby'] );
		}
	
		extract( shortcode_atts( array(
			'order' => 'ASC',
			'orderby' => 'menu_order ID',
			'id' => $post->ID,
			'itemtag' => 'dl',
			'icontag' => 'dt',
			'captiontag' => 'dd',
			'columns' => 3,
			'size' => 'thumbnail',
			'group' => false
		), $attr ) );
	
		$id = intval($id);
		
		if( !empty( $group ) ) {
			$args = array(
				'post_parent' => $id,
				'post_status' => 'inherit',
				'post_type' => 'attachment',
				'post_mime_type' => 'image',
				'order' => $order,
				'orderby' => $orderby,
				'meta_key' => '_jw_cc_ss_group',
				'meta_value' => $group			
			);
		} else {
			$args = array(
				'post_parent' => $id,
				'post_status' => 'inherit',
				'post_type' => 'attachment',
				'post_mime_type' => 'image',
				'order' => $order,
				'orderby' => $orderby
			);		
		}
		
		$attachments = get_children( $args );
	
		if ( empty( $attachments ) )
			return '';
	
		if ( is_feed() ) {
			$output = "\n";
			foreach ( $attachments as $att_id => $attachment )
				$output .= wp_get_attachment_link($att_id, $size, true) . "\n";
			return $output;
		}
	
		$itemtag = tag_escape($itemtag);
		$captiontag = tag_escape($captiontag);
		$columns = intval($columns);
		$itemwidth = $columns > 0 ? floor(100/$columns) : 100;
	
		$selector = "gallery-{$instance}";
	
		$output = "<!-- colorcycle_gallery_shortcode() -->
			<div id='$selector' class='gallery galleryid-{$id}'>";
	
		$i = 0;
		foreach ( $attachments as $id => $attachment ) {
			$link = wp_get_attachment_link($id, $size, false, false);
			
			$output .= "<{$itemtag} class='gallery-item' style='width:{$itemwidth}%;'>";
			$output .= "
				<{$icontag} class='gallery-icon'>
					$link
				</{$icontag}>";
			if ( $captiontag && trim($attachment->post_excerpt) ) {
				$output .= "
					<{$captiontag} class='gallery-caption'>
					" . wptexturize($attachment->post_excerpt) . "
					</{$captiontag}>";
			}
			$output .= "</{$itemtag}>";
			if ( $columns > 0 && ++$i % $columns == 0 )
				$output .= '<br style="clear: both" />';
		}
	
		$output .= "
				<br style='clear: both;' />
			</div>\n";
	
		return $output;
	}
		
	function content_filter($content) {
		global $post, $add_cc_scripts;
		
		$add_cc_scripts = true;
		
		$pattern[0] = "/(<a)([^\>]*?) href=('|\")([A-Za-z0-9\/_\.\~\:-]*?)(\.bmp|\.gif|\.jpg|\.jpeg|\.png)('|\")([^\>]*?)>(.*?)<\/a>/i";
		$replacement[0]	= '$1 href=$3$4$5$6$2$7>$8</a>';
		$pattern[1] = "/(<a href=)('|\")([A-Za-z0-9\/_\.\~\:-]*?)(\.bmp|\.gif|\.jpg|\.jpeg|\.png)('|\")([^\>]*?)(>)(.*?)(<\/a>)/i";
		$replacement[1]	= '$1$2$3$4$5 class="colorbox" $6$7$8$9';
		$pattern[3] = "/(<a href=)('|\")([A-Za-z0-9\/_\.\~\:-]*?)(\.bmp|\.gif|\.jpg|\.jpeg|\.png)('|\")([^\>]*?)(>)(.*?) title=('|\")(.*?)('|\")(.*?)(<\/a>)/i";
		$replacement[3]	= '$1$2$3$4$5$6 title=$9$10$11$7$8 title=$9$10$11$12$13';
		$pattern[4]	= "/(<a href=)('|\")([A-Za-z0-9\/_\.\~\:-]*?)(\.bmp|\.gif|\.jpg|\.jpeg|\.png)('|\")([^\>]*?) title=([^\>]*?) title=([^\>]*?)(>)(.*?)(<\/a>)/i";
		$replacement[4]	= '$1$2$3$4$5$6 title=$7$9$10$11';

		$content = preg_replace($pattern, $replacement, $content);
		return $content;
		
	}
	
	function colorcycle_show( $atts ) {
		
		global $post, $add_cc_scripts, $cc_localize, $wptouch_pro, $facebook_app;
		$add_cc_scripts = true;
		
		static $cc_instance = 0;
		$cc_instance++;
		
		if ( isset( $attr['orderby'] ) ) {
			$attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
			if ( !$attr['orderby'] )
				unset( $attr['orderby'] );
		}
		
		extract( shortcode_atts( array(
			'id' => $post->ID,
			'size' => 'large',
			'fullsize' => 'full',
			'thumbsize' => array(50,50),
			'show' => 'selected',
			'showthumbs' => false,
			'linkto' => 'large',
			'pager' => false,
			'per_page' => 12,
			'speed' => 2000,
			'pause' => 0,
			'delay' => 1000,
			'fx' => 'fade',
			'group' => false,
			'orderby' => 'menu_order',
			'order' => 'ASC'
		), $atts ) );
		
		if( ( is_object( $wptouch_pro ) && $wptouch_pro->showing_mobile_theme ) || $facebook_app) {
			$size = 'medium';
			$linkto = 'none';
		}
		
		$cc_localize['speed'] = $speed;
		$cc_localize['pause'] = $pause;
		$cc_localize['delay'] = $delay;
		$cc_localize['fx'] = $fx;
		$cc_localize['thumbs'] = $showthumbs; 
		 
		if( !empty( $group ) ) {
			$args = array( 
				'post_type' => 'attachment', 
				'post_mime_type' => 'image',
				'orderby' => $orderby,
				'order' => $order,
				'meta_key' => '_jw_cc_ss_group', 
				'meta_value' => $group,
				'post_parent' => $post->ID
			);
		} elseif( $show == 'all' ) {
			$args = array( 
				'post_type' => 'attachment', 
				'post_mime_type' => 'image',
				'orderby' => $orderby,
				'order' => $order,
				'post_parent' => $post->ID
			);
		} else {
			$args = array( 
				'post_type' => 'attachment', 
				'post_mime_type' => 'image',
				'orderby' => $orderby,
				'order' => $order,
				'meta_key' => '_jw_cc_ss_img', 
				'meta_value' => 'yes',
				'post_parent' => $post->ID
			);
		}
		
		$attachments =& get_children( $args );
		
		if($attachments) {
			$out = '<div class="jw-colorcycle-wrap"><div id="jw-colorcycle-' . $post->ID . '-' . $cc_instance . '" class="jw-colorcycle">';
		} else {
			$out = null;
			return($out);
			exit;
		}
		
		$count = 1;
		$slides = '';
		$thumbs = '';
		$height = 0;
		
		foreach( $attachments as $attachment => $attachment_array ) {
		
			if( $showthumbs )
				$thumbimage = wp_get_attachment_image_src( $attachment, $thumbsize, false );
			$slideimage = wp_get_attachment_image_src( $attachment, $size, false );
			$fullimage = wp_get_attachment_image_src( $attachment, $fullsize, false );
			$image = get_post( $attachment );
			$image_title = $image->post_title;
			$image_title_attr = esc_attr( strip_tags( $image_title ) );
			$image_caption = $image->post_excerpt;
			$image_description = $image->post_content;
			if( $image_caption != '' ) {
				$caption = '<div class="slide-cap">' . $image_caption . '</div>';
			} else {
				$caption = '';
			}
			if( $linkto == 'none' ) { 
			
				$out.= '<div class="slide" id="i' . $count . '"><span class="jw-cc-img-wrap"><img src="' . $slideimage[0] . '" width="' . $slideimage[1] . '" height="' . $slideimage[2] . '" alt="' . $image_title_attr . '" />' . $caption . '</span></div>';
			
			} elseif( $linkto == 'large' || get_post_meta( $image->ID, "_jw_cc_ss_href", true ) == '' ) {
			
				$out.= '<div class="slide" id="i'.$count.'"><a href="'.$fullimage[0].'" class="colorbox jw-cc-img-wrap"><img src="'.$slideimage[0] . '" width="'.$slideimage[1].'" height="'.$slideimage[2].'" alt="' . $image_title_attr . '" /></a>' . $caption . '</div>';
				
			} elseif( $linkto == 'url' ) { 
			
				$out.= '<div class="slide" id="i' . $count . '"><a class="jw-cc-img-wrap" href="' . get_post_meta($image->ID, "_jw_cc_ss_href", true ) . '"><img src="' . $slideimage[0] . '" width="' . $slideimage[1] . '" height="' . $slideimage[2] . '" alt="' . $image_title_attr . '" /></a>' . $caption . '</div>';
				
			} if( $showthumbs ) {
			
				$thumbs.= '<li class="colorcycle-thumb"><a href="#jw-colorcycle-' . $post->ID . '-' . $cc_instance . '" id="goto' . $count . '" class="slides' . $post->ID . '"><img src="' . $thumbimage[0] . '" width="' . $thumbimage[1] . '" height="' . $thumbimage[2] . '" alt="Thumbnail: ' . $image_title_attr . '" /></a></li>';
				
			}			
			
			$count++;

		}
		
		$out.= '</div>';

		if( $pager ) {
		
			$out.= '<div class="jw-colorcycle-pager"><a href="#jw-colorcycle-' . $post->ID . '-' . $cc_instance . '" class="jw-cc-prev">Previous</a> <span class="jw-cc-pages"> </span> <a href="#jw-colorcycle-' . $post->ID . '-' . $cc_instance . '" class="jw-cc-prev">Next</a></div>';
			
		}

		$out.= '</div>';
		
		if( $showthumbs ) {
		
			$out.= '<div class="jw-colorcycle-thumbs"><ul class="jw-colorcycle-thumb">'.$thumbs.'</ul></div>';
			
		}
				
		return $out;
		
	}

}

register_activation_hook( __FILE__, array( 'ColorCycle', 'install' ) );
register_deactivation_hook( __FILE__, array( 'ColorCycle', 'uninstall' ) );