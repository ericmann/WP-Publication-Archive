<?php
/**
 * The Template for displaying all single publications.
 *
 * @package WP Publication Archive
 * @since 3.0
 */

get_header(); ?>

	<div id="primary" class="site-content">
		<div id="content" role="main">

			<?php
			while ( have_posts() ) : the_post(); $publication = new WP_Publication_Archive_Item( get_the_ID() ); ?>

				<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
					<header class="entry-header">
						<h1 class="entry-title"><?php the_title(); ?></h1>
					</header><!-- .entry-header -->

					<div class="entry-content">
						<?php $publication->the_thumbnail(); ?>
						<?php the_content(); ?>
						<section class="publication-downloads">
							<?php $publication->the_uri(); ?>
							<?php $publication->list_downloads(); ?>
						</section>
					</div><!-- .entry-content -->

					<footer class="entry-meta">
						<?php $publication->the_keywords(); ?>
						<?php $publication->the_categories(); ?>
						<?php edit_post_link( __( 'Edit Publication', 'wp_pubarch_translate' ), '<span class="edit-link">', '</span>' ); ?>
					</footer><!-- .entry-meta -->
				</article><!-- #post -->

			<?php endwhile; // end of the loop. ?>

		</div><!-- #content -->
	</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>