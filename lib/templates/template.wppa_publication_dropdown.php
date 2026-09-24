<?php
/**
 * Default template for displaying the output of the wp-publication-archive shortcode as a dropdown.
 *
 * To customize the display of the shortcode, simply copy this file into your active theme and make changes to the copied
 * version.  The plugin will automatically detect the new version in your theme and will defer to it instead.
 *
 * @since 2.5
 */

global $wppa_container;

/**
 * Certain objects are required for properly generating the output of the shortcode.  The following variables must be
 * retrieved from container variable housed in the global scope.
 *
 * @var array  $publications Array of publications to display.
 * @var number $total_pubs   Number of publications in the database.
 * @var number $limit        Number of publications to display per page.
 * @var number $offset       Number of publications to skip.
 * @var number $paged        Current page number
 * @var object $post         Global Post object representing the page the shortcode is used on.
 */
extract( $wppa_container );
?>
<div class="publication-archive">
	<p><?php _e( 'Download Publication', 'wp_pubarch_translate' ); ?></p>
	<form name="publication_dropdown" method="get" action="">
		<select name="dropdown" onchange="window.location.href=this.form.dropdown.options[this.form.dropdown.selectedIndex].value">
			<option value=""><?php _e( 'Select file', 'wp_pubarch_translate' ); ?></option>
<?php foreach( $publications as $publication ) { ?>
			<option value="<?php echo WP_Publication_Archive::get_open_link( $publication->ID ); ?>"><?php echo $publication->post_title; ?></option>
<?php } ?>
		</select>
	</form>
</div>