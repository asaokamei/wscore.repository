<?php
namespace WScore\Repository\Helpers;

use DateTimeImmutable;

class CurrentDateTime
{
    private $now;

    /**
     * CurrentDateTime constructor.
     *
     * @param null $now
     */
    public function __construct($now = null)
    {
        $this->now = $now ?: new DateTimeImmutable();
    }

    /**
     * @param string $format
     * @return string
     */
    public function format($format)
    {
        return $this->now->format($format);
    }
}