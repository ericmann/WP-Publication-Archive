<?php
/**
 * Characterisation fixture: a 3.0.1-style theme copy of the bundled list
 * template. Reproduces the extract( $wppa_container ) pattern real 3.0.1
 * theme copies use, to prove D15 (SPEC §6.5) still renders.
 */

global $wppa_container;

// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- reason: 3.0.1 theme copy fixture (SPEC §6.5)
extract( $wppa_container );

echo 'CHAR-LIST ' . count( $publications );
