<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\WordPress\Controller;

use ProjectsM\MembershipWordpress\Api\CloudController;
use ProjectsM\MembershipWordpress\Api\MembershipCloud;
use ProjectsM\MembershipWordpress\WordPress\Plugin;
use ProjectsM\MembershipWordpress\WordPress\View;


class IndexController extends AbstractController
{
    /**
     * @var CloudController
     */
    private $cloudController;


    /**
     * @var MembershipCloud
     */
    private $cloud;


    /**
     * @inheritDoc
     */
    public function __construct (View $view, Plugin $plugin, CloudController $cloudController, MembershipCloud $cloud)
    {
        parent::__construct($view, $plugin);
        $this->cloudController = $cloudController;
        $this->cloud = $cloud;
    }


    /**
     * @return bool
     */
    public function overviewAction ()
    {
        $successMessage = null;
        $errorMessage = null;
        $testRecipient = null;
        $testContract = null;
        $contracts = $this->fetchAllContracts();

        if ($this->hasFormSubmission())
        {
            $apiKey = $this->getFormData("api_key");
            $adminEmail = $this->getFormData("admin_email");
            $testRecipient = $this->getFormData("test_recipient");
            $testContract = $this->getFormData("test_contract");

            if (\is_string($apiKey))
            {
                $this->plugin->storeApiKey($apiKey);
                $successMessage = "Die Daten wurden erfolgreich gespeichert.";
            }

            if (\is_string($adminEmail))
            {
                $this->plugin->storeAdminEmail($adminEmail);
                $successMessage = "Die Daten wurden erfolgreich gespeichert.";
            }

            if (null !== $testRecipient || null !== $testContract)
            {
                if (\is_string($testRecipient) && \is_string($testContract) && 0 !== \preg_match('~^\d+$~', $testContract))
                {
                    $wasSent = $this->cloudController->sendTestEmail((int) $testContract, $testRecipient);

                    if ($wasSent)
                    {
                        $successMessage = "Die Test-E-Mail wurde erfolgreich verschickt.";
                    }
                    else
                    {
                        $errorMessage = "Es wurde kein Vertrag mit der angegebenen ID gefunden.";
                    }
                }
                else
                {
                    $errorMessage = "Ungültige Eingabe: bitte eine Zahl als Vertrag engeben und einen Empfänger angeben.";
                }
            }

            foreach ($contracts as &$contract)
            {
                $value = $this->getFormData("admin_email_{$contract['id']}");

                if (\is_string($value))
                {
                    $this->plugin->storeNotificationEmail($contract["id"], $value);
                    $successMessage = "Die Daten wurden erfolgreich gespeichert.";
                    $contract["value"] = $value;
                }
            }
        }

        return $this->render("admin/index/overview.html.twig", [
            "accountUrl" => MembershipCloud::ACCOUNT_URL,
            "apiKey" => $this->plugin->getApiKey(),
            "adminEmail" => $this->plugin->getAdminEmail(),
            "successMessage" => $successMessage,
            "errorMessage" => $errorMessage,
            "formAction" => $this->plugin->generateAdminUrl(),
            "pageTitle" => "Membership",
            "testRecipient" => $testRecipient,
            "testContract" => $testContract,
            "allContracts" => $contracts,
        ]);
    }


    /**
     * Fetches all contracts
     *
     * @return array
     */
    private function fetchAllContracts () : array
    {
        $data = $this->cloud->fetchAllContracts();

        foreach ($data as &$contract)
        {
            $contract["value"] = $this->plugin->getNotificationEmail($contract["id"]);
        }

        return $data;
    }
}
