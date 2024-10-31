<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\Api\Constraint;


class NotBlankConstraint implements ValidationConstraintInterface
{
    /**
     * @inheritDoc
     */
    public function isValid ($value) : bool
    {
        return null !== $value;
    }
}
