<?php

/**
 * Customize functional tests bootstrap
 */
putenv("DB_CONNECTION=mysql_test");

/**
 * Migrate DB, then test in transaction
 */
$artisan = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'artisan';

passthru("php $artisan migrate:refresh");
passthru("php $artisan db:seed");

require __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';
