<?php

abstract class Jetpack_Tiled_Gallery_Layout {
	protected $type; // Defined in child classes
	public $attachments;
	public $link;
	public $grayscale;

	public function __construct( $attachments, $link, $grayscale ) {
		$this->attachments = $attachments;
		$lthis->link = $link;
		$this->needs_attachment_link = ! ( isset( $link ) && $link == 'file' );
		$this->grayscale = $grayscale;
	}

	public function HTML() {
		// Render the carousel container template, which will take the
		// appropriate strategy to fill it
		ob_start();
		require dirname( __FILE__) . '/templates/carousel-container.php';
		$html = ob_get_clean();

		return $html;
	}

	protected function get_container_extra_data() {
		global $post;

		$blog_id = (int) get_current_blog_id();

		if ( defined( 'IS_WPCOM' ) && IS_WPCOM ) {
			$likes_blog_id = $blog_id;
		} else {
			$likes_blog_id = Jetpack_Options::get_option( 'id' );
		}

		$extra_data = array( 'blog_id' => $blog_id, 'permalink' => get_permalink( isset( $post->ID ) ? $post->ID : 0 ), 'likes_blog_id' => $likes_blog_id );

		return $extra_data;
	}
}
?>
