<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\Api\Data;


class Runtime extends AbstractEntity
{
    /**
     * @var int
     */
    public $id;


    /**
     * @var int
     */
    public $length;


    /**
     * @var string
     */
    public $title;


    /**
     * @var int
     */
    public $recurringFee;


    /**
     * @var string|null
     */
    public $recurringDescription;


    /**
     * @var int
     */
    public $entryFee;


    /**
     * @var string|null
     */
    public $entryDescription;
}
