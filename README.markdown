# Imbo - Image box
Imbo is an image "server" that can be used to add/get/delete images using a REST interface. There is also support for adding meta data to an image. The main idea behind Imbo is to have a place to store high quality original images and to use the REST interface to fetch variations of those images. Imbo will resize, rotate, crop (amongst other features) on the fly so you won't have to store all the different variations.

[![Current build Status](https://secure.travis-ci.org/imbo/imbo.png)](http://travis-ci.org/imbo/imbo)

## Documentation
End-user docs can be found [here](http://docs.imbo-project.org/en/latest/).

## Community
Feel free to join `#imbo` on the Freenode IRC network (`chat.freenode.net`) if you have any questions.

## Developer/Contributer notes
Here you will find some notes about how Imbo works internally along with information on what is needed to develop Imbo.

* [Jenkins job](http://ci.starzinger.net/job/Imbo/)
* [API Documentation](http://ci.starzinger.net/job/Imbo/API_Documentation/)
* [Code Coverage](http://ci.starzinger.net/job/Imbo/Code_Coverage/)
* [Code Browser](http://ci.starzinger.net/job/Imbo/Code_Browser/)

### Get started
First you must make sure you have the [Imagick extension](http://pecl.php.net/package/imagick) installed. If not, you can install it using the following commands (on Ubuntu):

    sudo apt-get install php5-imagick

Now click the fork button on github and then clone your fork:

    git clone git@github.com:<username>/imbo.git

Enter the newly created directory and initialize the project using [composer](http://getcomposer.org):

    cd imbo
    curl -s https://getcomposer.org/installer | php
    php composer.phar --dev install

or by using the Rakefile if you have installed rake:

    cd imbo
    rake installdep

And lastly, execute the unit test suite:

    vendor/bin/phpunit

or by using the Rakefile:

    rake phpunit

If you want to run the functional tests using [Behat](http://behat.org/) and [Guzzle](http://guzzlephp.org/) (which requires php-5.4, unless you specify a working url in ``behat.yml``) you can do this by using the behat binary or the Rakefile:

    vendor/bin/behat

or

    rake behat

If you want to run both the unit test suite and the functional tests, you can run:

    rake test

Some tests will probably be skipped unless you have already installed all optional dependencies, like [APC](http://pecl.php.net/package/apc), [Memcached](http://pecl.php.net/package/memcached) and [Doctrine](http://www.doctrine-project.org).

If you send me a pull request I would appreciate it if you include tests for all new code, and make sure that the test suite passes. I also require you to use "feature branches", also for minor fixes like typos in comments.
