<?php
/**
 * PHPStan-only stub for the class_alias() target of Keys::LEGACY_CLASS_ITEM
 * (WPPA\Legacy\Aliases::register(), see includes/class-publication-item.php
 * and includes/legacy/class-aliases.php). templates/classic/ keeps the
 * frozen 3.0.1 class name so theme copies stay compatible, which PHPStan
 * cannot resolve through a runtime class_alias(). Never included at
 * runtime; the real alias is WP_Publication_Archive_Item === Publication_Item.
 */

class WP_Publication_Archive_Item extends \WPPA\Publication_Item {
}
