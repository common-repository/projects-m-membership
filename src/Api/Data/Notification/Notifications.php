<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\Api\Data\Notification;

use ProjectsM\MembershipWordpress\Api\Data\AbstractEntity;


class Notifications extends AbstractEntity
{
    /**
     * @var ConfirmNotification
     */
    public $confirm;


    /**
     * @var WelcomeNotification
     */
    public $welcome;

    /**
     * @inheritDoc
     */
    public function __construct (array $data)
    {
        parent::__construct($data);

        $this->confirm = new ConfirmNotification($data["confirm"]);
        $this->welcome = new WelcomeNotification($data["welcome"]);
    }
}
