<?php
/**
 * PHPStan-only stubs for the class_alias() targets registered by
 * WPPA\Legacy\Aliases::register() (see includes/legacy/class-aliases.php).
 * templates/classic/ and Plugin::register_widgets() reference these frozen
 * 3.0.1 class names, which PHPStan cannot resolve through a runtime
 * class_alias(). Never included at runtime.
 */

/**
 * Real alias target: WP_Publication_Archive_Item === Publication_Item.
 */
class WP_Publication_Archive_Item extends \WPPA\Publication_Item {
}

/**
 * Real alias target: WP_Publication_Archive_Utilities === WPPA\Legacy\Utilities.
 */
class WP_Publication_Archive_Utilities extends \WPPA\Legacy\Utilities {
}

/**
 * Real alias target: WP_Publication_Archive_Cat_Count_Widget ===
 * WPPA\Widgets\Category_Count_Widget.
 */
class WP_Publication_Archive_Cat_Count_Widget extends \WPPA\Widgets\Category_Count_Widget {
}

/**
 * Real alias target: WP_Publication_Archive_Widget === WPPA\Widgets\Archive_Widget.
 */
class WP_Publication_Archive_Widget extends \WPPA\Widgets\Archive_Widget {
}

/**
 * Real alias target: WP_Publication_Archive_Category_Widget ===
 * WPPA\Widgets\Related_Widget.
 */
class WP_Publication_Archive_Category_Widget extends \WPPA\Widgets\Related_Widget {
}
