# Amazing Time Core Application

## Installation

```
npm install -g @angular/cli@1.7.1
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

## Build

- `ng build` - build public
- `ng build -w` - build public and watch changes

## Migrations

- `php artisan migrate` - apply all not applied migrations
- `php artisan make:migration <MigrationName>` - create new migration
- `php artisan migrate:fresh` - drop all tables and re-run all migrations

- `php artisan db:seed` - apply all DB seeders
- `php artisan db:seed --class=<SeederClassName>` - apply specific seeders only
- `php artisan make:seeder <SeederClassName>` - create new seeder
