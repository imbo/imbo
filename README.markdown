# Imbo
Imbo is an image "server" that can be used to add/get/delete images using a REST interface. There is also support for adding meta data to an image. The main idea behind Imbo is to have a place to store high quality original images and to use the REST interface to fetch variations of those images. Imbo will resize, rotate, crop (amongst other features) on the fly so you won't have to store all the different variations.

[![Current build Status](https://secure.travis-ci.org/imbo/imbo.png)](http://travis-ci.org/imbo/imbo)

## Installation / Configuration / Documentation
End-user docs can be found [here](http://docs.imbo-project.org/en/latest/).

## Getting started
These steps will guide you through the basic steps to get imbo up and running. It won't be a perfect set up, but it should be enough to let you play around with imbo and get familiar. Please don't forget to check the [end-user](http://docs.imbo-project.org/en/latest/) docs to understand possible issues with this configuration.

### Requirements

* PHP >= 5.6
* Imagick extension for PHP
* MySQL
* *nix compatible system
* Apache

You can use Mongodb or Sqlite instead of MySQL and Nginx and other webservers will work instead of Apache too.
This tutorials simply aims at configuration which will most likely work on your server and thus uses MySQL as well as Apache.

### Installation

* Switch to your console
* If you don't have composer installed yet, run this command `curl -s https://getcomposer.org/installer | php`
* Create a new directory where you'd like to install imbo. It doesn't have to be in a public directory.
* Within that directory, create another directory called `config` and one called `images`.
* Create another file called `composer.json` with the following content in it:
```
{
  "name": "yourname/imbo",
  "require": {
    "imbo/imbo": "dev-develop",
    "doctrine/dbal": "2.*"
  }
}
```
* The directory should now look like this:
```
/path/to/install/composer.json
/path/to/install/config/
/path/to/install/images/
```
* To install all the packages, run `composer update`.
* Create a symlink to a directory that your Apache webserver can access, in this case `public`:  `ln -s ./vendor/imbo/imbo/public/ public`.
* In your config directory, create a new file called `config.php` with the following content:
```php
<?php
return [
    'accessControl' => function() {
        return new Imbo\Auth\AccessControl\Adapter\SimpleArrayAdapter([
            'user' => 'your-secret-private-key',
        ]);
    },
    'database' => function() {
        return new Imbo\Database\Doctrine([
            'dbname'   => 'database',
            'user'     => 'username',
            'password' => 'password',
            'host'     => 'hostname',
            'driver'   => 'pdo_mysql',
        ]);
    },
    'storage' => function() {
        return new Imbo\Storage\Filesystem([
            'dataDir' => '/path/to/images',
        ]);
    },
];
```
* When you now open your browser and enter the url configured to access the previously linked directory, you should see a JSON file like this:
```
{
site: "http://imbo.io",
source: "https://github.com/imbo/imbo",
issues: "https://github.com/imbo/imbo/issues",
docs: "http://docs.imbo.io"
}
```

### PHP Client

Now that we have our server running, let's create a simple PHP client that uploads a file.

## License
Copyright (c) 2011-2016, Christer Edvartsen <cogo@starzinger.net>

Licensed under the MIT License

## Community
If you have any questions feel free to join `#imbo` on the Freenode IRC network (`chat.freenode.net`), or ask them on the [forum](https://groups.google.com/forum/#!forum/imbo-project).
