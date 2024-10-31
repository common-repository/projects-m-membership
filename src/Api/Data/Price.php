<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\Api\Data;


class Price extends AbstractEntity
{
    /**
     * @var int
     */
    public $amount;


    /**
     * @var int
     */
    public $interval;
}
