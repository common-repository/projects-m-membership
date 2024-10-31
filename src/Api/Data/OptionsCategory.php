<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\Api\Data;


class OptionsCategory extends AbstractEntity
{
    /**
     * @var int
     */
    public $id;


    /**
     * @var string
     */
    public $headline;


    /**
     * @var int
     */
    public $selectionMethod;


    /**
     * @var bool
     */
    public $showInOverview;


    /**
     * @var Option[]
     */
    public $options = [];


    /**
     * @inheritDoc
     */
    public function __construct (array $data)
    {
        parent::__construct($data);

        $this->options = \array_map(
            function (array $value)
            {
                return new Option($value);
            },
            $data["options"]
        );
    }

}
