<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\Api\Constraint;


class AreaCodeConstraint implements ValidationConstraintInterface
{
    /**
     * @inheritDoc
     */
    public function isValid ($value) : bool
    {
        if (null === $value)
        {
            return true;
        }

        if (!\is_string($value))
        {
            return false;
        }

        return 0 !== \preg_match('~^\\d{4,5}$~', $value);
    }

}
