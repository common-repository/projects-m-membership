<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\Api\Data;


use ProjectsM\MembershipWordpress\Api\Data\Notification\Notifications;


class Client extends AbstractEntity
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var Notifications
     */
    public $notifications;

    /**
     * @var ClientPersonalization
     */
    public $personalization;


    /**
     * @var Recommendation|null
     */
    public $recommendation;


    /**
     * @var bool|null
     */
    public $hasMagiclineApi;


    /**
     * @inheritDoc
     */
    public function __construct (array $data)
    {
        parent::__construct($data);
        $this->notifications = new Notifications($data["notifications"]);
        $this->personalization = new ClientPersonalization($data["personalization"]);

        if (null !== $data["recommendation"])
        {
            $this->recommendation = new Recommendation($data["recommendation"]);
        }
    }


}
