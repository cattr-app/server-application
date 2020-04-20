# Running Tests From PhpStorm  
# TEST!

### IDE Configuration 

* Make sure correct interpreter configured for the project.  
(_File | Settings | Languages & Frameworks | PHP_)

* Make sure path to composer autoloader is correct in test frameworks configuration options,
as well as default configuration file (`phpunit.xml`) for test runner, 
which can be find inside project root directory.  
(_File | Settings | Languages & Frameworks | PHP | Test Frameworks_)  



### Environment and DB configuration

* Create new test dedicated database, for example `amazing_time_tests`.
* Copy `.env.testing.example` file and rename it to `.env.testing`.
* Inside `.env.testing` edit DB connection info to match yours.
* Apply migrations to testing database: `php artisan migrate --env=testing`.
* Run RoleSeeder: `php artisan db:seed --class=RoleSeeder --env=testing`.

 ### Running tests
 
You can run and debug single test as well as tests from entire files and folders.
PhpStorm creates a run/debug configuration with the default settings and launches the tests.
You can also run tests with coverage (_if xdebug extension installed_) to see test coverage situation
up to the highlighting of executed and non-executed lines right inside the code editor.  

_For more information check official PhpStorm documentation._

# Running tests from command line

To run tests from CLI use phpunit script from `vendor/phpunit/phpunit`.  
Don't forget to provide path to phpunit.xml with `--configuration` parameter. 


For example:
`vendor/phpunit/phpunit/phpunit --configuration phpunit.xml tests/Feature`.

For more information check [PHPUnit docs](https://phpunit.readthedocs.io/en/8.5/).

# Running Tests in CI pipelines

### GitLab

Configuration at `.gitlab-ci.yml` allows you to run tests automatically 
when you push commits to your repository. 
It will appear as "integration_testing" job at the test stage. 
You can select branches, that job will run on by pointing them in **only** directive at `.gitalb-ci.yml`. 
Or exclude branches by **except** directive.

For more information check [gitlab-ci.yml reference](https://docs.gitlab.com/ee/ci/yaml/) 
and [GitLab CI/CD](https://docs.gitlab.com/ee/ci/)


# Writing Tests

For basic information check [Laravel Documentation](https://laravel.com/docs/6.x/testing).

#### Main TestCase class
Every test should extend `tests/TestCase` class, which creates application before running tests.

It uses `DatabaseTransaction` trait to wrap each test case in a database transaction
to isolate a database from any changes while running tests.
Because of that it's worth mentioning that there is no point in trying to look into the database during debugging, 
because there will be no changes,
but nothing prevents you from making queries directly from the tests.  

#### Requests
All requests return a slightly extended `TestResponse` class, which can be found in `tests/TestResponse.php`. 
It provides methods to conveniently check different types of responses and their structure.

The overridden `actingAs` helper method provides a simple way to authenticate a given user model as the current user.
The user must have valid tokens for successful authentication.

Also, it's highly recommended to use constants when specifying the expected answer status code,
which are defined in `tests/TestCase` as well as in `tests/TestResponse`.

### Factories

The data that's necessary for tests generated with the use of factories that are placed in `tests/Factories`,
and facades for them in `tests/Facades`.
