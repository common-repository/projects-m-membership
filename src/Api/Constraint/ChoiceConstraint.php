<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\Api\Constraint;


class ChoiceConstraint implements ValidationConstraintInterface
{
    /**
     * @var array
     */
    private $choices;


    /**
     * @param array $choices
     */
    public function __construct (array $choices)
    {

        $this->choices = $choices;
    }

    /**
     * @inheritDoc
     */
    public function isValid ($value) : bool
    {
        if (null === $value)
        {
            return true;
        }

        return \is_scalar($value)
            ? \in_array($value, $this->choices, true)
            : false;
    }
}
