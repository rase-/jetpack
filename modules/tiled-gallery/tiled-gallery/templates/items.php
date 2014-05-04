<?php foreach ( $this->items as $item ): ?>
	<div class="tiled-gallery-item<?php if ( isset( $item->size ) ) echo " tiled-gallery-item-$item->size"; ?>">
		<a href="<?php echo $item->link; ?>" border="0">
			<img <?php echo Jetpack_Tiled_Gallery_Item::generate_carousel_image_args( $item->image ); ?>
				src="<?php echo $item->img_src; ?>"
				width="<?php echo esc_attr( $item->image->width ); ?>"
				height="<?php echo esc_attr( $item->image->height ); ?>"
				title="<?php echo esc_attr( $item->image_title ); ?>"
				alt="<?php echo esc_attr( $item->image_alt ); ?>"
			/>
		</a>

		<?php if ( $this->grayscale == true ): ?>
			<a href="<?php echo $item->link; ?>" border="0">
				<img <?php echo Jetpack_Tiled_Gallery_Item::generate_carousel_image_args( $item->image ); ?>
					class="grayscale"
					src="<?php echo esc_url( $item->image_src_grayscale ) ?>"
					width="<?php echo esc_attr( $item->image->width ); ?>"
					height="<?php echo esc_attr( $item->image->height ); ?>"
					title="<?php echo esc_attr( $item->image_title ); ?>"
					align="left"
					alt="<?php echo esc_attr( $item->image_alt ); ?>"
				/>
			</a>
		<?php endif; ?>

		<?php if ( trim( $item->image->post_excerpt ) ): ?>
			<div class="tiled-gallery-caption">
				<?php echo wptexturize( $item->image->post_excerpt ); ?>
			</div>
		<?php endif; ?>
	</div>
<?php endforeach; ?>
