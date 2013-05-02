<?php
/*
Plugin Name: ColorCycle
Plugin URI: http://colorcycle.jacksonwhelan.com
Description: ColorCycle adds Colorbox for image enlargements, and creates slideshows of attached images using the Cycle plugin for jQuery.
Author: Jackson
Version: 1.5.2
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
    	// Scripts and styles
    	add_action( 'init', array( &$this, 'register_scripts' ) );
    	add_action( 'admin_init', array( &$this, 'register_admin_style' ) );
    	add_action( 'wp_footer', array( &$this, 'print_scripts') );
    	add_action( 'wp_print_styles', array( &$this, 'add_stylesheets') );
    	
    	// Media
		add_filter( 'attachment_fields_to_edit', array( &$this, 'image_attachment_fields_to_edit' ), 100, 2 );
		add_filter( 'attachment_fields_to_save', array( &$this, 'image_attachment_fields_to_save' ), 9, 2 );    
		add_action( 'wp_ajax_save-attachment-compat', array( &$this, 'image_attachment_fields_to_save_ajax' ), 0, 1);
		add_action( 'admin_footer-post-new.php', array( &$this, 'show_post_attachments' ) );
		add_action( 'admin_footer-post.php', array( &$this, 'show_post_attachments' ) );
		//add_action( 'add_meta_boxes', array( &$this, 'add_meta_boxes' ) );
		//add_action( 'save_post', array( &$this, 'cc_save_postdata' ) );
		add_filter( 'media_upload_tabs', array( &$this, 'cc_slideshow_tab' ) );
		add_action( 'media_upload_cc_slideshow', array( &$this, 'cc_slideshow_tab_iframe' ) );
			
		// Ouput
		add_filter( 'post_gallery', array( &$this, 'gallery_sc' ), 10, 2 );
		add_filter( 'the_content', array( &$this, 'content_filter' ), 99 );
		add_shortcode( 'slideshow', array( &$this, 'colorcycle_show' ) );
		add_shortcode( 'ccgallery', array( &$this, 'colorcycle_gallery' ) );
		add_shortcode( 'ccgalleries', array( &$this, 'colorcycle_index' ) );
    }
  
	function register_scripts() {
		wp_register_script('cycle', plugins_url( 'colorcycle/jquery.cycle/jquery.cycle.all.min.js' ), array('jquery'), '2.9999.5', true);
		wp_register_script('colorbox', plugins_url( 'colorcycle/colorbox/jquery.colorbox-min.js' ), array('jquery' ), '1.3.23', true);
		wp_register_script('colorcycle', plugins_url( 'colorcycle/colorcycle.js' ), array('jquery', 'colorbox', 'cycle' ), '1.4', true);
	}
	
	function register_admin_style() {
		wp_register_style( 'colorcycle-admin-css', plugins_url( 'colorcycle-admin.css', __FILE__ ) );
		wp_enqueue_style( 'colorcycle-admin-css' );
	}
	 
	function print_scripts() {
		global $add_cc_scripts, $cc_localize;
	 
		if ( !$add_cc_scripts )
			return;
	 	
	 	$data = array( 
			'pause' => 0,
			'speed' => 2500,
			'delay' => 1000,
			'timeout' => 1000,
			'thumbs' => false,
			'forcevertical' => false,
			'fx' => 'fade',
			'cb_opts' => array(			
				'transition' => "elastic",
				'width' => "90%",
				'initialWidth' => "600",
				'innerWidth' => false,
				'maxWidth' => "100%",
				'height' => "90%",
				'initialHeight' => "450",
				'innerHeight' => false,
				'maxHeight' => "100%",
				'scalePhotos' => true,
				'scrolling' => true,
				'inline' => false,
				'html' => false,
				'iframe' => false,
				'fastIframe' => true,
				'photo' => false,
				'href' => false,
				'title' => false,
				'rel' => false,
				'opacity' => 0.9,
				'current' => __( "Image {current} of {total}", 'colorcycle' ),
				'previous' => __( "Previous", 'colorcycle' ),
				'next' => __( "Next", 'colorcycle' ),
				'close' => __( "Close", 'colorcycle' ),
				'slideshow' => false,
				'slideshowAuto' => false,
				'slideshowSpeed' => 10000,
				'slideshowStart' => __( "Start Slideshow", 'colorcycle' ),
				'slideshowStop' => __( "Stop Slideshow", 'colorcycle' ),
			)
		);
		
		if( is_array( $cc_localize ) ) {
			$data = wp_parse_args( $cc_localize, $data );		
		}
			
		wp_localize_script( 'colorcycle', 'color_cycle', $data );
		
		wp_print_scripts( array( 'colorbox', 'cycle', 'colorcycle' ) );
	}
	
	function add_stylesheets() {
		$theme = apply_filters( 'cc_cb_theme', 'default' );
        wp_register_style( 'colorbox-css', plugins_url( 'colorcycle/colorbox/themes/' . $theme . '/colorbox.css' ) );
        wp_register_style( 'color-cycle-css', plugins_url( 'colorcycle/colorcycle.css' ) );	
		wp_enqueue_style( 'colorbox-css' );
        wp_enqueue_style( 'color-cycle-css' );
	}
	
	function image_attachment_fields_to_edit( $form_fields, $post ) {
						
		$form_fields["jw_cc_header"]['tr'] = '<tr><td colspan="2"><h3>ColorCycle Options</h3></td></tr>';

		$noselected = $selected = '';
		
		if( get_post_meta( $post->ID, "_jw_cc_ss_img", true ) == 'yes' ) {
			$selected = " selected='selected'";
		} else {
			$noselected = " selected='selected'";
		}
		
		$form_fields["jw_cc_ss_img"]["label"] = __( "Show Image?" );  
		$form_fields["jw_cc_ss_img"]["input"] = "html";  
		$form_fields["jw_cc_ss_img"]["html"] = "<select name='attachments[{$post->ID}][jw_cc_ss_img]' id='attachments[{$post->ID}][jw_cc_ss_img]'> 
		<option value='no'".$noselected.">No</option> 
		<option value='yes'".$selected.">Yes</option> 
		</select>";
		$form_fields["jw_cc_ss_img"]["helps"] = __( "Show image in galleries and slideshows." );
		
		if(get_post_meta($post->ID, "_jw_cc_ss_href", true) != '') {
			$value = get_post_meta($post->ID, "_jw_cc_ss_href", true);
		} else {
			$value = "";
		}
		$form_fields["jw_cc_ss_href"]["label"] = __( "Link To" );  
		$form_fields["jw_cc_ss_href"]["input"] = "html";  
		$form_fields["jw_cc_ss_href"]["html"] = "<input name='attachments[{$post->ID}][jw_cc_ss_href]' id='attachments[{$post->ID}][jw_cc_ss_href]' value='$value' type='text' />";
		$form_fields["jw_cc_ss_href"]["helps"] = __( "Alternate URL for this image to link to." );
		
		if( apply_filters( 'cc_ss_group_enable', false ) ) {
		
			if(get_post_meta($post->ID, "_jw_cc_ss_group", true) != '') {
				$value = get_post_meta($post->ID, "_jw_cc_ss_group", true);
			} else {
				$value = "";
			}
			$form_fields["jw_cc_ss_group"]["label"] = __("Grouping");  
			$form_fields["jw_cc_ss_group"]["input"] = "html";  
			$form_fields["jw_cc_ss_group"]["html"] = "<input name='attachments[{$post->ID}][jw_cc_ss_group]' id='attachments[{$post->ID}][jw_cc_ss_group]' value='$value' type='text' />";  
			$form_fields["jw_cc_ss_group"]["helps"] = __( "Optional - Assign arbitrary groups to control which images are shown." );
		
		}
				
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
	
	function image_attachment_fields_to_save_ajax() {
		$post_id = $_POST['id'];
	  
		if( isset( $_POST['attachments'][$post_id]['jw_cc_ss_img'] ) ) {
			update_post_meta( $post_id, '_jw_cc_ss_img', $_POST['attachments'][$post_id]['jw_cc_ss_img'] );
		}
		if( isset( $_POST['attachments'][$post_id]['jw_cc_ss_href'] ) ) {
			update_post_meta( $post_id, '_jw_cc_ss_href', $_POST['attachments'][$post_id]['jw_cc_ss_href'] );
		}
		if( isset( $_POST['attachments'][$post_id]['jw_cc_ss_group'] ) ) {
			update_post_meta( $post_id, '_jw_cc_ss_group', $_POST['attachments'][$post_id]['jw_cc_ss_group'] );
		}
	  
		clean_post_cache($post_id);
	}
	
	function show_post_attachments() {
	?>
	<!-- Prefer Attachments Over Entire Library -->
	<script>
	jQuery(function($) {
	    var called = 0;
	    $('#title').ajaxStop(function() {
	        if ( 0 == called ) {
	            $('[value="uploaded"]').attr( 'selected', true ).parent().trigger('change');
	            called = 1;
	        }
	    });
	});
	</script><?php
	}
	
	function add_meta_boxes() {
	    add_meta_box( 
	        'cc_ss_options',
	        __( 'Gallery Options', 'colorcycle' ),
	        array( &$this, 'cc_inner_meta_box' ),
	        'post',
	        'side'
	    );
	    add_meta_box(
	        'cc_ss_options',
	        __( 'Gallery Options', 'colorcycle' ),
	        array( &$this, 'cc_inner_meta_box' ),
	        'page',
	        'side'
	    );
	}
	
	function cc_inner_meta_box( $post ) {
		$_post_id = $post->ID;
		wp_nonce_field( plugin_basename( __FILE__ ), 'cc_post_options' );
		$ccoptions = get_post_meta( $_REQUEST['post'], '_cc_post_options', true );
		$cc_gall_ss = ( isset( $ccoptions['cc_gall_ss'] ) && $ccoptions['cc_gall_ss'] == 1 ) ? ' checked=checked ' : '';		
		echo '<label for="cc_gall_ss">';
		_e( "Display gallery as slideshow", 'colorcycle' );
		echo '</label> ';
		echo '<input type="checkbox" id="cc_gall_ss" name="cc_gall_ss" value="1"'.$cc_gall_ss.'/>';
		?>
		
		<input id="cc_media_ids" type="text" name="cc_media_ids" value="">
		<a href="#" class="button cc_media_upload" title="Add Media">Add Media</a>			
		<script type="text/javascript">
			jQuery( '.cc_media_upload' ).click(function() {
			    var clone = wp.media.gallery.shortcode;
			    wp.media.gallery.shortcode = function(attachments) {
				    images = attachments.pluck('id');
				    jQuery( '#cc_media_ids' ).val(images);
				    wp.media.gallery.shortcode = clone;
				    var shortcode= new Object();
				    shortcode.string = function() {return '[slideshow]'};
				    return shortcode;
				}
				jQuery(this).blur();
				wp.media.editor.open();
				return false;       
			});
		</script>

		<?php
	}
	
	function cc_save_postdata( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
		      return;

		if ( !wp_verify_nonce( $_POST['cc_post_options'], plugin_basename( __FILE__ ) ) )
		      return;
		      
		if ( 'page' == $_POST['post_type'] ) {
		    if ( !current_user_can( 'edit_page', $post_id ) )
		        return;
		} else {
		    if ( !current_user_can( 'edit_post', $post_id ) )
		        return;
		}
		
		$post_ID = $_POST['post_ID'];
		
		$options = array();
		
		if( isset( $_POST['cc_gall_ss'] ) )
			$options['cc_gall_ss'] = $_POST['cc_gall_ss'];

		update_post_meta( $post_ID, '_cc_post_options', $options );
	}

	
	function cc_slideshow_tab( $tabs ) {
	    $newtab = array( 'cc_slideshow' => 'Add Slideshow' );
	    return array_merge( $tabs, $newtab );
	}
	
	function cc_slideshow_tab_iframe() {
	
		return wp_iframe( array( &$this, 'cc_slideshow_tab_content' ) );		
	
	}	
	function cc_slideshow_tab_content() {

		$_post_id = intval( $_GET['post_id'] );	
		echo media_upload_header();	
		?>
		<div style="padding:10px;">
			<p>Insert a slideshow of images attached to this page/post.</p>
			<label>Size</label>
			<select id="cc_ss_size">
				<?php
				$size_names = apply_filters( 'image_size_names_choose', array('thumbnail' => __('Thumbnail'), 'medium' => __('Medium'), 'large' => __('Large'), 'full' => __('Full Size'), 'other' => __('Other Size') ) );
				foreach( $size_names as $size => $label ) {
				
					echo( '<option value="' . $size . '">' . $label . '</option>' );
				
				}
				?>
			</select>
			<input type="text" size="3" class="ccsizeval" id="cc_ss_w" value="" /> <span class="ccsizeval">x</span> 
			<input type="text" size="3" class="ccsizeval" id="cc_ss_h" value="" />
			<br/><br/>
			<select id="cc_ss_showthumbs">
				<option value="false">No Thumbnails</option>
				<option value="true">Show Thumbnails</option>
			</select>
			<select id="cc_ss_show">
				<option value="selected">Show Specified</option>
				<option value="all">Show All</option>
			</select>
			<script type="text/javascript">
			  jQuery('#cc_ss_size').change( function() {
			  	var sel = document.getElementById("cc_ss_size").value;
			  	if( sel == 'other' ) {
			  		jQuery('.ccsizeval').show();
			  	} else {
			  		jQuery('.ccsizeval').hide();
			  	}
			  });
			  function insertSlideshow(id) {
			    var s;
			    var cc_ss_size = document.getElementById("cc_ss_size");
				var cc_ss_showthumbs = document.getElementById("cc_ss_showthumbs");
			    var cc_ss_show = document.getElementById("cc_ss_show");
			    
			    if( cc_ss_size.value == 'other' ) {
			    	var cc_ss_w = document.getElementById("cc_ss_w");
			    	var cc_ss_h = document.getElementById("cc_ss_h");
			    	var ss_size = cc_ss_w.value + ',' + cc_ss_h.value;		    			    
			    } else {
			    	var ss_size = cc_ss_size.value;
			    }
			    
			    s = "[slideshow ";
			    s += "size=\"" + ss_size + "\" ";
			    s += "show=\"" + cc_ss_show.value + "\" ";
			    s += "showthumbs=\"" + cc_ss_showthumbs.value + "\"]";
			    getJWWin().send_to_editor(s);
			  }
			
			  function getJWWin() {
			    return window.dialogArguments || opener || parent || top;
			  }
			</script><br/>
			<br/>
			<a onclick='insertSlideshow(<?php echo $_post_id; ?>);' id='cc_ss_insert_<?php echo $_post_id; ?>' class='button button-primary' name='send[<?php echo $_post_id; ?>]'>Insert Slideshow</a>
			<div class="attachments">
				
			</div>
		</div>
		<?php
	}
	
	// Improved content filter from Shutter Reloaded by Andrew Ozz

	function content_filter( $content ) {
	
		return preg_replace_callback( '/<a ([^>]+)>/i', array( &$this, 'filter_callback' ), $content );
	
	}
	
	function filter_callback( $a ) {

		global $post, $add_cc_scripts;

		$str = $a[1];
	
		if ( preg_match('/href=[\'"][^"\']+\.(?:gif|jpeg|jpg|png)/i', $str) ) {

			$add_cc_scripts = true;

			if ( false !== strpos(strtolower($str), 'class=') )
				return '<a ' . preg_replace('/(class=[\'"])/i', '$1colorbox cc-' . $post->ID . ' ', $str) . '>';
			else
				return '<a class="colorbox cc-' . $post->ID . '" ' . $str . '>';

		}

		return $a[0];

	}
		
	function gallery_sc( $content, $attr ) {
		return $this->colorcycle_gallery( $attr );
	}
	
	function colorcycle_gallery( $attr ) {
		
		global $post, $add_cc_scripts, $cc_localize, $wptouch_pro;

		static $instance = 0;
		$instance++;
		
		if ( isset( $attr['orderby'] ) ) {
			$attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
			if ( !$attr['orderby'] )
				unset( $attr['orderby'] );
		}
		
		if ( ! empty( $attr['ids'] ) ) {
			if ( empty( $attr['orderby'] ) )
				$attr['orderby'] = 'post__in';
			$attr['include'] = $attr['ids'];
		}
		
		if( is_object( $wptouch_pro ) && $wptouch_pro->showing_mobile_theme ) {
			$attr['size'] = 'medium';
			$attr = apply_filters( 'cc_gallery_wpt_attr', $attr );
		} else {
			$attr = apply_filters( 'cc_gallery_attr', $attr );			
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
			'group' => false,
			'linkto' => 'large',
			'maxHeight' => '90%',
			'maxWidth' => '90%',
			'cbslideshow' => true,
			'cb_width' => false,
			'cb_height' => false,
			'name' => false,
			'theme' => false,
			'ids' => '',
			'exclude' => ''
		), $attr ) );
	
		$id = ( $id == 'null' ) ? null : intval( $id );
		
		if( $name )
			$id = $this->get_id_from_name( $name );
		
		if ( 'RAND' == $order )
		$orderby = 'none';
		
		$size = $this->get_size_from_string( $size );
		
		$cc_localize['theme'] = ( $theme ) ? $theme : 'default';
		
		$cb_opts = array(
			'width' => $cb_width,
			'height' => $cb_height,
			'maxHeight' => $maxHeight,
			'maxWidth' => $maxWidth,
			'slideshow' => $cbslideshow,
			'slideshowAuto' => false,
			'slideshowSpeed' => 2500
		);
		
		$cc_localize['cb_opts'] = $cb_opts;
		
		$cc_localize = apply_filters( 'cc_gallery_localize', $cc_localize, $post->ID );
		
		$args = array(
			'post_parent' => $id,
			'post_status' => 'inherit',
			'post_type' => 'attachment',
			'post_mime_type' => 'image',
			'order' => $order,
			'orderby' => $orderby,
		);
		
		if ( 'RAND' == $order )
			$orderby = 'none';
		
		$args = apply_filters( 'cc_gallery_args', $args, $post->ID );
				
		if ( !empty( $ids ) ) {
			$args = array_merge( $args, array( 'include' => $ids ) );
			$_attachments = get_posts( $args );
				$attachments = array();
			foreach ( $_attachments as $key => $val ) {
				$attachments[$val->ID] = $_attachments[$key];
			}
		} elseif ( !empty( $exclude ) ) {
			$args = array_merge( $args, array( 'exclude' => $exclude ) );
			$attachments = get_children( $args );
		} else {
			$attachments = get_children( $args );
		}
					
		if ( empty( $attachments ) )
			return '';
	
		if ( is_feed() ) {
			$output = "\n";
			foreach ( $attachments as $att_id => $attachment )
				$output .= wp_get_attachment_link($att_id, $size, true) . "\n";
			return $output;
		}
	
		$add_cc_scripts = true;
		
		$itemtag = tag_escape($itemtag);
		$captiontag = tag_escape($captiontag);
		$columns = intval($columns);
		$itemwidth = $columns > 0 ? floor(100/$columns) : 100;
		
		$output = "
		<!-- colorcycle_gallery -->
		<div id='gallery-{$instance}' class='gallery colorcycle-gallery galleryid-{$id}'>";
		
		$i = 0;
		foreach ( $attachments as $id => $attachment ) {
			
			if( $linkto == 'url' ) {
				$url = get_post_meta( $id, "_jw_cc_ss_href", true );
				$class = 'linkto-url';
			} else {
				$url = wp_get_attachment_image_src( $id, $linkto, false );
				$url = $url[0];
				$class = 'colorbox linkto-img';
			}
			$img = wp_get_attachment_image( $id, $size, false );
			$title = ( $attachment->post_excerpt) ? esc_attr( $attachment->post_excerpt ) : esc_attr( $attachment->post_title ) ;
			$output .= "<{$itemtag} class='gallery-item' style='width:{$itemwidth}%;'>";
			$output .= "
				<{$icontag} class='gallery-icon'>
					<a href='$url' rel='gall$instance' class='$class' title='$title'>$img</a>
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
				<br class='clear' />
			</div>\n";
	
		return $output;
	}
	
	function colorcycle_show( $attr ) {
				
		global $post, $add_cc_scripts, $cc_localize, $_wp_additional_image_sizes, $wptouch_pro;
		$add_cc_scripts = true;
		
		static $cc_instance = 0;
		$cc_instance++;

		$attr = apply_filters( 'cc_show_attr', $attr, $post->ID );
		
		if( is_object( $wptouch_pro ) && $wptouch_pro->showing_mobile_theme ) {
			$attr['size'] = 'medium';		
			$attr = apply_filters( 'cc_show_wpt_attr', $attr );
		} else {
			$attr = apply_filters( 'cc_show_attr', $attr );			
		}
		
		if ( ! empty( $attr['ids'] ) ) {
			if ( empty( $attr['orderby'] ) )
				$attr['orderby'] = 'post__in';
			$attr['include'] = $attr['ids'];
		}
		
		if ( isset( $attr['orderby'] ) ) {
			$attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
			if ( !$attr['orderby'] )
				unset( $attr['orderby'] );
		}
		
		if( !isset( $attr['size'] ) )
			$attr['size'] = 'large';
			
		$attr['size'] = $this->get_size_from_string( $attr['size'] );	
		
		if( count( $attr['size'] ) < 2 && isset( $attr['size'] ) ) {
		
			if( $attr['size'] == 'medium' || $attr['size'] == 'large' ) {
			
				$default_w = get_option( $attr['size'] . '_size_w' );
				$default_h = get_option( $attr['size'] . '_size_h' );
				
			} else {
						
				$default_w = $_wp_additional_image_sizes[$attr['size']]['width'];
				$default_h = $_wp_additional_image_sizes[$attr['size']]['width'];
			
			}
					
		} else {
			
			$default_w = $attr['size'][0];
			$default_h = $attr['size'][1];
		
		}
		
		extract( shortcode_atts( array(
			'id' => $post->ID,
			'size' => 'large',
			'width' => $default_w,
			'height' => $default_h,
			'fullsize' => 'full',
			'thumbsize' => array(50,50),
			'show' => 'selected',
			'showthumbs' => false,
			'showcaption' => false,
			'linkto' => 'large',
			'pager' => false,
			'per_page' => 12,
			'speed' => 2500,
			'timeout' => 2000,
			'pause' => 0,
			'delay' => 1000,
			'fx' => 'fade',
			'group' => false,
			'orderby' => 'menu_order',
			'order' => 'ASC',
			'maxHeight' => '90%',
			'maxWidth' => '90%',
			'cbslideshow' => true,
			'cb_width' => '90%',
			'cb_height' => '90%',
			'target' => false,
			'forcevertical' => false,
			'name' => false,
			'ids' => '',
			'exclude' => '',
			'cbslideshowspeed' => 10000
		), $attr ) );
		
		$id = ( $id == 'null') ? null : intval( $id );
		
		$showthumbs = ( $showthumbs === 'true' );
		
		if( $name )
			$id = $this->get_id_from_name( $name );
		
		if ( 'RAND' == $order )
			$orderby = 'none';
					
		$cc_localize['height'] = $height;
		$cc_localize['width'] = $width;
		$cc_localize['speed'] = $speed;
		$cc_localize['timeout'] = $timeout;
		$cc_localize['pause'] = $pause;
		$cc_localize['delay'] = $delay;
		$cc_localize['fx'] = $fx;
		$cc_localize['thumbs'] = $showthumbs; 
		$cc_localize['forcevertical'] = $forcevertical;
		
		$cb_opts = array(
			'width' => $cb_width,
			'height' => $cb_height,
			'maxHeight' => $maxHeight,
			'maxWidth' => $maxWidth,
			'slideshow' => $cbslideshow,
			'slideshowSpeed' => $cbslideshowspeed
		);
		
		$cc_localize['cb_opts'] = $cb_opts;
		
		$cc_localize = apply_filters( 'cc_show_localize', $cc_localize, $post->ID );
		
		$args = array(
			'post_parent' => $id,
			'post_status' => 'inherit',
			'post_type' => 'attachment',
			'post_mime_type' => 'image',
			'order' => $order,
			'orderby' => $orderby,
		);
		
		if( !empty( $group ) ) {
			$args['meta_key'] = '_jw_cc_ss_group';
			$args['meta_value'] = $group;			
		} elseif( $show == 'selected' && empty( $include ) ) {
			$args['meta_key'] = '_jw_cc_ss_img';
			$args['meta_value'] = 'yes';
		}
		
		$args = apply_filters( 'cc_show_args', $args, $post->ID );
		
		if ( !empty( $ids ) ) {
			$args = array_merge( $args, array( 'include' => $ids ) );
			$_attachments = get_posts( $args );
				$attachments = array();
			foreach ( $_attachments as $key => $val ) {
				$attachments[$val->ID] = $_attachments[$key];
			}
		} elseif ( !empty($exclude) ) {
			$args = array_merge( $args, array( 'exclude' => $exclude ) );
			$attachments = get_children( $args );
		} else {
			$attachments = get_children( $args );
		}
		
		if( $attachments ) {
			$out = '
			<!-- colorcycle_show -->
			<div class="jw-colorcycle-wrap">
			<div id="jw-colorcycle-' . $post->ID . '-' . $cc_instance . '" class="jw-colorcycle">';
		} else {
			$out = null;
			return $out;
			exit;
		}
		
		$count = 1;
		$slides = $thumbs = '';
		$targetattr = ( $target ) ? ' target="' . $target . '"' : '';
		
		foreach( $attachments as $attachment => $attachment_array ) {
		
			if( $showthumbs )
				$thumbimage = wp_get_attachment_image_src( $attachment, $thumbsize, false );
				
			$slideimage = wp_get_attachment_image_src( $attachment, $size, false );
			$fullimage = wp_get_attachment_image_src( $attachment, $fullsize, false );
		
			$image = get_post( $attachment );
			$image_title = $image->post_title;
			$image_title_attr = ( $image->post_excerpt ) ? esc_attr( $image->post_excerpt ) : esc_attr( strip_tags( $image_title ) );
			$image_alt = ( $alt = get_post_meta( $image->ID, '_wp_attachment_image_alt', true ) ) ? $alt : $image_title_attr;
			$image_caption = $image->post_excerpt;
			$image_description = $image->post_content;
			
			if( $image_caption != '' && $showcaption ) {
				$caption = '<div class="slide-cap">' . $image_caption . '</div>';
			} else {
				$caption = '';
			}
			
			if( $linkto == 'none' ) { 
			
				$out.= '<div class="slide" id="i' . $count . '"><span class="jw-cc-img-wrap"><img src="' . $slideimage[0] . '" width="' . $slideimage[1] . '" height="' . $slideimage[2] . '" alt="' . $image_alt . '" /></span>' . $caption . '</div>';
			
			} elseif( $linkto == 'large' || get_post_meta( $image->ID, "_jw_cc_ss_href", true ) == '' ) {
			
				$out.= '<div class="slide" id="i'.$count.'"><a href="'.$fullimage[0].'" class="colorbox jw-cc-img-wrap" rel="group' . $cc_instance . '" title="' . $image_title_attr . '"><img src="'.$slideimage[0] . '" width="'.$slideimage[1].'" height="'.$slideimage[2].'" alt="' . $image_alt . '" /></a>' . $caption . '</div>';
				
			} elseif( $linkto == 'url' ) { 
			
				$out.= '<div class="slide" id="i' . $count . '"><a class="jw-cc-img-wrap" href="' . get_post_meta($image->ID, "_jw_cc_ss_href", true ) . '"><img src="' . $slideimage[0] . '" width="' . $slideimage[1] . '" height="' . $slideimage[2] . '" alt="' . $image_alt . '" /></a>' . $caption . '</div>';
				
			} 
			
			if( $showthumbs ) {
			
				$thumbs.= '<li class="colorcycle-thumb"><a href="#jw-colorcycle-' . $post->ID . '-' . $cc_instance . '" id="goto' . $count . '" class="slides' . $post->ID . '"><img src="' . $thumbimage[0] . '" width="' . $thumbimage[1] . '" height="' . $thumbimage[2] . '" alt="Thumbnail: ' . $image_title_attr . '" /></a></li>';
				
			}			
			
			$count++;

		}
		
		$out.= '</div>';

		if( $pager ) {
		
			$out.= '<div class="jw-colorcycle-pager"><a href="#jw-colorcycle-' . $post->ID . '-' . $cc_instance . '" class="jw-cc-prev">Previous</a> <span class="jw-cc-pages"> </span> <a href="#jw-colorcycle-' . $post->ID . '-' . $cc_instance . '" class="jw-cc-next">Next</a></div>';
			
		}

		$out.= '</div>';
		
		if( $showthumbs ) {
		
			$out.= '<div class="jw-colorcycle-thumbs"><ul class="jw-colorcycle-thumb">'.$thumbs.'</ul></div>';
			
		}
				
		return $out;
		
	}

	function colorcycle_index( $attr ) {

		global $post;
		
		if ( isset( $attr['orderby'] ) ) {
			$attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
			if ( !$attr['orderby'] )
				unset( $attr['orderby'] );
		}
		
		extract( shortcode_atts( array(
			'id' => $post->ID,
			'size' => 'thumbnail',
			'showthumbs' => false,
			'orderby' => 'menu_order',
			'order' => 'ASC',
			'columns' => 3,
			'post_type' => 'page',
			'hidecurrent' => false,
		), $attr ) );	
		
		$id = ( $id == 'parent' ) ? $post->post_parent : intval( $id );
		$sizearray = explode( ',', $size );
		$size = ( count( $sizearray > 1 ) ) ? $sizearray : $size;
		
		$gindexargs = array(
			'post_parent' => $id,
			'post_type' => $post_type,
			'posts_per_page' => '-1',
			'orderby' => 'menu_order',
			'order' => 'ASC'
		);
		
		if( $hidecurrent ) 
			$gindexargs['post__not_in'] = array( $post->ID );
		
		$current = $post->ID;
		$columns = intval($columns);
		$itemwidth = $columns > 0 ? floor(100/$columns) : 100;
			
		$gindex = new WP_Query( $gindexargs );		
		
		if( $gindex->have_posts() ) : 
			$i = 0;
			$out = '<ul class="cc-gallery-index clearfix">';
			while( $gindex->have_posts() ) : $gindex->the_post();
				$active = ( $current == $post->ID ) ? ' cc-active' : null;
				$out.= '<li class="cc-gallery' . $active . '" style="width:' . $itemwidth . '%;"><a href="'
				 . get_permalink() . '" title="' . the_title_attribute( array( 'echo' => 0 ) ) . '">'
				 . get_the_post_thumbnail( $post->ID, $size, array( 'title' => the_title_attribute( array( 'echo' => 0, 'class' => 'cc-gallery-thumb' ) ) ) ) . '<br/>' 
				 . $post->post_title . '</a></li>';
				 if ( $columns > 0 && ++$i % $columns == 0 )
					$out.= '</ul><ul class="cc-gallery-index clearfix">';
			endwhile; 
			$out.= '</ul>';
		endif;
	
		return $out;
	
	}
	
	function get_id_from_name( $name ) {
		global $wpdb;
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s OR post_name = %s AND post_status = 'publish'", $name, $name ) );
		return $id;
	}
	
	function get_size_from_string( $size ) {
		$sizearray = explode( ',', $size );
		$size = ( count( $sizearray ) == 2 ) ? $sizearray : $size;
		return $size;
	}

}

register_activation_hook( __FILE__, array( 'ColorCycle', 'install' ) );
register_deactivation_hook( __FILE__, array( 'ColorCycle', 'uninstall' ) );