<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\Api\Data\Notification;

use ProjectsM\MembershipWordpress\Api\Data\AbstractEntity;


class ConfirmNotification extends AbstractEntity
{
    /**
     * @var string|null
     */
    public $text;
}
