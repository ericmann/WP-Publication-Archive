<?php
/**
 * Implements SPEC.md §8 Phase 0 item 4 (D16): 3.0.1's icon lookup and MIME
 * detection, moved off the deleted 2002 BSD-licensed mimetype extension
 * table onto wp_check_filetype().
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA;

final class Icons {

	/**
	 * 3.0.1 WP_Publication_Archive::get_image().
	 */
	public function url_for( string $doctype ): string {
		switch ( $doctype ) {
			case 'application/pdf':
			case 'application/postscript':
				$image_url = WP_PUB_ARCH_URL . Keys::ICON_DIR . 'pdf.png';
				break;
			case 'application/zip':
			case 'application/x-stuffit':
			case 'application/x-rar-compressed':
			case 'application/x-tar':
				$image_url = WP_PUB_ARCH_URL . Keys::ICON_DIR . 'zip.png';
				break;
			case 'audio/basic':
			case 'audio/mp4':
			case 'audio/mpeg':
			case 'audio/ogg':
			case 'audio/vorbis':
			case 'audio/x-ms-wma':
			case 'audio/x-ms-wax':
			case 'audio/vnd.rn-realaudio':
			case 'audio/vnd.wave':
				$image_url = WP_PUB_ARCH_URL . Keys::ICON_DIR . 'audio.png';
				break;
			case 'image/gif':
			case 'image/jpeg':
			case 'image/png':
			case 'image/svg+xml':
			case 'image/tiff':
			case 'image/vnd.microsoft.icon':
			case 'application/vnd.oasis.opendocument.graphics':
			case 'application/vnd.ms-excel':
				$image_url = WP_PUB_ARCH_URL . Keys::ICON_DIR . 'image.png';
				break;
			case 'text/cmd':
			case 'text/css':
			case 'text/plain':
			case 'application/vnd.oasis.opendocument.text':
			case 'application/vnd.oasis.opendocument.presentation':
			case 'application/vnd.ms-powerpoint':
			case 'application/vnd.openxmlformats-officedocument.presentationml.presentation':
			case 'application/msword':
			case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
				$image_url = WP_PUB_ARCH_URL . Keys::ICON_DIR . 'doc.png';
				break;
			case 'text/csv':
			case 'application/vnd.oasis.opendocument.spreadsheet':
			case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
				$image_url = WP_PUB_ARCH_URL . Keys::ICON_DIR . 'data.png';
				break;
			case 'video/mpeg':
			case 'video/mp4':
			case 'video/ogg':
			case 'video/quicktime':
			case 'video/webm':
			case 'video/x-ms-wmv':
				$image_url = WP_PUB_ARCH_URL . Keys::ICON_DIR . 'video.png';
				break;
			default:
				$image_url = WP_PUB_ARCH_URL . Keys::ICON_DIR . 'unknown.png';
		}

		return Hooks::publication_icon( $image_url, $doctype );
	}

	/**
	 * D16: replaces the deleted 2002 mimetype extension table's type lookup.
	 * $name may be a URL or a bare filename; only its path/basename is used.
	 */
	public function mime_for( string $name ): string {
		$path = (string) wp_parse_url( $name, PHP_URL_PATH );
		$type = wp_check_filetype( basename( $path ) )['type'];

		return false === $type ? Keys::CONTENT_TYPE_FALLBACK : $type;
	}
}
