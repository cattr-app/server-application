## About Cattr
Cattr is an open-source time tracking solution, designed to be flawlessly integrated with your infrastructure. 
Superpowered with features like built-in screenshot capture and activity detection, it's a great instrument to boost 
your team's performance straight to the top.

#### We have our own [Container Registry](https://git.amazingcat.net/cattr/core/app/container_registry/9?orderBy=NAME&sort=desc), the images are hosted on GitLab


### Screenshots
|           Dashboard           |           Project report           |
|:-----------------------------:|:----------------------------------:|
| ![](./examples/dashboard.jpg) | ![](./examples/project_report.jpg) |

### Demo
The demo app is available here: [demo.cattr.app](https://demo.cattr.app) 

## Install Cattr
[Installation manual](https://docs.cattr.app/#/en/getting-started/?id=requirements) on the documentation website.

```
composer install
php artisan key:generate
yarn
```

After should be edited `.env` file (e.g. for DB connection), look at `.env.example` for examples

```
php artisan migrate --seed --seeder=InitialSeeder
```

App will not start without seeding of InitialSeeder

After seeding it, run `php artisan cattr:make:admin` and you will be able to login with following credentials
```
admin@cattr.app
password
```

### Run Local server

local server by default will be run as <http://127.0.0.1:8000>

```
php artisan serve
yarn dev
```

### Generate IDE helpers

```
composer dumphelpers
composer dumperd
```

## Links

https://git.amazingcat.net/cattr/desktop/desktop-application – Cattr Desktop Application. You can also download the built app for
any OS from the [official site](https://cattr.app/desktop/).

### Documentation

You can find the Cattr documentation [on the website](https://docs.cattr.app).

Checkout the [Getting Started](https://docs.cattr.app/#/en/getting-started/) page for a quick overview.

### Questions

For questions and support please use the [Github Discussions](https://github.com/orgs/cattr-app/discussions). 

