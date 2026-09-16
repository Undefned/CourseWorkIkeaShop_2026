<?php
header('Content-Type: text/plain; charset=utf-8');
echo 'PHP ' . PHP_VERSION . "\n";
echo 'filter_var: ' . (function_exists('filter_var') ? 'YES' : 'NO') . "\n";
echo 'preg_match: ' . (function_exists('preg_match') ? 'YES' : 'NO') . "\n";
echo 'json_encode: ' . (function_exists('json_encode') ? 'YES' : 'NO') . "\n";
echo 'pdo_pgsql: ' . (extension_loaded('pdo_pgsql') ? 'YES' : 'NO') . "\n";
echo 'mbstring: ' . (extension_loaded('mbstring') ? 'YES' : 'NO') . "\n";
echo 'loaded: ' . implode(',', get_loaded_extensions()) . "\n";