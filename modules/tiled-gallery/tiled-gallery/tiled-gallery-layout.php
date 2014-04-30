<?php

jetpack_require_lib( 'class.html-tag-builder' );

abstract class Jetpack_Tiled_Gallery_Layout {
	public $attachments;
	public $type;
	public $link;
	public $grayscale;

	public function __construct( $attachments, $type, $link, $grayscale ) {
		$this->type = $type;
		$this->attachments = $attachments;
		$this->needs_attachment_link = ! ( isset( $link ) && $link == 'file' );
		$this->grayscale = $grayscale;
	}

	abstract public function HTML();

	protected function generate_carousel_container() {
		global $post;

		$blog_id = (int) get_current_blog_id();

		if ( defined( 'IS_WPCOM' ) && IS_WPCOM ) {
			$likes_blog_id = $blog_id;
		} else {
			$likes_blog_id = Jetpack_Options::get_option( 'id' );
		}

		$extra_data = array( 'blog_id' => $blog_id, 'permalink' => get_permalink( isset( $post->ID ) ? $post->ID : 0 ), 'likes_blog_id' => $likes_blog_id );

		return Jetpack_HTML_Tag_Builder::element( 'div' )
			->addClass( $this->gallery_classes() )
			->data( 'original-width', esc_attr( Jetpack_Tiled_Gallery::get_content_width() ) )
			->raw( "data-carousel-extra='" . json_encode( $extra_data ) . "'" );
	}

	protected function gallery_classes() {
		return 'tiled-gallery type-' . esc_attr( $this->type ) . ' tiled-gallery-unresized';
	}
}
?>
