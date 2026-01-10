<?php declare(strict_types=1);

namespace Imbo\Helpers;

use DateTime;
use DateTimeZone;

class DateFormatter
{
    /**
     * Get a formatted date.
     *
     * @param DateTime $date An instance of DateTime
     *
     * @return string Returns a formatted date string
     */
    public function formatDate(DateTime $date): string
    {
        return $date
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('D, d M Y H:i:s').' GMT';
    }
}
