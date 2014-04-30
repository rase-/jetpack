<?php
jetpack_require_lib( 'class.html-tag-builder' );

class Jetpack_Tiled_Gallery_Item {
	private $image;

	public function __construct( $attachment_image, $needs_attachment_link, $type ) {
		$this->image = $attachment_image;
		$this->type = $type;

		$this->size = 'large';

		if ( $this->image->width < 250 )
				$this->size = 'small';

		$this->image_title = $this->image->post_title;
		$this->image_alt = get_post_meta( $this->image->ID, '_wp_attachment_image_alt', true );
		$this->orig_file = wp_get_attachment_url( $this->image->ID );
		$this->link = $needs_attachment_link ? get_attachment_link( $this->image->ID, $this->orig_file ) : $this->orig_file;

		$this->img_src = add_query_arg( array( 'w' => $this->image->width, 'h' => $this->image->height ), $this->orig_file );

		$this->img_src_grayscale = jetpack_photon_url( $this->img_src, array( 'filter' => 'grayscale' ) );
	}

	public function HTML( $grayscale ) {
		// Base elements
		$el = Jetpack_HTML_Tag_Builder::element( 'div' )
				->addClass( 'tiled-gallery-item', 'tiled-gallery-item-' . esc_attr( $this->size ) );
		$a = Jetpack_HTML_Tag_Builder::element( 'a' )
				->href( esc_url( $this->link ) );
		$img = Jetpack_HTML_Tag_Builder::element( 'img' )
				->raw( Jetpack_Tiled_Gallery_Item::generate_carousel_image_args( $this->image ) )
				->src( esc_url( $this->img_src ) )
				->width( esc_attr( $this->image->width ) )
				->height( esc_attr( $this->image->height ) )
				->title( esc_attr( $this->image_title ) )
				->alt( esc_attr( $this->image_alt ) );

		// Set layout type specific things
		if ( 'square' == $this->type ) {
			$img->css( 'margin', esc_attr( $margin ) . 'px' );
			$a->border('0');
		} else if ( 'mosaic' == $this->type ) {
			$img->align( 'left' );
		}

		// Build basic structure
		$el->content( $a->content( $img ) );

		// Grayscale overlay
		if ( $grayscale == true ) {
			$el->content( $this->grayscale_image() );
		}

		// Caption
		if ( trim( $this->image->post_excerpt ) ) {
			$el->content( $this->caption() );
		}

		return $el->build();
	}

	private function grayscale_image() {
		$a = Jetpack_HTML_Tag_Builder::element( 'a' )
				->href( esc_url( $this->link ) );
		$img = Jetpack_HTML_Tag_Builder::element( 'img' )
				->raw( Jetpack_Tiled_Gallery_Item::generate_carousel_image_args( $this->image ) )
				->addClass( 'grayscale' )
				->width( esc_attr( $this->image->width ) )
				->height( esc_attr( $this->image->height ) )
				->align( 'left' )
				->title( esc_attr( $this->image_title ) )
				->alt( esc_attr( $this->image_alt ) );

		if ( 'square' == $this->type ) {
			$a->border('0');
			$img->css( 'margin', '2px' );
			$img->src( esc_url( 'http://en.wordpress.com/imgpress?url=' . urlencode( $this->image->guid ) . '&resize=' . $this->image->width . ',' . $this->image->height . '&filter=grayscale' ) );
		} else if ( 'mosaic' == $this->type ) {
			$img->src( esc_url( $this->img_src_grayscale ) );
		}

		return $a->content( $img );
	}

	private function caption() {
		return Jetpack_HTML_Tag_Builder::element( 'div' )
				->addClass( 'tiled-gallery-caption' )
				->content( wptexturize( $this->image->post_excerpt ) );
	}

	public static function generate_carousel_image_args( $image ) {
		$attachment_id = $image->ID;
		$orig_file       = wp_get_attachment_url( $attachment_id );
		$meta            = wp_get_attachment_metadata( $attachment_id );
		$size            = isset( $meta['width'] ) ? intval( $meta['width'] ) . ',' . intval( $meta['height'] ) : '';
		$img_meta        = ( ! empty( $meta['image_meta'] ) ) ? (array) $meta['image_meta'] : array();
		$comments_opened = intval( comments_open( $attachment_id ) );

		$medium_file_info = wp_get_attachment_image_src( $attachment_id, 'medium' );
		$medium_file      = isset( $medium_file_info[0] ) ? $medium_file_info[0] : '';

		$large_file_info  = wp_get_attachment_image_src( $attachment_id, 'large' );
		$large_file       = isset( $large_file_info[0] ) ? $large_file_info[0] : '';
		$attachment_title = wptexturize( $image->post_title );
		$attachment_desc  = wpautop( wptexturize( $image->post_content ) );

        // Not yet providing geo-data, need to "fuzzify" for privacy
		if ( ! empty( $img_meta ) ) {
            foreach ( $img_meta as $k => $v ) {
                if ( 'latitude' == $k || 'longitude' == $k )
                    unset( $img_meta[$k] );
            }
        }

		$img_meta = json_encode( array_map( 'strval', $img_meta ) );

		$output = sprintf(
				'data-attachment-id="%1$d" data-orig-file="%2$s" data-orig-size="%3$s" data-comments-opened="%4$s" data-image-meta="%5$s" data-image-title="%6$s" data-image-description="%7$s" data-medium-file="%8$s" data-large-file="%9$s"',
				esc_attr( $attachment_id ),
				esc_url( wp_get_attachment_url( $attachment_id ) ),
				esc_attr( $size ),
				esc_attr( $comments_opened ),
				esc_attr( $img_meta ),
				esc_attr( $attachment_title ),
				esc_attr( $attachment_desc ),
				esc_url( $medium_file ),
				esc_url( $large_file )
			);
		return $output;
	}
}
?>
