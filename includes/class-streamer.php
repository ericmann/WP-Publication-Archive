<?php
/**
 * Implements SPEC.md §6.2 Streamer::send(): the plugin's one file-read call
 * (P11). D1 and D6 (a remote URL streamed straight to the client
 * with no size cap or local temp file) close in P1-07: Delivery now proxies
 * only through a temp file this class owns and deletes.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA;

final class Streamer {

	private string $temp_dir;

	/** @var callable */
	private $exit;

	/** @var callable */
	private $header;

	private int $ob_floor;

	public function __construct( string $temp_dir, ?callable $exit = null, ?callable $header = null, int $ob_floor = 0 ) {
		$this->temp_dir = $temp_dir;
		$this->exit     = $exit ?? static function () {
			exit;
		};
		$this->header   = $header ?? 'header';
		$this->ob_floor = $ob_floor;
	}

	public function temp_dir(): string {
		return $this->temp_dir;
	}

	public function ob_floor(): int {
		return $this->ob_floor;
	}

	/**
	 * SPEC §6.2 Streamer::send(): sends the temp file staged by proxy mode
	 * (P1-07 calls this). Refuses a path outside $this->temp_dir, sends
	 * Content-Type/Content-Length/(optional) Content-Disposition, ends
	 * every output buffer above $this->ob_floor without a notice (D6),
	 * streams the file, deletes it, then exits.
	 */
	public function send( string $path, string $content_type, ?string $filename ): void {
		$real_path     = realpath( $path );
		$real_temp_dir = realpath( $this->temp_dir );

		if ( false === $real_path || false === $real_temp_dir || 0 !== strpos( $real_path, $real_temp_dir ) ) {
			throw new \InvalidArgumentException( 'Streamer::send() refuses a path outside its temp dir.' );
		}

		( $this->header )( 'Content-Type: ' . $content_type );
		( $this->header )( 'Content-Length: ' . filesize( $real_path ) );

		if ( null !== $filename ) {
			$filename = str_replace( array( '"', "\r", "\n" ), '', $filename );

			( $this->header )( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		}

		// D6: only while there is a buffer to end, so PHP 8 raises no notice.
		while ( ob_get_level() > $this->ob_floor ) {
			ob_end_clean();
		}

		readfile( $real_path );
		unlink( $real_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink, WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink -- reason: Streamer owns and deletes its own temp file, which lives under $this->temp_dir (get_temp_dir()); wp_delete_file() is unavailable in unit tests.

		( $this->exit )();
	}
}
