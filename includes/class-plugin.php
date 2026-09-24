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

		// Transitional: the 3.0.1 runtime is required and wired here until
		// P0-15 moves its behaviour onto WPPA services.
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
		$this->clock  = new SystemClock();
		$this->flags  = new Flags( $this->clock );
		$this->assets = new Assets( $this->flags );
	}

	public function register_hooks(): void {
		if ( $this->hooks_registered ) {
			return;
		}

		$this->add_hook( 'action', Keys::HOOK_WP_ENQUEUE_SCRIPTS, array( $this->assets, 'register' ), 10, 1 );
		$this->add_hook( 'action', Keys::HOOK_WP_ENQUEUE_SCRIPTS, array( $this->assets, 'enqueue_front' ), 11, 1 );
		$this->add_hook( 'action', Keys::HOOK_ADMIN_ENQUEUE_SCRIPTS, array( $this->assets, 'register' ), 10, 1 );

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

	/**
	 * Delegates to the 3.0.1 loader. Transitional until P0-15.
	 */
	public static function activate(): void {
		\WP_Publication_Archive_Loader::activate();
	}

	/**
	 * Delegates to the 3.0.1 loader. Transitional until P0-15.
	 */
	public static function deactivate(): void {
		\WP_Publication_Archive_Loader::deactivate();
	}
}
