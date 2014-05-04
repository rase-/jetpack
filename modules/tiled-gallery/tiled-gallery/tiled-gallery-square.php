<?php
require_once dirname( __FILE__ ) . '/tiled-gallery-layout.php';
require_once dirname( __FILE__ ) . '/tiled-gallery-item.php';

class Jetpack_Tiled_Gallery_Layout_Square extends Jetpack_Tiled_Gallery_Layout {
	protected $type = 'square';

	private function compute_items() {
		$content_width = Jetpack_Tiled_Gallery::get_content_width();
		$images_per_row = 3;
		$margin = 2;

		$margin_space = ( $images_per_row * $margin ) * 2;
		$size = floor( ( $content_width - $margin_space ) / $images_per_row );
		$remainder = count( $this->attachments ) % $images_per_row;
		if ( $remainder > 0 ) {
			$remainder_space = ( $remainder * $margin ) * 2;
			$remainder_size = ceil( ( $content_width - $remainder_space - $margin ) / $remainder );
		}

		$items = array();
		$c = 1;
		foreach( $this->attachments as $image ) {
			if ( $remainder > 0 && $c <= $remainder )
				$img_size = $remainder_size;
			else
				$img_size = $size;

			$image->width = $image->height = $img_size;

			$item = new Jetpack_Tiled_Gallery_Square_Item( $image, $this->needs_attachment_link, $this->grayscale );

			$items[] = $item;
			$c ++;
		}

		return $items;
	}

	public function HTML() {
		$this->items = $this->compute_items();
		return parent::HTML();
	}
}
?>
