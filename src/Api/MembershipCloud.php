<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\Api;

use Composer\CaBundle\CaBundle;
use ProjectsM\MembershipWordpress\Api\Data\ApiData;


class MembershipCloud
{
    const CLOUD_URL = "https://membership.projects-m.de";
    const API_URL = self::CLOUD_URL . "/api";
    const ACCOUNT_URL = self::CLOUD_URL . "/account";


    /**
     * @var null|int
     */
    private $client;


    /**
     * @var null|string
     */
    private $secret;


    /**
     *
     * @param null|string $apiToken
     */
    public function __construct (string $apiToken = null)
    {
        $this->parseApiToken($apiToken);
    }


    /**
     * Parses the API secret
     *
     * @param null|string $apiSecret
     */
    private function parseApiToken (string $apiSecret = null)
    {
        // Invalid: no secret given
        if (null === $apiSecret)
        {
            return;
        }

        $parts = \explode("_", $apiSecret);

        // invalid format
        if (2 !== count($parts))
        {
            return;
        }

        // first part must be a number
        if (false === \preg_match('~^[0-9]+$~', $parts[0]))
        {
            return;
        }

        $this->client = (int) $parts[0];
        $this->secret = $parts[1];
    }


    /**
     * @return bool
     */
    public function hasApiKey () : bool
    {
        return null !== $this->secret;
    }


    /**
     * Fetches the config data
     *
     * @param int  $contractId
     * @param bool $fullData
     * @return ApiData|null
     */
    public function fetchApiData (int $contractId, bool $fullData)
    {
        $result = $this->sendContractRequest($contractId, [
            "action" => "fetch-config",
            "full" => $fullData,
        ]);

        return (null !== $result && "ok" === $result["status"])
            ? new ApiData($result["contract"])
            : null;
    }


    /**
     * Generates the PDF on the server
     *
     * @param int   $contractId
     * @param array $data
     * @return array|null
     */
    public function generatePdf (int $contractId, array $data)
    {
        return $this->sendContractRequest($contractId, [
            "action" => "generate-pdf",
            "data" => $data,
        ]);
    }


    /**
     * @return array|null
     */
    public function fetchAllContracts ()
    {
        $data = $this->sendGenericRequest("fetch-contracts");

        return "ok" === $data["status"]
            ? $data["contracts"]
            : [];
    }

	/**
	 * @return array|null
	 */
	public function submitToMagicline (string $entryId)
	{
		/** @var $wpdb \wpdb */
		global $wpdb;

		$result = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}pm_membership_sign_ups WHERE id = %s",
				$entryId
			),
			\ARRAY_A
		);
		$data = $result[0] ?? null;

		if (null !== $data && 1 !== \count($result))
		{
			return null;
		}

		$data = $this->sendContractRequest((int) $data["contract_id"], [
			"action" => "submit-magicline",
			"data" => $data,
		]);

		return "ok" === $data["status"]
			? $data["data"]
			: [];
	}


    /**
     * Sends a request for the contracts-API
     *
     * @param int    $contractId
     * @param array  $data
     * @return array|null
     */
    private function sendContractRequest (int $contractId, array $data)
    {
        return $this->sendRequest((string) $contractId, $data);
    }


    /**
     * Sends a request for the generic API
     *
     * @param string $action
     * @param array  $data
     * @return array|null
     */
    private function sendGenericRequest (string $action, array $data = [])
    {
        return $this->sendRequest($action, $data);
    }


    /**
     * Sends a request
     *
     * @param int    $contractId
     * @param array  $data
     * @return array|null
     */
    private function sendRequest (string $path, array $data)
    {
        // abort if no secret is set
        if (null === $this->secret)
        {
            return null;
        }

        $data = \json_encode([
            "c" => $this->client,
            "k" => $this->secret,
            "p" => $data,
        ]);

        // fetch CA option
        $caPathOrFile = CaBundle::getSystemCaRootBundlePath();

        $channel = \curl_init(self::API_URL . "/{$path}");
        \curl_setopt($channel, \CURLOPT_CUSTOMREQUEST, "POST");
        \curl_setopt($channel, \CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($channel, \CURLOPT_POSTFIELDS, $data);
        \curl_setopt($channel, \CURLOPT_HTTPHEADER, [
            "Content-Type: application/json; charset=UTF-8",
            "Accept: application/json",
            "Content-Length: " . \strlen($data),
        ]);
        \curl_setopt($channel, \CURLOPT_SSL_VERIFYHOST, 2);
        \curl_setopt($channel, \CURLOPT_SSL_VERIFYPEER, true);

        if (\is_dir($caPathOrFile) || (\is_link($caPathOrFile) && \is_dir(\readlink($caPathOrFile))))
        {
            \curl_setopt($channel, \CURLOPT_CAPATH, $caPathOrFile);
        }
        else
        {
            \curl_setopt($channel, \CURLOPT_CAINFO, $caPathOrFile);
        }

        $result = \curl_exec($channel);
        \curl_close($channel);

        if (false === $result)
        {
            return ["status" => "error", "error" => "request_failed"];
        }

        $jsonResult = \json_decode($result, true);

        return \is_array($jsonResult)
            ? $jsonResult
            : ["status" => "error", "error" => "failed_decoding"];
    }
}
