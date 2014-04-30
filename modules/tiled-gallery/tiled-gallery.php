<?php

// Include the class file containing methods for rounding constrained array elements.
// Here the constrained array element is the dimension of a row, group or an image in the tiled gallery.
include_once dirname( __FILE__ ) . '/math/class-constrained-array-rounding.php';
include_once dirname( __FILE__ ) . '/tiled-gallery/tiled-gallery-mosaic.php';
include_once dirname( __FILE__ ) . '/tiled-gallery/tiled-gallery-square.php';

jetpack_require_lib( 'class.html-tag-builder' );

class Jetpack_Tiled_Gallery {
	private static $talaveras = array( 'rectangular', 'square', 'circle', 'rectangle' );

	public function __construct() {
		add_action( 'admin_init', array( $this, 'settings_api_init' ) );
		add_filter( 'jetpack_gallery_types', array( $this, 'jetpack_gallery_types' ), 9 );
		add_filter( 'jetpack_default_gallery_type', array( $this, 'jetpack_default_gallery_type' ) );
	}

	public function tiles_enabled() {
		// Check the setting status
		return '' != get_option( 'tiled_galleries' );
	}

	public function set_atts( $atts ) {
		global $post;

		$this->atts = shortcode_atts( array(
			'order'      => 'ASC',
			'orderby'    => 'menu_order ID',
			'id'         => isset( $post->ID ) ? $post->ID : 0,
			'include'    => '',
			'exclude'    => '',
			'type'       => '',
			'grayscale'  => false,
			'link'       => '',
		), $atts );

		$this->atts['id'] = (int) $this->atts['id'];
		$this->float = is_rtl() ? 'right' : 'left';

		// Default to rectangular is tiled galleries are checked
		if ( $this->tiles_enabled() && ( ! $this->atts['type'] || 'default' == $this->atts['type'] ) )
			$this->atts['type'] = 'rectangular';

		if ( !$this->atts['orderby'] ) {
			$this->atts['orderby'] = sanitize_sql_orderby( $this->atts['orderby'] );
			if ( !$this->atts['orderby'] )
				$this->atts['orderby'] = 'menu_order ID';
		}

		if ( 'RAND' == $this->atts['order'] )
			$this->atts['orderby'] = 'none';
	}

	public function get_attachments() {
		extract( $this->atts );

		if ( !empty( $include ) ) {
			$include = preg_replace( '/[^0-9,]+/', '', $include );
			$_attachments = get_posts( array('include' => $include, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );

			$attachments = array();
			foreach ( $_attachments as $key => $val ) {
				$attachments[$val->ID] = $_attachments[$key];
			}
		} elseif ( 0 == $id ) {
			// Should NEVER Happen but infinite_scroll_load_other_plugins_scripts means it does
			// Querying with post_parent == 0 can generate stupidly memcache sets on sites with 10000's of unattached attachments as get_children puts every post in the cache.
			// TODO Fix this properly
			$attachments = array();
		} elseif ( !empty( $exclude ) ) {
			$exclude = preg_replace( '/[^0-9,]+/', '', $exclude );
			$attachments = get_children( array('post_parent' => $id, 'exclude' => $exclude, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );
		} else {
			$attachments = get_children( array('post_parent' => $id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby ) );
		}
		return $attachments;
	}

	public function get_attachment_link( $attachment_id, $orig_file ) {
		if ( isset( $this->atts['link'] ) && $this->atts['link'] == 'file' )
			return $orig_file;
		else
			return get_attachment_link( $attachment_id );
	}

	public static function default_scripts_and_styles() {
		wp_enqueue_script( 'tiled-gallery', plugins_url( 'tiled-gallery/tiled-gallery.js', __FILE__ ), array( 'jquery' ) );
		wp_enqueue_style( 'tiled-gallery', plugins_url( 'tiled-gallery/tiled-gallery.css', __FILE__ ), array(), '2012-09-21' );
	}

	public function gallery_shortcode( $val, $atts ) {
		if ( ! empty( $val ) ) // something else is overriding post_gallery, like a custom VIP shortcode
			return $val;

		global $post;

		$this->set_atts( $atts );

		$attachments = $this->get_attachments();
		if ( empty( $attachments ) )
			return '';

		if ( is_feed() || defined( 'IS_HTML_EMAIL' ) )
			return '';

		if ( in_array( $this->atts['type'], self::$talaveras ) ) {
			// Enqueue styles and scripts
			self::default_scripts_and_styles();
			$gallery = ( 'rectangular' == $this->atts['type'] )
				? new Jetpack_Tiled_Gallery_Layout_Mosaic( $attachments, $this->atts['type'], $this->atts['link'], $this->atts['grayscale'] )
				: new Jetpack_Tiled_Gallery_Layout_Square( $attachments, $this->atts['type'], $this->atts['link'], $this->atts['grayscale'] );

			$gallery_html = $gallery->HTML();

			if ( $gallery_html && class_exists( 'Jetpack' ) && class_exists( 'Jetpack_Photon' ) ) {
				// Tiled Galleries in Jetpack require that Photon be active.
				// If it's not active, run it just on the gallery output.
				if ( ! in_array( 'photon', Jetpack::get_active_modules() ) )
					$gallery_html = Jetpack_Photon::filter_the_content( $gallery_html );
			}

			return $gallery_html;
		}

		return '';
	}

	public static function gallery_already_redefined() {
		global $shortcode_tags;
		if ( ! isset( $shortcode_tags[ 'gallery' ] ) || $shortcode_tags[ 'gallery' ] !== 'gallery_shortcode' )
			return true;
	}

	public static function init() {
		if ( self::gallery_already_redefined() )
			return;

		$gallery = new Jetpack_Tiled_Gallery;
		add_filter( 'post_gallery', array( $gallery, 'gallery_shortcode' ), 1001, 2 );
	}

	public static function get_content_width() {
		$tiled_gallery_content_width = Jetpack::get_content_width();

		if ( ! $tiled_gallery_content_width )
			$tiled_gallery_content_width = 500;

		return apply_filters( 'tiled_gallery_content_width', $tiled_gallery_content_width );
	}

	/**
	 * Media UI integration
	 */
	function jetpack_gallery_types( $types ) {
		if ( get_option( 'tiled_galleries' ) && isset( $types['default'] ) ) {
			// Tiled is set as the default, meaning that type='default'
			// will still display the mosaic.
			$types['thumbnails'] = $types['default'];
			unset( $types['default'] );
		}

		$types['rectangular'] = __( 'Tiled Mosaic', 'jetpack' );
		$types['square'] = __( 'Square Tiles', 'jetpack' );
		$types['circle'] = __( 'Circles', 'jetpack' );

		return $types;
	}

	function jetpack_default_gallery_type( $default ) {
		return ( get_option( 'tiled_galleries' ) ? 'rectangular' : 'default' );
	}

	/**
	 * Add a checkbox field to the Carousel section in Settings > Media
	 * for setting tiled galleries as the default.
	 */
	function settings_api_init() {
		global $wp_settings_sections;

		// Add the setting field [tiled_galleries] and place it in Settings > Media
		if ( isset( $wp_settings_sections['media']['carousel_section'] ) )
			$section = 'carousel_section';
		else
			$section = 'default';

		add_settings_field( 'tiled_galleries', __( 'Tiled Galleries', 'jetpack' ), array( $this, 'setting_html' ), 'media', $section );
		register_setting( 'media', 'tiled_galleries', 'esc_attr' );
	}

	function setting_html() {
		echo '<label><input name="tiled_galleries" type="checkbox" value="1" ' .
			checked( 1, '' != get_option( 'tiled_galleries' ), false ) . ' /> ' .
			__( 'Display all your gallery pictures in a cool mosaic.', 'jetpack' ) . '</br></label>';
	}
}

class Jetpack_Tiled_Gallery_Shape {
	static $shapes_used = array();

	public function __construct( $images ) {
		$this->images = $images;
		$this->images_left = count( $images );
	}

	public function sum_ratios( $number_of_images = 3 ) {
		return array_sum( array_slice( wp_list_pluck( $this->images, 'ratio' ), 0, $number_of_images ) );
	}

	public function next_images_are_symmetric() {
		return $this->images_left > 2 && $this->images[0]->ratio == $this->images[2]->ratio;
	}

	public function is_not_as_previous( $n = 1 ) {
		return ! in_array( get_class( $this ), array_slice( self::$shapes_used, -$n ) );
	}

	public function is_wide_theme() {
		return Jetpack::get_content_width() > 1000;
	}

	public static function set_last_shape( $last_shape ) {
		self::$shapes_used[] = $last_shape;
	}

	public static function reset_last_shape() {
		self::$shapes_used = array();
	}
}

class Jetpack_Tiled_Gallery_Three extends Jetpack_Tiled_Gallery_Shape {
	public $shape = array( 1, 1, 1 );

	public function is_possible() {
		$ratio = $this->sum_ratios( 3 );
		return $this->images_left > 2 && $this->is_not_as_previous() &&
			( ( $ratio < 2.5 ) || ( $ratio < 5 && $this->next_images_are_symmetric() ) || $this->is_wide_theme() );
	}
}

class Jetpack_Tiled_Gallery_Four extends Jetpack_Tiled_Gallery_Shape {
	public $shape = array( 1, 1, 1, 1 );

	public function is_possible() {
		return $this->is_not_as_previous() && $this->sum_ratios( 4 ) < 3.5 &&
			( $this->images_left == 4 || ( $this->images_left != 8 && $this->images_left > 5 ) );
	}
}

class Jetpack_Tiled_Gallery_Five extends Jetpack_Tiled_Gallery_Shape {
	public $shape = array( 1, 1, 1, 1, 1 );

	public function is_possible() {
		return $this->is_wide_theme() && $this->is_not_as_previous() && $this->sum_ratios( 5 ) < 5 &&
			( $this->images_left == 5 || ( $this->images_left != 10 && $this->images_left > 6 ) );
	}
}

class Jetpack_Tiled_Gallery_Two_One extends Jetpack_Tiled_Gallery_Shape {
	public $shape = array( 2, 1 );

	public function is_possible() {
		return $this->is_not_as_previous( 3 ) && $this->images_left >= 2 &&
			$this->images[2]->ratio < 1.6 && $this->images[0]->ratio >= 0.9 && $this->images[0]->ratio < 2.0 && $this->images[1]->ratio >= 0.9 && $this->images[1]->ratio < 2.0;
	}
}

class Jetpack_Tiled_Gallery_One_Two extends Jetpack_Tiled_Gallery_Shape {
	public $shape = array( 1, 2 );

	public function is_possible() {
		return $this->is_not_as_previous( 3 ) && $this->images_left >= 2 &&
			$this->images[0]->ratio < 1.6 && $this->images[1]->ratio >= 0.9 && $this->images[1]->ratio < 2.0 && $this->images[2]->ratio >= 0.9 && $this->images[2]->ratio < 2.0;
	}
}

class Jetpack_Tiled_Gallery_One_Three extends Jetpack_Tiled_Gallery_Shape {
	public $shape = array( 1, 3 );

	public function is_possible() {
		return $this->is_not_as_previous() && $this->images_left > 3 &&
			$this->images[0]->ratio < 0.8 && $this->images[1]->ratio >= 0.9 && $this->images[1]->ratio < 2.0 && $this->images[2]->ratio >= 0.9 && $this->images[2]->ratio < 2.0 && $this->images[3]->ratio >= 0.9 && $this->images[3]->ratio < 2.0;
	}
}

class Jetpack_Tiled_Gallery_Panoramic extends Jetpack_Tiled_Gallery_Shape {
	public $shape = array( 1 );

	public function is_possible() {
		return $this->images[0]->ratio >= 2.0;
	}
}

class Jetpack_Tiled_Gallery_Symmetric_Row extends Jetpack_Tiled_Gallery_Shape {
	public $shape = array( 1, 2, 1 );

	public function is_possible() {
		return $this->is_not_as_previous() && $this->images_left >= 3 && $this->images_left != 5 &&
			$this->images[0]->ratio < 0.8 && $this->images[0]->ratio == $this->images[3]->ratio;
	}
}



add_action( 'init', array( 'Jetpack_Tiled_Gallery', 'init' ) );

