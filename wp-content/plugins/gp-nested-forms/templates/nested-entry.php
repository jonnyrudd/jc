<?php
/**
 * @var $nested_form
 * @var $entry
 * @var $modifiers
 */

echo GFCommon::get_submitted_fields( $nested_form, $entry, false, false, 'html', false, 'all_fields', $modifiers );