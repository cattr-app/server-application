# AmazingTime core-app Tests

## Set up PhpStorm or IntelliJ IDEA

First of all, we should set up `phpunit`

1. Go to [File] -> [Settings...] -> [Languages & Frameworks] 
-> [PHP] -> [Tests Frameworks]

2. Click `Download phpunit.phar from https://phpunit.de/phpunit.phar`.

Okay. We have working phpunit. There is time to setup phpunit config.

1. In [Tests Frameworks] find `Default configuration file`, 
then set path to `phpunit.xml` in project.

2. Done.

![Example](./pic/TestsSetup.png =250x)

## How to run tests

1. Go to [Run] -> [Run...]
2. Click `phpunit.xml`

PhpStorm starts tests.

## How to add tests

If you want to add additional route to existing controller:
1. Open existing test controller 
(i.e. `tests/Feature/v1/UserControllerTest.php` )
2. Add new method, where name should:
  * start with `test_`
  * contain test method name (i.e. `create` or `update`)
  * and what should expect (i.e. `ExpectFail` or `ExpectPass`)
* if test should fail, you should write why (i.e. `EmptyId`)
  
