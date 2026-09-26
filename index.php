<?php

/**
 * Laravel - Root Directory Entry Point Redirect
 *
 * For web server environments (e.g. Laragon, shared hosting) where the document root
 * is pointing to the project folder rather than the public/ directory.
 */

define('LARAVEL_START', microtime(true));

// Forward request to public index
require_once __DIR__ . '/public/index.php';
