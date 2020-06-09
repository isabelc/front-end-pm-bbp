<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( $box_content->found_messages ) {
?>
	<div class="fep-cb-check-uncheck-all-div">
		<label>
			<input type="checkbox" class="fep-cb-check-uncheck-all" />
			<?php esc_html_e( 'Check/Uncheck all', 'front-end-pm' ); ?>
		</label>
	</div>
	<div id="fep-table" class="fep-table fep-odd-even">
		<?php
		while ( $box_content->have_messages() ) {
			$box_content->the_message();
			?><div id="fep-message-<?php echo fep_get_the_id(); ?>" class="fep-table-row">
				<?php foreach ( Fep_Messages::init()->get_table_columns() as $column => $display ) : ?>
					<div class="fep-column fep-column-<?php echo esc_attr( $column ); ?>"><?php Fep_Messages::init()->get_column_content( $column ); ?></div>
				<?php endforeach; ?>
			</div>
			<?php
		} //endwhile
		?>
	</div>
	<?php
	echo fep_pagination_prev_next( $box_content->has_more_row );
} else {
	if ( empty( $_GET['fep-filter'] ) || 'show-all' == $_GET['fep-filter'] ) {
		?>
		<div class="fep-error">No messages found.</div>
		<?php
	} else {
		?>
		<div class="fep-error">No messages found. Try different filter.</div>
		<?php
	}
}
