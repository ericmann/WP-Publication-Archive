<?php
/**
 * Implements SPEC.md §4: the composition root. Builds every service once,
 * wires every hook in register_hooks() (the only add_action/add_filter/
 * add_shortcode site, P3), and exposes accessors so tests can read and swap
 * services.
 *
 * @author Eric Mann <eric@eamann.com>
 */

namespace WPPA;

final class Plugin {

	private static ?Plugin $instance = null;

	private Clock $clock;

	private Flags $flags;

	private Assets $assets;

	private Cli $cli;

	private Rest $rest;

	private Post_Type $post_type;

	private Rewrites $rewrites;

	private Upgrade $upgrade;

	private bool $hooks_registered = false;

	/** @var list<array{type: string, hook: string, callback: callable, priority: int}> */
	private array $registered_hooks = array();

	public static function boot(): void {
		if ( null !== self::$instance ) {
			return;
		}

		$instance = new self();

		self::$instance = $instance;

		wp_cache_add_global_groups( Keys::CACHE_GROUP );

		$instance->register_hooks();

		$instance->upgrade->maybe_upgrade();

		// Transitional: the 3.0.1 runtime is required and wired here until
		// P0-15 moves its remaining behaviour onto WPPA services.
		\WP_Publication_Archive_Loader::load();

		Hooks::booted( $instance );
	}

	public static function instance(): self {
		if ( null === self::$instance ) {
			throw new \LogicException( 'Plugin has not been booted.' );
		}

		return self::$instance;
	}

	private function __construct() {
		$this->clock     = new SystemClock();
		$this->flags     = new Flags( $this->clock );
		$this->assets    = new Assets( $this->flags );
		$this->cli       = new Cli( $this->flags );
		$this->rest      = new Rest( $this->flags );
		$this->post_type = new Post_Type();
		$this->rewrites  = new Rewrites( $this->flags );
		$this->upgrade   = new Upgrade( $this->flags );
	}

	public function register_hooks(): void {
		if ( $this->hooks_registered ) {
			return;
		}

		$this->add_hook( 'action', Keys::HOOK_WP_ENQUEUE_SCRIPTS, array( $this->assets, 'register' ), 10, 1 );
		$this->add_hook( 'action', Keys::HOOK_WP_ENQUEUE_SCRIPTS, array( $this->assets, 'enqueue_front' ), 11, 1 );
		$this->add_hook( 'action', Keys::HOOK_ADMIN_ENQUEUE_SCRIPTS, array( $this->assets, 'register' ), 10, 1 );
		$this->add_hook( 'action', Keys::HOOK_CLI_INIT, array( $this, 'register_cli' ), 10, 1 );
		$this->add_hook( 'action', Keys::HOOK_REST_API_INIT, array( $this->rest, 'register_routes' ), 10, 1 );

		$this->add_hook( 'action', Keys::HOOK_INIT, array( $this->post_type, 'register' ), 10, 1 );
		$this->add_hook( 'action', Keys::HOOK_INIT, array( $this->rewrites, 'register' ), 10, 1 );
		$this->add_hook( 'action', Keys::HOOK_INIT, array( $this, 'load_textdomain' ), 10, 1 );
		$this->add_hook( 'filter', Keys::HOOK_QUERY_VARS, array( $this->rewrites, 'query_vars' ), 10, 1 );
		$this->add_hook( 'filter', Keys::HOOK_POST_TYPE_LINK, array( $this->rewrites, 'filter_post_type_link' ), 10, 2 );

		// D11, preserved: only shown when PHP cannot fetch remote files.
		if ( ! (bool) ini_get( 'allow_url_fopen' ) ) {
			$this->add_hook( 'action', Keys::HOOK_ADMIN_NOTICES, array( $this, 'fopen_notice' ), 10, 1 );
		}

		$this->hooks_registered = true;
	}

	public function unregister_hooks(): void {
		foreach ( $this->registered_hooks as $registered ) {
			if ( 'action' === $registered['type'] ) {
				remove_action( $registered['hook'], $registered['callback'], $registered['priority'] );
			} else {
				remove_filter( $registered['hook'], $registered['callback'], $registered['priority'] );
			}
		}

		$this->registered_hooks = array();
		$this->hooks_registered = false;
	}

	/**
	 * @param callable $callback
	 */
	private function add_hook( string $type, string $hook, $callback, int $priority, int $args ): void {
		if ( 'action' === $type ) {
			add_action( $hook, $callback, $priority, $args );
		} else {
			add_filter( $hook, $callback, $priority, $args );
		}

		$this->registered_hooks[] = array(
			'type'     => $type,
			'hook'     => $hook,
			'callback' => $callback,
			'priority' => $priority,
		);
	}

	public function register_cli(): void {
		if ( class_exists( 'WP_CLI' ) ) {
			\WP_CLI::add_command( Keys::CLI_COMMAND, $this->cli );
		}
	}

	/**
	 * Hooked to init. No translation function may run before init
	 * (WordPress 6.7 just-in-time notice).
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			Keys::TEXT_DOMAIN,
			false,
			dirname( plugin_basename( WP_PUB_ARCH_DIR . Keys::SLUG . '.php' ) ) . '/' . Keys::LANGUAGES_DIR
		);
	}

	/**
	 * Hooked to admin_notices, only when allow_url_fopen is off (D11).
	 */
	public function fopen_notice(): void {
		echo '<div class="error"><p>' . wp_kses_post(
			__( 'Please set <code>allow_url_fopen</code> to "On" in your PHP.ini file, otherwise WP Publication Archive downloads <strong>WILL NOT WORK!</strong>', 'wp-publication-archive' )
		) . '<br /><a target="_blank" href="http://php.net/allow-url-fopen">' . esc_html__( 'More information ...', 'wp-publication-archive' ) . '</a></p></div>';
	}

	/**
	 * Swap a service. Tests only.
	 */
	public function replace( string $service, object $with ): void {
		if ( ! defined( 'WP_TESTS_MULTISITE' ) && ! class_exists( \PHPUnit\Framework\TestCase::class, false ) ) {
			throw new \LogicException( 'Plugin::replace() is only available in tests.' );
		}

		switch ( $service ) {
			case 'clock':
				$this->assign_service( $service, $with, Clock::class, $this->clock );
				break;
			case 'flags':
				$this->assign_service( $service, $with, Flags::class, $this->flags );
				break;
			case 'assets':
				$this->assign_service( $service, $with, Assets::class, $this->assets );
				break;
			case 'cli':
				$this->assign_service( $service, $with, Cli::class, $this->cli );
				break;
			case 'rest':
				$this->assign_service( $service, $with, Rest::class, $this->rest );
				break;
			case 'post_type':
				$this->assign_service( $service, $with, Post_Type::class, $this->post_type );
				break;
			case 'rewrites':
				$this->assign_service( $service, $with, Rewrites::class, $this->rewrites );
				break;
			case 'upgrade':
				$this->assign_service( $service, $with, Upgrade::class, $this->upgrade );
				break;
			default:
				throw new \InvalidArgumentException( 'Unknown service: ' . $service );
		}
	}

	/**
	 * @param mixed $with
	 * @param mixed $current
	 */
	private function assign_service( string $service, $with, string $expected, &$current ): void {
		if ( ! $with instanceof $expected ) {
			throw new \TypeError( 'Service "' . $service . '" must be an instance of ' . $expected . '.' );
		}

		$current = $with;
	}

	public function clock(): Clock {
		return $this->clock;
	}

	public function flags(): Flags {
		return $this->flags;
	}

	public function assets(): Assets {
		return $this->assets;
	}

	public function cli(): Cli {
		return $this->cli;
	}

	public function rest(): Rest {
		return $this->rest;
	}

	public function post_type(): Post_Type {
		return $this->post_type;
	}

	public function rewrites(): Rewrites {
		return $this->rewrites;
	}

	public function upgrade(): Upgrade {
		return $this->upgrade;
	}

	public static function activate(): void {
		$instance = self::instance();
		$instance->post_type->register();
		$instance->rewrites->register();

		flush_rewrite_rules(); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules -- reason: 3.0.1 behaviour, activation-only (SPEC §4.1).
	}

	public static function deactivate(): void {
		flush_rewrite_rules(); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules -- reason: 3.0.1 behaviour, deactivation-only (SPEC §4.1).
	}
}
