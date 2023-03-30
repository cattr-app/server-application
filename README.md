## About Cattr
Cattr is an open-source time tracking solution, designed to be flawlessly integrated with your infrastructure. 
Superpowered with features like built-in screenshot capture and activity detection, it's a great instrument to boost 
your team's performance straight to the top.

### Screenshots
|                                                Dashboard                                                 |                                              Project report                                              |
|:--------------------------------------------------------------------------------------------------------:|:--------------------------------------------------------------------------------------------------------:|
| ![](https://git.amazingcat.net/Cattr/core/cattr-frontend/uploads/69a5912d9db48237c29cd58aa54728b1/2.png) | ![](https://git.amazingcat.net/Cattr/core/cattr-frontend/uploads/bd595fdde959e6aff922ce2253a8acc8/1.png) |

### Demo
The demo app is available here: [demo.cattr.app](https://demo.cattr.app) 

## Install Cattr
[Installation manual](https://docs.cattr.app/#/en/advanced/) on the documentation website.

```
composer install
yarn
```

After should be edited `.env` file (e.g. for DB connection), look at `.env.example` for examples

```
php artisan migrate --seed --seeder=InitialSeeder
```

App will not start without seeding of InitialSeeder

### Run Local server

local server by default will be run as <http://127.0.0.1:8000>

```
php artisan serve
yarn dev
```

### Generate IDE helpers

```
composer dumphelpers
```

## Links

https://github.com/cattr-app/desktop-application – Cattr Desktop Application. You can also download the built app for
any OS from the [official site](https://cattr.app/desktop/).

### Documentation

You can find the Cattr documentation [on the website](https://docs.cattr.app).

Checkout the [Getting Started](https://docs.cattr.app/#/en/getting-started/) page for a quick overview.

### Questions

For questions and support please use the [official forum](https://community.cattr.app). 

