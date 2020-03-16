# Cattr Backend Application

## Installation

```
composer install
composer run-script config:init
```

After should be edited `.env` file (e.g. for DB connection)

```
php artisan migrate
```

## Run Local server

local server by default will be run as <http://127.0.0.1:8000>

```
php artisan serve
```

## Migrations

- `php artisan migrate` - apply all not applied migrations
- `php artisan make:migration <MigrationName>` - create new migration
- `php artisan migrate:fresh` - drop all tables and re-run all migrations

- `php artisan db:seed` - apply all DB seeders
- `php artisan db:seed --class=<SeederClassName>` - apply specific seeders only
- `php artisan make:seeder <SeederClassName>` - create new seeder

## Generate IDE helpers

```
composer dumphelpers
```

## Generate documentation

```
npm install
npm run api
```
