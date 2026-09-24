<?php
/**
 * The template for displaying Archive pages.
 *
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 *
 * @package WP Publication Archive
 * @since 3.0
 */

get_header(); ?>

	<section id="primary" class="site-content">
		<div id="content" role="main">

			<?php if ( have_posts() ) : ?>
				<header class="archive-header">
					<h1 class="archive-title">
						<?php _e( 'Publication Archives', 'wp_pubarch_translate' ); ?>
					</h1>
				</header><!-- .archive-header -->

				<?php
				while ( have_posts() ) : the_post(); $publication = new WP_Publication_Archive_Item( get_the_ID() ); ?>

					<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
					<header class="entry-header">
						<h1 class="entry-title">
							<a href="<?php the_permalink(); ?>" title="<?php echo esc_attr( sprintf( __( 'Permalink to %s', 'wp_pubarch_translate' ), the_title_attribute( 'echo=0' ) ) ); ?>" rel="bookmark"><?php the_title(); ?></a>
						</h1>
					</header><!-- .entry-header -->

					<div class="entry-content">
						<?php $publication->the_thumbnail(); ?>
						<?php the_excerpt(); ?>
					</div><!-- .entry-content -->

					<footer class="entry-meta">
						<?php edit_post_link( __( 'Edit Publication', 'wp_pubarch_translate' ), '<span class="edit-link">', '</span>' ); ?>
					</footer><!-- .entry-meta -->
				</article><!-- #post -->

				<?php endwhile; ?>

			<?php endif; ?>

		</div><!-- #content -->
	</section><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>