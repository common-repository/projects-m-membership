<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\Api\Data;



class ApiData
{
    use ArraySerializableTrait;


    /**
     * @var int
     */
    public $id;


    /**
     * @var string|null
     */
    public $firstDay;


    /**
     * @var Runtime[]
     */
    public $runtimes = [];


    /**
     * @var OptionsCategory[]
     */
    public $optionsCategories = [];


    /**
     * @var Client
     */
    public $client;


    /**
     * @param array $data
     */
    public function __construct (array $data)
    {
        $this->id = $data["id"];
		$this->firstDay = $data["firstDay"];

        $this->runtimes = \array_map(
            function (array $value)
            {
                return new Runtime($value);
            },
            $data["runtimes"]
        );

        $this->optionsCategories = \array_map(
            function (array $value)
            {
                return new OptionsCategory($value);
            },
            $data["optionsCategories"]
        );

        $this->client = new Client($data["client"]);
    }
}
