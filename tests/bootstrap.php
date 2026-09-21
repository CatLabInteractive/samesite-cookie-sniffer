<?php

require __DIR__ . '/../vendor/autoload.php';

// Fail on deprecations and notices, as consuming apps do in their test suites.
set_error_handler(function ($severity, $message, $file, $line) {
    throw new \ErrorException($message, 0, $severity, $file, $line);
});
