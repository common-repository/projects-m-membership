<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\Api;

use ProjectsM\MembershipWordpress\Api\Data\ApiData;
use ProjectsM\MembershipWordpress\WordPress\Plugin;
use ProjectsM\MembershipWordpress\WordPress\View;


class CloudController
{
    const EVENT_REGISTERED = "registered";
    const EVENT_CONFIRMED = "confirmed";

    /**
     * @var MembershipCloud
     */
    private $cloud;


    /**
     * @var MembershipNotifications
     */
    private $notifications;


    /**
     * @var View
     */
    private $view;


    /**
     * @var Plugin
     */
    private $plugin;


    /**
     * @var array
     */
    private $listeners = [];


    /**
     * @var FileStorage
     */
    private $fileStorage;


    /**
     * @param MembershipCloud         $cloud
     * @param MembershipNotifications $notifications
     * @param View                $view
     * @param Plugin              $plugin
     * @param FileStorage         $fileStorage
     */
    public function __construct (MembershipCloud $cloud, MembershipNotifications $notifications, View $view, Plugin $plugin, FileStorage $fileStorage)
    {
        $this->cloud = $cloud;
        $this->notifications = $notifications;
        $this->view = $view;
        $this->plugin = $plugin;
        $this->fileStorage = $fileStorage;
    }


    /**
     * Handles the request
     *
     * @param null|string $privacyPageUrl
     * @param null|string $method      The HTTP request method. If null is given, the global PHP default is used.
     * @param array|null  $queryData
     * @param null|array  $requestData The POST / request data. If null is given, the global PHP default is used.
     */
    public function handleRequest (string $privacyPageUrl = null, string $method = null, array $queryData = null, array $requestData = null)
    {
        $method = $method ?? (string) $_SERVER['REQUEST_METHOD'];
        $queryData = $queryData ?? \stripslashes_deep($_GET);
        $requestData = $requestData ?? \stripslashes_deep($_POST);
        $codeConfirmation = $queryData["confirm"] ?? null;
        $contractId = $queryData["id"] ?? null;

        if (null !== $contractId && 0 === \preg_match('~^\d+$~', $contractId))
        {
            $this->sendJson(["status" => "error", "error" => "invalid_contract_format"]);
            return;
        }

        $needsFullData = ("GET" !== $method) || \is_string($codeConfirmation);
        $apiData = $this->cloud->fetchApiData((int) $contractId, $needsFullData);

        if (null === $apiData)
        {
            $this->sendJson(["status" => "error", "error" => "invalid_contract"]);
            return;
        }

        if ("GET" === $method)
        {
            if (\is_string($codeConfirmation))
            {
                $this->confirmRegistration($apiData, $codeConfirmation);
            }
            else
            {
                $this->sendJson([
                    "status" => "ok",
                    "data" => $apiData->toArray(),
                    "privacyPageUrl" => $privacyPageUrl,
                ]);
            }
        }
        else
        {
            $this->handleRegistration($apiData, $requestData);
        }
    }


    /**
     * Handles registration confirmation
     *
     * @param ApiData $apiData
     * @param string  $code
     */
    private function confirmRegistration (ApiData $apiData, string $code)
    {
        global $wpdb;

        $data = $this->getEntryByConfirmationCode($code);
        $isError = false;

        if (null === $data)
        {
            $isError = true;
        }
        else if ($data["time_confirmed"] === null)
        {
            // not yet confirmed, so request PDF
            $submittedData = \json_decode($data["data"], true);
            $submittedData["time_created"] = $data["time_created"];
            $pdfResult = $this->cloud->generatePdf((int) $data["contract_id"], $submittedData);

            if ($pdfResult["status"] === "ok")
            {
                $pdfContent = $pdfResult["pdf"];
                $this->storePdf(\base64_decode($pdfContent), $data);

                // send notification mail
                $this->notifications->sendCustomerWelcomeMail($apiData, $submittedData, $pdfContent);

                // update time confirmed in DB
                $wpdb->update("{$wpdb->prefix}pm_membership_sign_ups", [
                    "time_confirmed" => \current_time("mysql"),
                ], [
                    "id" => $data["id"],
                ]);

                // dispatch event
                $this->dispatchEvent(self::EVENT_CONFIRMED, [
                    "pdf" => \base64_decode($pdfContent),
                    "data" => $submittedData,
                    "signupId" => $data["id"],
                ]);
            }
            else
            {
                $isError = true;
            }
        }

        header("Content-Type: text/html; charset=UTF-8");
        echo $this->view->render("frontend/confirmation.html.twig", [
            "cssFile" => $this->plugin->generateUrl('build/css/membership.css'),
            "isError" => $isError,
            "website" => [
                "name" => \get_bloginfo("name"),
                "url" => \get_bloginfo("url"),
            ],
            "personalization" => $apiData->client->personalization,
        ]);
    }


    /**
     * @param string $pdf
     * @param array  $row   the row from the database for this record
     */
    private function storePdf (string $pdf, array $row)
    {
        // use confirmation code as this is random enough - it is created using random_bytes.
        $code = $row["confirmation_code"];
        $storagePath = $this->fileStorage->getPdfStoragePath($code);

        if (!\is_dir($storagePath))
        {
            /** @noinspection MkdirRaceConditionInspection */
            @\mkdir($storagePath, 0755, true);
        }

        \file_put_contents("{$storagePath}/{$code}.pdf", $pdf);
    }


    /**
     * Action: handles the registration
     *
     * @param ApiData $apiData
     * @param array   $data
     */
    private function handleRegistration (ApiData $apiData, array $data)
    {
        global $wpdb;

        $validator = new DataValidator($apiData);

        // normalize data
        $data = $this->normalizeRegistrationData($data);

        if ($validator->isValid($data, $apiData))
        {
            $confirmationCode = $this->generateUnusedConfirmationCode();

            $wpdb->insert("{$wpdb->prefix}pm_membership_sign_ups", [
                "data" => \json_encode($data, \JSON_UNESCAPED_SLASHES),
                "confirmation_code" => $confirmationCode,
                "contract_id" => $apiData->id,
            ]);

            // send confirmation mail
            $this->notifications->sendCustomerConfirmationMail($apiData, $data, $confirmationCode);

            // dispatch event
            $this->dispatchEvent(self::EVENT_REGISTERED, [
                "data" => $data,
                "config" => $apiData,
            ]);

            $this->sendJson(["status" => "ok"]);
        }
        else
        {
            $this->sendJson(["status" => "error"]);
        }
    }

	/**
	 * @param string|null $signupId
	 * @return void
	 */
	public function handleMagiclineTransfer ($signupId)
	{
		global $wpdb;

		$redirectBack = function ()
		{
			// redirect back
			\wp_safe_redirect($this->plugin->generateAdminUrl("sign-ups"));
			exit;
		};


		if (null === $signupId)
		{
			$redirectBack();
			return;
		}

		$storedData = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}pm_membership_sign_ups WHERE id = %s",
					$signupId
				),
				\ARRAY_A
			)[0] ?? null;

		if (
			null === $storedData
			|| (
				\is_array($storedData["magicline_data"])
				&& isset($storedData["magicline_data"]["exported"])
				&& true === $storedData["magicline_data"]["exported"]
			)
		)
		{
			$redirectBack();
			return;
		}

		$result = $this->cloud->submitToMagicline($signupId);

		if (null !== $result)
		{
			$wpdb->update("{$wpdb->prefix}pm_membership_sign_ups", [
				"magicline_data" => \json_encode($result),
			], [
				"id" => $signupId,
			]);
		}

		$redirectBack();
	}


    /**
     * @param array $data
     * @return array
     */
    private function normalizeRegistrationData (array $data) : array
    {
        // reset options to empty array, if just null is given
        $data["options"] = $data["options"] ?? [];

        // reset empty string values in the form to null
        if (\is_array($data["form"]))
        {
            foreach ($data["form"] as $key => $value)
            {
                if ("" === $value)
                {
                    $data["form"][$key] = null;
                }
            }
        }

        return $data;
    }


    /**
     * @param int    $contract
     * @param string $recipient
     * @return bool
     */
    public function sendTestEmail (int $contract, string $recipient) : bool
    {
        $apiData = $this->cloud->fetchApiData($contract, true);

        if (null === $apiData)
        {
            return false;
        }

        $this->notifications->sendTestEmail($apiData, $recipient);
        return true;
    }


    /**
     * Sends JSON to the browser
     *
     * @param array|null $data
     */
    private function sendJson (array $data = null)
    {
        if (null === $data)
        {
            $data = [
                "status" => "error",
                "error" => "no_data",
            ];
        }

        header("Content-Type: application/json; charset=UTF-8");
        echo \json_encode($data, \JSON_UNESCAPED_SLASHES);
    }


    /**
     * Generates an unused confirmation code
     *
     * @return string
     */
    private function generateUnusedConfirmationCode () : string
    {
        do
        {
            $code = \bin2hex(\random_bytes(12));
        }
        while (null !== $this->getEntryByConfirmationCode($code));

        return $code;
    }


    /**
     * Returns the entry by confirmation code
     *
     * @param string $confirmationCode
     * @return array|null
     */
    private function getEntryByConfirmationCode (string $confirmationCode)
    {
        global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}pm_membership_sign_ups WHERE confirmation_code LIKE '" . $wpdb->_real_escape($confirmationCode) . "'", \ARRAY_A);

        return !empty($results)
            ? $results[0]
            : null;
    }


    /**
     * @param string   $event
     * @param callable $callback
     */
    public function addEventListeners (string $event, callable $callback)
    {
        $this->listeners[$event][] = $callback;
    }


    /**
     * @param string $event
     * @param null   $payload
     */
    private function dispatchEvent (string $event, $payload = null)
    {
        $listeners = $this->listeners[$event] ?? [];

        foreach ($listeners as $listener)
        {
            $listener($payload);
        }
    }
}
