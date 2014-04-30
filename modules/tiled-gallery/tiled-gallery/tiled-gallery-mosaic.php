<?php
jetpack_require_lib( 'class.html-tag-builder' );
require_once dirname( __FILE__ ) . '/tiled-gallery-layout.php';
require_once dirname( __FILE__ ) . '/tiled-gallery-item.php';

class Jetpack_Tiled_Gallery_Layout_Mosaic extends Jetpack_Tiled_Gallery_Layout {
	public function HTML() {
		$grouper = new Jetpack_Tiled_Gallery_Grouper( $this->attachments );
		Jetpack_Tiled_Gallery_Shape::reset_last_shape();

		$container = $this->generate_carousel_container();
		foreach ( $grouper->grouped_images as $row ) {
			$container->content( $row->HTML( $this->needs_attachment_link, $this->grayscale ) );
		}

		return $container->build();
	}
}

class Jetpack_Tiled_Gallery_Grouper {
	public $margin = 4;
	public function __construct( $attachments ) {
		$content_width = Jetpack_Tiled_Gallery::get_content_width();
		$ua_info = new Jetpack_User_Agent_Info();

		$this->last_shape = '';
		$this->images = $this->get_images_with_sizes( $attachments );
		$this->grouped_images = $this->get_grouped_images();
		$this->apply_content_width( $content_width - 5 ); //reduce the margin hack to 5px. It will be further reduced when we fix more themes and the rounding error.
	}

	public function get_current_row_size() {
		$images_left = count( $this->images );
		if ( $images_left < 3 )
			return array_fill( 0, $images_left, 1 );

		foreach ( array( 'One_Three', 'One_Two', 'Five', 'Four', 'Three', 'Two_One', 'Symmetric_Row', 'Panoramic' ) as $shape_name ) {
			$class_name = "Jetpack_Tiled_Gallery_$shape_name";
			$shape = new $class_name( $this->images );
			if ( $shape->is_possible() ) {
				Jetpack_Tiled_Gallery_Shape::set_last_shape( $class_name );
				return $shape->shape;
			}
		}

		Jetpack_Tiled_Gallery_Shape::set_last_shape( 'Two' );
		return array( 1, 1 );
	}

	public function get_images_with_sizes( $attachments ) {
		$images_with_sizes = array();

		foreach ( $attachments as $image ) {
			$meta  = wp_get_attachment_metadata( $image->ID );
			$image->width_orig = ( isset( $meta['width'] ) && $meta['width'] > 0 )? $meta['width'] : 1;
			$image->height_orig = ( isset( $meta['height'] ) && $meta['height'] > 0 )? $meta['height'] : 1;
			$image->ratio = $image->width_orig / $image->height_orig;
			$image->ratio = $image->ratio? $image->ratio : 1;
			$images_with_sizes[] = $image;
		}

		return $images_with_sizes;
	}

	public function read_row() {
		$vector = $this->get_current_row_size();

		$row = array();
		foreach ( $vector as $group_size ) {
			$row[] = new Jetpack_Tiled_Gallery_Group( array_splice( $this->images, 0, $group_size ) );
		}

		return $row;
	}

	public function get_grouped_images() {
		$grouped_images = array();

		while( !empty( $this->images ) ) {
			$grouped_images[] = new Jetpack_Tiled_Gallery_Row( $this->read_row() );
		}

		return $grouped_images;
	}

	// todo: split in functions
	// todo: do not stretch images
	public function apply_content_width( $width ) {
		foreach ( $this->grouped_images as $row ) {
			$row->width = $width;
			$row->raw_height = 1 / $row->ratio * ( $width - $this->margin * ( count( $row->groups ) - $row->weighted_ratio ) );
			$row->height = round( $row->raw_height );

			$this->calculate_group_sizes( $row );
		}
	}

	public function calculate_group_sizes( $row ) {
		// Storing the calculated group heights in an array for rounding them later while preserving their sum
		// This fixes the rounding error that can lead to a few ugly pixels sticking out in the gallery
		$group_widths_array = array();
		foreach ( $row->groups as $group ) {
			$group->height = $row->height;
			// Storing the raw calculations in a separate property to prevent rounding errors from cascading down and for diagnostics
			$group->raw_width = ( $row->raw_height - $this->margin * count( $group->images ) ) * $group->ratio + $this->margin;
			$group_widths_array[] = $group->raw_width;
		}
		$rounded_group_widths_array = Jetpack_Constrained_Array_Rounding::get_rounded_constrained_array( $group_widths_array, $row->width );

		foreach ( $row->groups as $group ) {
			$group->width = array_shift( $rounded_group_widths_array );
			$this->calculate_image_sizes( $group );
		}
	}

	public function calculate_image_sizes( $group ) {
		// Storing the calculated image heights in an array for rounding them later while preserving their sum
		// This fixes the rounding error that can lead to a few ugly pixels sticking out in the gallery
		$image_heights_array = array();
		foreach ( $group->images as $image ) {
			$image->width = $group->width - $this->margin;
			// Storing the raw calculations in a separate property for diagnostics
			$image->raw_height = ( $group->raw_width - $this->margin ) / $image->ratio;
			$image_heights_array[] = $image->raw_height;
		}

		$image_height_sum = $group->height - count( $image_heights_array ) * $this->margin;
		$rounded_image_heights_array = Jetpack_Constrained_Array_Rounding::get_rounded_constrained_array( $image_heights_array, $image_height_sum );

		foreach ( $group->images as $image ) {
			$image->height = array_shift( $rounded_image_heights_array );
		}
	}
}

class Jetpack_Tiled_Gallery_Row {
	public function __construct( $groups ) {
		$this->groups = $groups;
		$this->ratio = $this->get_ratio();
		$this->weighted_ratio = $this->get_weighted_ratio();
	}

	public function get_ratio() {
		$ratio = 0;
		foreach ( $this->groups as $group ) {
			$ratio += $group->ratio;
		}
		return $ratio > 0? $ratio : 1;
	}

	public function get_weighted_ratio() {
		$weighted_ratio = 0;
		foreach ( $this->groups as $group ) {
			$weighted_ratio += $group->ratio * count( $group->images );
		}
		return $weighted_ratio > 0 ? $weighted_ratio : 1;
	}

	public function HTML( $needs_attachment_link, $grayscale ) {
		$el = Jetpack_HTML_Tag_Builder::element( 'div' )
				->addClass( 'gallery-row' )
				->css( 'width', esc_attr( $this->width ) . 'px' )
				->css( 'height', esc_attr( $this->height ) . 'px' );
		foreach ( $this->groups as $group ) {
			$el->content( $group->HTML( $needs_attachment_link, $grayscale ) );
		}

		return $el->build();
	}
}

class Jetpack_Tiled_Gallery_Group {
	public function __construct( $images ) {
		$this->images = $images;
		$this->ratio = $this->get_ratio();
	}

	public function get_ratio() {
		$ratio = 0;
		foreach ( $this->images as $image ) {
			if ( $image->ratio )
				$ratio += 1/$image->ratio;
		}
		if ( !$ratio )
			return 1;

		return 1/$ratio;
	}

	public function HTML( $needs_attachment_link, $grayscale ) {
		$el = Jetpack_HTML_Tag_Builder::element( 'div' )
				->addClass( 'gallery-group', 'images-' . esc_attr( count( $this->images ) ) )
				->css( 'width', esc_attr( $this->width ) . 'px' )
				->css( 'height', esc_attr( $this->height ) . 'px' );

		foreach ( $this->images as $image ) {
			$gallery_item = new Jetpack_Tiled_Gallery_Item( $image, $needs_attachment_link, 'mosaic' );
			$el->content( $gallery_item->HTML( $grayscale ) );
		}

		return $el->build();
	}
}
?>
