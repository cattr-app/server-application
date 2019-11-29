## (WIP)
### PhpStorm Configuration 

* First of all make sure that you have correct interpreter configured for the project.  
(**File | Settings | Languages & Frameworks | PHP**)

* Then open Test Framework configuration dialog.  
(**File | Settings | Languages & Frameworks | PHP | Test Frameworks**)
* 
*
*

### Environment and DB configuration

* Create new test dedicated database, for example `amazing_time_tests`.  
* Copy `.env.testing.example` file and rename it to `.env.testing`.  
* In `.env.testing` edit DB connection info to match yours.  
* After that you need to apply migrations to your testing database `php artisan migrate --env=testing`.  
* And run RoleSeeder `php artisan db:seed --class=RoleSeeder`.

