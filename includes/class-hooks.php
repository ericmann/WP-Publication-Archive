<?php
/**
 * Implements SPEC.md §6.4: one static method per exposed hook (legacy names
 * included). This is the only file that calls apply_filters() or
 * do_action() (P4). Add a Keys constant, a method here, a line in
 * docs/HOOKS.md and a test, in that order.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA;

final class Hooks {

	private function __construct() {
	}

	public static function filter_enabled( bool $enabled, string $context ): bool {
		return (bool) apply_filters( Keys::FILTER_ENABLED, $enabled, $context );
	}

	public static function booted( Plugin $plugin ): void {
		do_action( Keys::ACTION_BOOTED, $plugin );
	}

	public static function content_save_pre( string $content ): string {
		return (string) apply_filters( Keys::CORE_FILTER_CONTENT_SAVE_PRE, $content );
	}

	public static function open_url( string $url ): string {
		return (string) apply_filters( Keys::FILTER_OPEN_URL, $url );
	}

	public static function download_url( string $url ): string {
		return (string) apply_filters( Keys::FILTER_DOWNLOAD_URL, $url );
	}

	public static function mask_url( bool $mask ): bool {
		return (bool) apply_filters( Keys::FILTER_MASK_URL, $mask );
	}

	public static function publication_icon( string $url, string $doctype ): string {
		return (string) apply_filters( Keys::FILTER_PUBLICATION_ICON, $url, $doctype );
	}

	/**
	 * @param string $title
	 * @param int    $id
	 *
	 * @return string
	 */
	public static function item_title( $title, $id ) {
		return apply_filters( Keys::FILTER_TITLE, $title, $id );
	}

	/**
	 * @param string|null $url
	 * @param int         $id
	 *
	 * @return string|null
	 */
	public static function item_upload_image( $url, $id ) {
		return apply_filters( Keys::FILTER_UPLOAD_IMAGE, $url, $id );
	}

	/**
	 * @param bool|string $authors
	 * @param int         $id
	 *
	 * @return bool|string
	 */
	public static function item_authors( $authors, $id ) {
		return apply_filters( Keys::FILTER_AUTHORS, $authors, $id );
	}

	/**
	 * @param string $summary
	 * @param int    $id
	 *
	 * @return string
	 */
	public static function item_summary( $summary, $id ) {
		return apply_filters( Keys::FILTER_SUMMARY, $summary, $id );
	}

	/**
	 * @param bool|string $keywords
	 * @param int         $id
	 *
	 * @return bool|string
	 */
	public static function item_keywords( $keywords, $id ) {
		return apply_filters( Keys::FILTER_KEYWORDS, $keywords, $id );
	}

	/**
	 * @param bool|string $categories
	 * @param int         $id
	 *
	 * @return bool|string
	 */
	public static function item_categories( $categories, $id ) {
		return apply_filters( Keys::FILTER_CATEGORIES, $categories, $id );
	}

	public static function open_in_blank( bool $default ): bool {
		return (bool) apply_filters( Keys::FILTER_OPEN_IN_BLANK, $default );
	}

	/**
	 * @param int|string $limit
	 *
	 * @return int|string
	 */
	public static function pubs_per_page( $limit ) {
		return apply_filters( Keys::FILTER_PUBS_PER_PAGE, $limit );
	}

	/**
	 * @param int|string $limit
	 *
	 * @return int|string
	 */
	public static function list_limit( $limit ) {
		return apply_filters( Keys::FILTER_LIST_LIMIT, $limit );
	}

	public static function list_template( string $template_name ): string {
		return (string) apply_filters( Keys::FILTER_LIST_TEMPLATE, $template_name );
	}

	public static function dropdown_template( string $template_name ): string {
		return (string) apply_filters( Keys::FILTER_DROPDOWN_TEMPLATE, $template_name );
	}

	public static function widget_template( string $template_name ): string {
		return (string) apply_filters( Keys::FILTER_WIDGET_TEMPLATE, $template_name );
	}

	public static function single_template( string $template_name ): string {
		return (string) apply_filters( Keys::FILTER_SINGLE_TEMPLATE, $template_name );
	}

	public static function archive_template( string $template_name ): string {
		return (string) apply_filters( Keys::FILTER_ARCHIVE_TEMPLATE, $template_name );
	}

	/**
	 * @param array<string, mixed> $container
	 *
	 * @return array<string, mixed>
	 */
	public static function list_container( array $container ): array {
		return (array) apply_filters( Keys::FILTER_LIST_CONTAINER, $container );
	}

	public static function summary_length( int $length ): int {
		return (int) apply_filters( Keys::FILTER_SUMMARY_LENGTH, $length );
	}

	public static function widget_summary_length( int $length ): int {
		return (int) apply_filters( Keys::FILTER_WIDGET_SUMMARY_LENGTH, $length );
	}

	/**
	 * @param mixed $label
	 *
	 * @return mixed
	 */
	public static function list_cats( $label ) {
		return apply_filters( Keys::CORE_FILTER_LIST_CATS, $label );
	}

	public static function dropdown_cats( string $output ): string {
		return (string) apply_filters( Keys::CORE_FILTER_DROPDOWN_CATS, $output );
	}

	/**
	 * @param string               $output
	 * @param array<string, mixed> $args
	 *
	 * @return string
	 */
	public static function list_categories( $output, array $args ) {
		return apply_filters( Keys::CORE_FILTER_LIST_CATEGORIES, $output, $args );
	}

	/**
	 * Passes exactly the arguments each caller gives (3.0.1 called this with
	 * different argument counts from different widgets).
	 *
	 * @param mixed ...$args
	 *
	 * @return mixed
	 */
	public static function widget_title( ...$args ) {
		return apply_filters( Keys::CORE_FILTER_WIDGET_TITLE, ...$args );
	}

	/**
	 * @param array<string, mixed> $args
	 *
	 * @return array<string, mixed>
	 */
	public static function widget_categories_args( array $args ): array {
		return (array) apply_filters( Keys::CORE_FILTER_WIDGET_CATEGORIES_ARGS, $args );
	}

	/**
	 * @param array<string, mixed> $args
	 *
	 * @return array<string, mixed>
	 */
	public static function widget_categories_dropdown_args( array $args ): array {
		return (array) apply_filters( Keys::CORE_FILTER_WIDGET_CATEGORIES_DROPDOWN_ARGS, $args );
	}

	public static function proxy_timeout( int $timeout ): int {
		return (int) apply_filters( Keys::FILTER_PROXY_TIMEOUT, $timeout );
	}

	public static function proxy_max_bytes( int $max_bytes ): int {
		return (int) apply_filters( Keys::FILTER_PROXY_MAX_BYTES, $max_bytes );
	}
}
