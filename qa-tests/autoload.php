<?php
// currently, all Q2A code depends on qa-base

global $qa_options_cache;

// Needed in order to avoid accessing the database while including the qa-base.php file
$qa_options_cache['enabled_plugins'] = '';

$qa_autoconnect = false;
require_once __DIR__.'/../qa-include/qa-base.php';
