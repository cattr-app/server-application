<?php

/**
 * Customize functional tests bootstrap
 */
putenv("DB_CONNECTION=mysql_test");

/**
 * Migrate DB, then test in transaction
 */
passthru("php artisan migrate:refresh");
passthru("php artisan db:seed");

require __DIR__ . '/autoload.php';
