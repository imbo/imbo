<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Exception;

/**
 * Duplicate Image Identifier exception - thrown if the image identifier already exists in the underlying database.
 *
 * @author Mats Lindh <mats@lindh.no>
 * @package Exceptions
 */
class DuplicateImageIdentifierException extends RuntimeException {}
