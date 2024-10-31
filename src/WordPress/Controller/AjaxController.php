<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\WordPress\Controller;

use ProjectsM\MembershipWordpress\Api\CloudController;


class AjaxController
{
    /**
     * @var CloudController
     */
    private $cloudController;


    /**
     * @param CloudController $cloudController
     */
    public function __construct (CloudController $cloudController)
    {
        $this->cloudController = $cloudController;
    }


    /**
     *
     */
    public function handle ()
    {
        $privacyPageUrl = \function_exists('get_privacy_policy_url')
            ? \get_privacy_policy_url()
            : "";

        if ("" === trim($privacyPageUrl))
        {
            $privacyPageUrl = null;
        }

        $this->cloudController->handleRequest($privacyPageUrl);

        // prevent WordPress from adding additional content
        exit;
    }

	/**
	 */
	public function handleMagiclineTransfer ()
	{
		$queryData = \stripslashes_deep($_GET);
		$signupId = isset($queryData["signup"]) && \ctype_digit($queryData["signup"])
			? $queryData["signup"]
			: null;

		$this->cloudController->handleMagiclineTransfer($signupId);

		// prevent WordPress from adding additional content
		exit;
	}
}
