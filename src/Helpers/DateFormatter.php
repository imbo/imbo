<?php
namespace Imbo\Helpers;

use DateTime,
    DateTimeZone;

/**
 * Date formatter helper class
 *
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
