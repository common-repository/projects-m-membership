<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\Api\Data\Notification;

use ProjectsM\MembershipWordpress\Api\Data\AbstractEntity;


class WelcomeNotification extends AbstractEntity
{
    /**
     * @var string|null
     */
    public $text;


    /**
     * @var string|null
     */
    public $contractFileNamePrefix;


    /**
     * @var array
     */
    public $attachments;
}
