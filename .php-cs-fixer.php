<?php declare(strict_types=1);
require 'vendor/autoload.php';

$finder = (new Symfony\Component\Finder\Finder())
    ->files()
    ->name('*.php')
    ->in(__DIR__)
    ->exclude('vendor');

return (new Imbo\CodingStandard\Config())
    ->setFinder($finder);
