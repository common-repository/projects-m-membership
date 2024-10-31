<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\Api\Data;


trait ArraySerializableTrait
{
    /**
     * @return array
     */
    public function toArray () : array
    {
        $result = [];

        foreach ($this as $property => $value)
        {
            $result[$property] = $this->serialize($value);
        }

        return $result;
    }


    /**
     * @param $value
     * @return array
     */
    private function serialize ($value)
    {
        if (\is_array($value))
        {
            return \array_map([$this ,"serialize"], $value);
        }

        return ($value instanceof AbstractEntity)
            ? $value->toArray()
            : $value;
    }
}
