<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4: 3.0.1's template location
 * (single_publication/publication_archives/find_template) and the
 * excerpt_length filter, including the Related widget's temporary summary
 * length scope (P3: a private flag set/cleared in try/finally, replacing
 * 3.0.1's add_filter/remove_filter-around-a-call pattern).
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA;

final class Templates {

	/**
	 * True while rendering inside the Related widget's loop.
	 */
	private bool $widget_scope = false;

	/**
	 * Hooked to template_include.
	 */
	public function single_template( string $template ): string {
		if ( is_singular( Keys::POST_TYPE ) ) {
			$template_name = Hooks::single_template( Keys::TEMPLATE_SINGLE );

			$path = $this->find( $template_name );
			if ( false !== $path ) {
				$template = $path;
			}
		}

		return $template;
	}

	/**
	 * Hooked to template_include.
	 */
	public function archive_template( string $template ): string {
		if ( is_archive() && Keys::POST_TYPE === get_query_var( 'post_type' ) ) {
			$template_name = Hooks::archive_template( Keys::TEMPLATE_ARCHIVE );

			$path = $this->find( $template_name );
			if ( false !== $path ) {
				$template = $path;
			}
		}

		return $template;
	}

	/**
	 * 3.0.1 find_template(). Stylesheet dir, then template dir, then the
	 * bundled templates/classic/.
	 *
	 * @return string|false
	 */
	public function find( string $name ) {
		$paths = array(
			get_stylesheet_directory() . '/' . $name,
			get_template_directory() . '/' . $name,
			WP_PUB_ARCH_DIR . Keys::TEMPLATE_DIR . $name,
		);

		foreach ( $paths as $path ) {
			if ( file_exists( $path ) ) {
				return $path;
			}
		}

		return false;
	}

	/**
	 * 3.0.1's locate_template()-or-bundled pattern used by the shortcode
	 * and the archive widget.
	 */
	public function locate( string $name ): string {
		$path = locate_template( $name );

		if ( '' !== $path ) {
			return $path;
		}

		return WP_PUB_ARCH_DIR . Keys::TEMPLATE_DIR . $name;
	}

	/**
	 * Hooked to excerpt_length.
	 */
	public function excerpt_length( int $length ): int {
		if ( $this->widget_scope ) {
			return Hooks::widget_summary_length( Keys::DEFAULT_WIDGET_SUMMARY_LENGTH );
		}

		$post = get_post();

		if ( null !== $post && Keys::POST_TYPE === $post->post_type ) {
			return Hooks::summary_length( $length );
		}

		return $length;
	}

	/**
	 * Runs $render() with excerpt_length() answering the widget summary
	 * length, replacing 3.0.1's add_filter()/remove_filter() around the
	 * Related widget's loop (P3).
	 */
	public function with_widget_summary_length( callable $render ): void {
		$this->widget_scope = true;

		try {
			$render();
		} finally {
			$this->widget_scope = false;
		}
	}
}
