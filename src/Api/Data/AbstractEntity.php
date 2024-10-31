<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\Api\Data;


class AbstractEntity
{
    use ArraySerializableTrait;

    /**
     * @param array $data
     */
    public function __construct (array $data)
    {
        foreach ($this as $property => $value)
        {
            $this->{$property} = $data[$property] ?? null;
        }
    }
}
