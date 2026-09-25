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
 * retrieved from container variable housed in the global scope. D15 (SPEC §6.5): read explicitly, without dumping the array into local variables.
 *
 * @var array<int, \WP_Post> $publications Array of publications to display.
 * @var number $total_pubs   Number of publications in the database.
 * @var number $limit        Number of publications to display per page.
 * @var number $offset       Number of publications to skip.
 * @var number $paged        Current page number
 * @var object $post         Post object representing the page the shortcode is used on.
 */
$publications      = $wppa_container['publications'];
$total_pubs        = $wppa_container['total_pubs'];
$limit             = $wppa_container['limit'];
$offset            = $wppa_container['offset'];
$container_paged   = $wppa_container['paged'];
$container_post    = $wppa_container['post'];
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

	<?php $next = add_query_arg( 'wpa-paged', $container_paged + 1, get_permalink( $container_post->ID ) ); ?>
	<?php $prev = add_query_arg( 'wpa-paged', $container_paged - 1, get_permalink( $container_post->ID ) ); ?>

	<?php if($offset > 0) { ?>
		<div class="nav-previous">
			<a href="<?php echo esc_url( $prev ); ?>">
				&laquo; <?php _e( 'Previous', 'wp-publication-archive' ); ?>
			</a>
		</div>
	<?php } ?>

	<?php if($offset + $limit < $total_pubs ) { ?>
		<div class="nav-next">
			<a href="<?php echo esc_url( $next ); ?>">
				<?php _e( 'Next', 'wp-publication-archive' ); ?> &raquo;
			</a>
		</div>
	<?php } ?>

	</div>
<?php } ?>
