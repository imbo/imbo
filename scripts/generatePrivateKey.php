#!/usr/bin/env php
<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo;

$strong = false;
$maxTries = 10;
$i = 0;

while (!$strong && $maxTries > $i++) {
    $data = openssl_random_pseudo_bytes(64, $strong);
}

if (!$strong) {
    echo "Could not generate private key." . PHP_EOL;
    exit;
}

echo hash('sha256', $data) . PHP_EOL;
