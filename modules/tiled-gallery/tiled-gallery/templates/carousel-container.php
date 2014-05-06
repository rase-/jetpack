<div
	class="tiled-gallery type-<?php echo $this->type; ?> tiled-gallery-unresized"
	data-original-width="<?php echo esc_attr( Jetpack_Tiled_Gallery::get_content_width() ); ?>"
	data-carousel-extra='<?php echo json_encode( $this->get_container_extra_data() ); ?>'
>
	<?php $this->template( "$this->type-layout" ); ?>
</div>
