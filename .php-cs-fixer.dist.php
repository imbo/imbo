<?php declare(strict_types=1);
require 'vendor/autoload.php';

$finder = new PhpCsFixer\Finder();
$config = new Imbo\CodingStandard\Config();

return $config
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setFinder($finder->in(__DIR__)->append([__FILE__]));
