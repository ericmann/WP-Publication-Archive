<?php
/**
 * This is the default template for the WP Publication Archive widget.
 *
 * Simply copy this file to your active theme and customize the output below.  The plugin will automatically detect your custom version and defer to your theme's file.  If you need to add custom markup, CSS classes, or even secondary content loops, do so in your own template file.  DO NOT edit the template bundled with the plugin.  It is meant to be an example upon which you base your own work and will be replaced if/when you upgrade the plugin - meaning any changes to this file will be lost during upgrades.
 *
 * @since 2.5
 */

/** @var WP_Query  */
global $wppa_publications;
/** @var object|WP_Post */
global $post;
if ( $wppa_publications->have_posts() ) {

	// begin html
	echo "<ul>";

	while ( $wppa_publications->have_posts() ) {
		$wppa_publications->the_post();

		// We're doing this instad of `get_the_title()` to avoid include '(Download Publication)'
		$pub_title = $post->post_title;

		echo "<li><a href='" . get_permalink() . "' title='$pub_title'>$pub_title</a></li>";

	}

	echo "</ul>";
}
wp_reset_postdata();
?>