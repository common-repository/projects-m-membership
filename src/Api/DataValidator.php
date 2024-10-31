<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\Api;


use ProjectsM\MembershipWordpress\Api\Constraint\AreaCodeConstraint;
use ProjectsM\MembershipWordpress\Api\Constraint\BirthdayConstraint;
use ProjectsM\MembershipWordpress\Api\Constraint\ChoiceConstraint;
use ProjectsM\MembershipWordpress\Api\Constraint\EmailConstraint;
use ProjectsM\MembershipWordpress\Api\Constraint\IbanConstraint;
use ProjectsM\MembershipWordpress\Api\Constraint\NotBlankConstraint;
use ProjectsM\MembershipWordpress\Api\Constraint\ValidationConstraintInterface;
use ProjectsM\MembershipWordpress\Api\Data\ApiData;


class DataValidator
{
    /**
     * @var ApiData
     */
    private $apiData;


    /**
     * @param ApiData $apiData
     */
    public function __construct (ApiData $apiData)
    {
        $this->apiData = $apiData;
    }


    public function isValid (array $signUpData, ApiData $apiData) : bool
    {
        if (!$this->isRuntimeValid($signUpData["runtime"] ?? null))
        {
            return false;
        }

        if (!$this->areOptionsValid($signUpData["options"] ?? null))
        {
            return false;
        }

        return $this->isFormValid(
            $signUpData["form"] ?? null,
            $apiData
		);
    }


    /**
     * Returns whether the runtime is valid
     *
     * @param int|null $runtimeId
     * @return bool
     */
    private function isRuntimeValid ($runtimeId) : bool
    {
        if (!$this->isValidNumber($runtimeId))
        {
            return false;
        }

        $runtimeId = (int) $runtimeId;

        foreach ($this->apiData->runtimes as $runtime)
        {
            if ($runtime->id === $runtimeId)
            {
                return true;
            }
        }

        return false;
    }


    /**
     * Validates that the options settings has a valid structure
     *
     * @param $options
     * @return bool
     */
    private function areOptionsValid ($options) : bool
    {
        if (!\is_array($options))
        {
            return false;
        }

        foreach ($options as $categoryId => $optionMapping)
        {
            if (!\is_array($optionMapping) || !$this->isValidNumber($categoryId))
            {
                return false;
            }

            foreach ($optionMapping as $optionId => $isSelected)
            {
                if (!$this->isValidNumber($optionId))
                {
                    return false;
                }
            }
        }

        return true;
    }

	/**
	 * Returns the valid start dates
	 */
	private function generateValidStartDates (string $firstDay = null) : array
	{
		$today = new \DateTimeImmutable();
		$parsedFirstDay = null !== $firstDay
			? \DateTimeImmutable::createFromFormat("Y-m-d", $firstDay)
			: null;
		$nextMonth = (new \DateTimeImmutable())
			->setDate((int) $today->format("Y"), (int) $today->format("m") + 1, 1)
			->setTime(0, 0);
		$dates = [];

		$beginning = null !== $parsedFirstDay && $parsedFirstDay > $nextMonth
			? $parsedFirstDay
			: $nextMonth;

		for ($offset = 0; $offset <= 2; ++$offset)
		{
			$date = (new \DateTimeImmutable())
				->setDate((int) $beginning->format("Y"), (int) $beginning->format("m") + $offset, 1)
				->setTime(0, 0);

			$dates[] = $date->format("Y-m-d");
		}

		return $dates;
	}


    /**
     * Returns whether the form data is valid
     *
     * @param array $formData
     * @return bool
     */
    private function isFormValid ($formData, ApiData $apiData) : bool
    {
        if (!\is_array($formData))
        {
            return false;
        }

        $optionalFieldsSelected = "1" === $formData["presenteeActive"];

        // this list needs to be kept in-sync with the client side constraint list in the JSX component {@see UserDataFormStepContent}
        $fieldConstraints = [
            "contractStart" => [
                new NotBlankConstraint(),
                new ChoiceConstraint($this->generateValidStartDates($apiData->firstDay)),
            ],
            "salutation" => [
                new NotBlankConstraint(),
                new ChoiceConstraint(["m", "f"]),
            ],
            "title" => [
                new ChoiceConstraint(["dr", "prof", "prof_dr"]),
            ],
            "lastName" => [
                new NotBlankConstraint(),
            ],
            "firstName" => [
                new NotBlankConstraint(),
            ],
            "birthday" => [
                new NotBlankConstraint(),
                new BirthdayConstraint(),
            ],
            "street" => [
                new NotBlankConstraint(),
            ],
            "areaCode" => [
                new NotBlankConstraint(),
                new AreaCodeConstraint(),
            ],
            "city" => [
                new NotBlankConstraint(),
            ],
            "phone" => [],
            "mobile" => [
                new NotBlankConstraint(),
            ],
            "email" => [
                new NotBlankConstraint(),
                new EmailConstraint(),
            ],

            // optional fields
            "presenteeActive" => [],
            "presenteeName" => [
                new NotBlankConstraint(),
            ],
            "presenteeBirthday" => [
                new NotBlankConstraint(),
                new BirthdayConstraint(),
            ],
            "presenteeEmail" => [
                new NotBlankConstraint(),
                new EmailConstraint(),
            ],
            "presenteeStreet" => [
                new NotBlankConstraint(),
            ],
            "presenteeAreaCode" => [
                new NotBlankConstraint(),
                new AreaCodeConstraint(),
            ],
            "presenteeCity" => [
                new NotBlankConstraint(),
            ],

            // rest of the regular fields
            "iban" => [
                new NotBlankConstraint(),
                new IbanConstraint(),
            ],
            "bic" => [
                new NotBlankConstraint(),
            ],
            "accountHolder" => [
                new NotBlankConstraint(),
            ],
            "accountInstitute" => [
                new NotBlankConstraint(),
            ],
        ];

        // first check for unknown fields reject if some are found
        foreach ($formData as $key => $value)
        {
            if (!\array_key_exists($key, $fieldConstraints))
            {
                return false;
            }
        }

        // then validate each field
        foreach ($fieldConstraints as $name => $constraints)
        {
            $value = $formData[$name] ?? null;

            if (0 === strpos($name, "presentee") && !$optionalFieldsSelected)
            {
                $constraints = [];
            }

            /** @var ValidationConstraintInterface $constraint */
            foreach ($constraints as $constraint)
            {
                if (!$constraint->isValid($value))
                {
                    return false;
                }
            }
        }

        return true;
    }


    /**
     * Returns whether the given value is valid number
     *
     * @param mixed $value
     * @return bool
     */
    private function isValidNumber ($value) : bool
    {
        return \is_int($value) || 0 !== \preg_match('~^\\d+$~', $value);
    }
}
