<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\Api\Constraint;


interface ValidationConstraintInterface
{
    /**
     * @param $value
     */
    public function isValid ($value) : bool;
}
