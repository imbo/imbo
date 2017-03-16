<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

// Set the Fixtures path
define('FIXTURES_DIR', realpath(__DIR__ . '/Fixtures'));

// Require the FeatureContext file as it's not part of the regular autolading functionality
require __DIR__ . '/../behat/features/bootstrap/FeatureContext.php';
