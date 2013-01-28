<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Helpers;

use DateTime,
    DateTimeZone;

/**
 * Date formatter helper class
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Core\Helpers
 */
class DateFormatter {
    /**
     * Get a formatted date
     *
     * @param DateTime $date An instance of DateTime
     * @return string Returns a formatted date string
     */
    public function formatDate(DateTime $date) {
        $date->setTimezone(new DateTimeZone('UTC'));

        return $date->format('D, d M Y H:i:s') . ' GMT';
    }
}
