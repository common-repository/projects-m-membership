<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\Api\Data;


class Option extends AbstractEntity
{
    /**
     * @var int
     */
    public $id;


    /**
     * @var string
     */
    public $name;


    /**
     * @var string|null
     */
    public $description;


    /**
     * @var Price|null
     */
    public $price;


    /**
     * @inheritDoc
     */
    public function __construct (array $data)
    {
        parent::__construct($data);

        if (null !== $data["price"])
        {
            $this->price = new Price($data["price"]);
        }
    }
}
