# Contributing to Imbo

If you want to contribute to Imbo please follow these guidelines.

## Requirements for local development

You should ideally use PHP 8.3 since that is the lowest supported version. Features added to PHP 8.4 and later MUST NOT be used as long as we want to support 8.3.

The GitHub workflow will run tests / QA against PHP 8.3, 8.4 and 8.5.

Refer to [composer.json](../composer.json) for more requirements.

## Installing dependencies

Run the following command to install dependencies using [Composer](https://getcomposer.org):

    composer install

## Running tests and static analysis

[PHPUnit](https://phpunit.de) is used for unit tests. Run the test suite using a Composer script:

    composer run test:unit

[imbo/behat-api-extension](https://github.com/imbo/behat-api-extension) is used for integration tests. There are different suites for different sets of adapters. Refer to [composer.json](../composer.json) for a complete list. Before running the integration tests you need to start up a few Docker containers as well as a running Imbo server. You can do this using Docker Compose:

    docker compose up -d

and a Composer script for the Imbo development server:

    composer run dev

Once you have the required services running you can run the integration tests using the following Composer script:

    composer run test:integration:<suite>

You can run all of them using:

    composer run test:integration

[PHPStan](https://phpstan.org) is used for static code analysis. Run the analysis using a Composer script:

    composer run sa

## Coding standards

Imbo follows the [Imbo coding standard](https://github.com/imbo/imbo-coding-standard), and runs [php-cs-fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer) as a step in the CI workflow, failing the workflow if there are discrepancies. You can also run the check locally using a Composer script:

    composer run cs

You can also have php-cs-fixer automatically fix the issues:

    composer run cs:fix

## Documentation

[Sphinx](https://www.sphinx-doc.org) is used for end-user documentation. The documentation resides in the `docs` directory. To generate the current documentation after checking out your fork, run the following command:

    composer run docs

If the command fails you are most likely missing packages not installable by Composer. Install missing packages and re-run the command to generate docs.

## Reporting issues

Use the [issue tracker on GitHub](https://github.com/imbo/imbo/issues) when reporting an issue.

## Submitting a pull request

If you want to implement a new feature, create a fork, create a feature branch called `feature/my-awesome-feature`, and send a pull request. The feature needs to be fully documented and tested before it will be merged.

If the pull request is a bug fix, remember to file an issue in the issue tracker first, then create a branch called `issue/<issue number>`. One or more test cases to verify the bug is required. When creating specific test cases for issues, please add a `@see` tag to the docblock or the added test case. For instance:

```php
/**
 * @see https://github.com/imbo/imbo/issues/<issue number>
 */
public function testSomething(): void
{
    // ...
}
```

## Conventional commits

Use [conventional commits](https://www.conventionalcommits.org/) for all commits. When a pull request is merged it will be squashed. There is a `commit-msg` Git hook script that you can use to validate your commits locally. Enable the script by running the following command:

    ln -s ../../scripts/conventional-commit-msg.sh .git/hooks/commit-msg
