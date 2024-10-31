<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\Api\Data;


class Recommendation extends AbstractEntity
{
    /**
     * @var string
     */
    public $subject;


    /**
     * @var string
     */
    public $text;


    /**
     * @var array
     */
    public $ctas;


    /**
     * @var array
     */
    public $attachments;
}
