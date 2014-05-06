<?php

abstract class Jetpack_Tiled_Gallery_Layout {
	// Template whitelist
	private static $templates = array( 'carousel-container', 'circle-layout', 'rectangular-layout', 'square-layout' );
	private static $partials = array( 'carousel-image-args', 'items' );

	protected $type; // Defined in child classes
	public $attachments;
	public $link;
	public $grayscale;

	public function __construct( $attachments, $link, $grayscale ) {
		$this->attachments = $attachments;
		$this->link = $link;
		$this->needs_attachment_link = ! ( isset( $link ) && $link == 'file' );
		$this->grayscale = $grayscale;
	}

	public function HTML() {
		// Render the carousel container template, which will take the
		// appropriate strategy to fill it
		ob_start();
		$this->template( 'carousel-container' );
		$html = ob_get_clean();

		return $html;
	}

	private function template( $name ) {
		if ( ! in_array( $name, self::$templates ) ) return;
		require dirname( __FILE__ ) . "/templates/$name.php";
	}

	private function partial( $name ) {
		if ( ! in_array( $name, self::$partials ) ) return;
		require dirname( __FILE__ ) . "/templates/partials/$name.php";
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
