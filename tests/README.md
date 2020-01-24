
# Running Tests From PhpStorm  


### IDE Configuration 

* Make sure correct interpreter configured for the project.  
(_File | Settings | Languages & Frameworks | PHP_)

* Make sure path to composer autoloader is correct in test frameworks configuration options,
as well as default configuration file (`phpunit.xml`) for test runner, which can be find inside project root directory.     
(_File | Settings | Languages & Frameworks | PHP | Test Frameworks_)  



### Environment and DB configuration

* Create new test dedicated database, for example `amazing_time_tests`.  
* Copy `.env.testing.example` file and rename it to `.env.testing`.  
* Inside `.env.testing` edit DB connection info to match yours.  
* Apply migrations to testing database: `php artisan migrate --env=testing`.
* Run RoleSeeder: `php artisan db:seed --class=RoleSeeder --env=testing`.

 ### Running tests
 
You can run and debug single tests as well as tests from entire files and folders.
PhpStorm creates a run/debug configuration with the default settings and launches the tests.
You can also run tests with coverage if xdebug extension is installed to see test coverage situation
up to the highlighting of executed and non-executed lines right inside the code editor.  

_For more information check official PhpStorm documentation._
