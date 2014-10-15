
Running Q2A's unit tests
=============================

From version 1.7 we have started including unit tests for simple automated testing. They are not required to run the app so you can safely delete the folder from your own site.

If you wish to run the tests (for example if you are contributing to Q2A) the following steps are required. This assumes a Mac or Linux operating system; commands may be different for Windows.

1. Download [PHPUnit](https://phar.phpunit.de/phpunit.phar) (PHP archive file).
2. Mark it as executable using the command `chmod +x phpunit.phar`
3. Move it to your executable directory: `mv phpunit.phar /usr/local/bin/phpunit`
4. Navigate to the Question2Answer root directory.
5. Run `phpunit --bootstrap qa-tests/autoload.php qa-tests`

Also check out the [PHPunit documentation](http://phpunit.de/getting-started.html) for more information about PHPUnit and unit testing in general.
