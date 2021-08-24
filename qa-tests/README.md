
Running Q2A's unit tests
=============================

From version 1.7 we have started including unit tests for simple automated testing. They are not required to run the app so you can safely delete the folder from your own site.

If you wish to run the tests (for example if you are contributing to Q2A) the following steps are required. This assumes a Mac or Linux operating system; commands may be different for Windows.

1. Download [PHPUnit](https://phar.phpunit.de/phpunit.phar) (PHP archive file).
2. Mark it as executable using the command `chmod +x phpunit.phar`
3. Move it to your executable directory: `mv phpunit.phar /usr/local/bin/phpunit`
4. Change current directory to the Question2Answer root (where `qa-config-example.php` is located)
5. Copy or rename `phpunit-qa-config-example.php` to `phpunit-qa-config.php` and, optionally, set the test database settings (this is only needed in order to run the database-dependent tests)
6. Run `phpunit --bootstrap qa-tests/autoload.php qa-tests` to run all tests
7. Run `phpunit --bootstrap qa-tests/autoload.php --exclude-group database qa-tests` to run all tests, except for the ones that depend on the database

Also check out the [PHPunit documentation](https://phpunit.de/getting-started-with-phpunit.html) for more information about PHPUnit and unit testing in general.
