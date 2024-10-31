<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\Api\Constraint;


class BirthdayConstraint implements ValidationConstraintInterface
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

        $dateTime = \DateTimeImmutable::createFromFormat("d.m.Y", $value);
        $today = new \DateTimeImmutable();

        if (false === $dateTime)
        {
            return false;
        }

        $diff = $dateTime->diff($today);
        return ($diff->invert !== 1) && ($diff->y >= 16);
    }
}
