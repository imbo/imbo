# Contributing to Imbo

## Resources

If you wish to contribute to Imbo, please read the following resources first:

* The [Contributing to Imbo](https://docs.imbo.io/en/latest/develop/contributing.html) chapter in the documentation

## Running tests

Imbo has both [Behat](https://docs.behat.org/en/latest/) and [PHPUnit](https://phpunit.de/) tests, and when adding new features or fixing bugs you are required to add relevant test cases. Remember to install dependencies before running the tests:

```
composer install
```

### Behat

```
vendor/bin/behat --strict
```

### PHPUnit

```
vendor/bin/phpunit
```

## Writing documentation

Imbo uses [Read the docs](https://readthedocs.org/projects/imbo/) for documentation, and all docs are located in the `docs` dir. The docs are written using [Sphinx](https://www.sphinx-doc.org/en/master/), and if you are contributing new features please add relevant docs.
