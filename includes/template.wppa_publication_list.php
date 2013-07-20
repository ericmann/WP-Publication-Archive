<?php
/**
 * Default template for displaying the output of the wp-publication-archive shortcode as a list.
 *
 * To customize the display of the shortcode, simply copy this file into your active theme and make changes to the copied
 * version.  The plugin will automatically detect the new version in your theme and will defer to it instead.
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
<?php foreach( $publications as $publication ) { ?>
	<?php $pub = new WP_Publication_Archive_Item( $publication ); ?>
	<div class="single-publication">
		<?php $pub->the_thumbnail(); ?>
		<?php $pub->the_title(); ?>
		<?php $pub->the_authors(); ?>
		<?php $pub->the_uri(); ?>
		<?php $pub->the_summary(); ?>
	    <?php $pub->the_keywords(); ?>
		<?php $pub->the_categories(); ?>
	</div>
<?php } ?>
</div>
<?php if( $total_pubs > $limit ) { ?>
<div id="navigation">

	<?php $next = add_query_arg( 'wpa-paged', $paged + 1, get_permalink( $post->ID ) ); ?>
	<?php $prev = add_query_arg( 'wpa-paged', $paged - 1, get_permalink( $post->ID ) ); ?>

	<?php if($offset > 0) { ?>
		<div class="nav-previous">
			<a href="<?php echo $prev; ?>">
				&laquo; <?php _e( 'Previous', 'wp_pubarch_translate' ); ?>
			</a>
		</div>
	<?php } ?>

	<?php if($offset + $limit < $total_pubs ) { ?>
		<div class="nav-next">
			<a href="<?php echo $next; ?>">
				<?php _e( 'Next', 'wp_pubarch_translate' ); ?> &raquo;
			</a>
		</div>
	<?php } ?>

	</div>
<?php } ?>