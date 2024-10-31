<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\Api\Constraint;


class IbanConstraint implements ValidationConstraintInterface
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

        return 0 !== \preg_match('~^(DE\d{2}\d{8}\d{10}|AT\d{2}\d{5}\d{11}|CH\d{2}\d{5}[\dA-Z]{12})$~', $value);
    }
}
