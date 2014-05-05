<?php foreach ( $this->rows as $row ): ?>
	<div
		class="gallery-row"
		style="width: <?php echo esc_attr( $row->width ); ?>px; height: <?php echo esc_attr( $row->height ); ?>px;"
	>
	<?php foreach ( $row->groups as $group ): ?>
		<div
			class="gallery-group images-<?php echo esc_attr( count( $group->images ) ); ?>"
			style="width: <?php echo esc_attr( $group->width ); ?>px; height: <?php echo esc_attr( $group->height ); ?>px;"
		>
			<?php $this->items = $group->items( $this->needs_attachment_link, $this->grayscale ); ?>
			<?php $this->partial( 'items' ); ?>
		</div> <!-- close group -->
	<?php endforeach; ?>
	</div> <!-- close row -->
<?php endforeach; ?>
