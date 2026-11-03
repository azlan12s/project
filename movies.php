<?php
/**
 * Movies category page.
 *
 * All the markup lives in includes/category_template.php so the eight category
 * pages stay identical in structure. To add a ninth category: create a file
 * like this one with its slug, and add a row to the categories table.
 */
require_once __DIR__ . '/includes/category_template.php';

render_category_page('movies');