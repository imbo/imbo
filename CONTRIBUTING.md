# Contributing to Imbo
## Running tests

Imbo has both [Behat](http://docs.behat.org/en/v2.5/) and [PHPUnit](https://phpunit.de/) tests, and when adding new features or fixing bugs you are required to add relevant test cases.

### Behat

```
./vendor/bin/behat --strict --profile no-cc --config tests/behat/behat.yml
```

The `--profile no-cc` arguments will disable the generation of code coverage. If you skip these arguments you will get code covarage of the Behat tests.

### PHPUnit

```
./vendor/bin/phpunit --verbose -c tests/phpunit
```

Include `--coverage-html <path>` if you want to generate code coverage report of the tests.

## Writing documentation

Imbo uses [Read the docs](https://readthedocs.org/projects/imbo/) for documentation, and all docs are located in the `docs` dir. The docs are written using Sphinx, and if you are contributing new features please add relevant docs.
