<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\Api;

use ProjectsM\MembershipWordpress\Api\Data\ApiData;
use ProjectsM\MembershipWordpress\WordPress\Plugin;
use ProjectsM\MembershipWordpress\WordPress\View;


class MembershipNotifications
{
    /**
     * @var View
     */
    private $view;


    /**
     * @var MailerInterface
     */
    private $mailer;


    /**
     * @var Plugin
     */
    private $plugin;


    /**
     *
     * @param View            $view
     * @param MailerInterface $mailer
     * @param Plugin          $plugin
     */
    public function __construct (View $view, MailerInterface $mailer, Plugin $plugin)
    {
        $this->view = $view;
        $this->mailer = $mailer;
        $this->plugin = $plugin;
    }


    /**
     * Sends the customer confirmation mail
     *
     * @param ApiData $apiData
     * @param array   $submittedData
     * @param string  $confirmationCode
     */
    public function sendCustomerConfirmationMail (ApiData $apiData, array $submittedData, string $confirmationCode)
    {
        $personalizedTexts = $this->getPersonalizedTexts($apiData, $submittedData);

        $subject = "{$apiData->client->name} — Anmeldung";

        $content = $this->view->render("mail/mail.html.twig", [
            "subject" => $subject,
            "website" => [
                "name" => \get_bloginfo("name"),
                "url" => \get_bloginfo("url"),
            ],
            "textContent" => $personalizedTexts["confirm_mail"],
            "actions" => [
                [
                    "label" => "Anmeldung jetzt bestätigen",
                    "url" => \admin_url("admin-ajax.php?action=pm_membership&id={$apiData->id}&confirm={$confirmationCode}"),
                ],
            ],
            "personalization" => $apiData->client->personalization,
        ]);

        $this->mailer->sendMail($submittedData["form"]["email"], $subject, $content);
    }


    /**
     * Sends the welcome mail to the customer
     *
     * @param ApiData $apiData
     * @param array   $submittedData
     * @param string  $pdf
     */
    public function sendCustomerWelcomeMail (ApiData $apiData, array $submittedData, string $pdf)
    {
        $attachmentsContents = \array_replace($apiData->client->notifications->welcome->attachments, [
            ($apiData->client->notifications->welcome->contractFileNamePrefix ?? "contract_") . time() . ".pdf" => $pdf,
        ]);

        $attachments = $this->transformFilesToAttachments($attachmentsContents);

        $personalizedTexts = $this->getPersonalizedTexts($apiData, $submittedData);
        $subject = "{$apiData->client->name} — Herzlich Willkommen";

        $content = $this->view->render("mail/mail.html.twig", [
            "subject" => $subject,
            "website" => [
                "name" => \get_bloginfo("name"),
                "url" => \get_bloginfo("url"),
            ],
            "textContent" => $personalizedTexts["welcome_mail"],
            "personalization" => $apiData->client->personalization,
        ]);

        // send mail to customer
        $this->mailer->sendMail($submittedData["form"]["email"], $subject, $content, $attachments);

        // send mail to admins
        foreach ($this->getAdminEmailAddresses($apiData->id) as $adminTo)
        {
            $this->mailer->sendMail($adminTo, $subject, $content, $attachments);
        }

        // remove temporary files
        $this->removeTemporaryFiles($attachments);

        // send recommendation mail
        $this->sendRecommendationMail($apiData, $submittedData);
    }


    /**
     * @param ApiData $apiData
     * @param array   $submittedData
     */
    private function sendRecommendationMail (ApiData $apiData, array $submittedData)
    {
        $recommendation = $apiData->client->recommendation;

        if (null === $recommendation)
        {
            return;
        }

        $attachments = $this->transformFilesToAttachments($recommendation->attachments);

        $content = $this->view->render("mail/mail.html.twig", [
            "subject" => $recommendation->subject,
            "website" => [
                "name" => \get_bloginfo("name"),
                "url" => \get_bloginfo("url"),
            ],
            "textContent" => $recommendation->text,
            "personalization" => $apiData->client->personalization,
            "actions" => $recommendation->ctas,
        ]);

        // send mail to customer
        $this->mailer->sendMail($submittedData["form"]["email"], $recommendation->subject, $content, $attachments);

        // remove temporary files
        $this->removeTemporaryFiles($attachments);
    }


    /**
     * @param array $files
     * @return array
     */
    private function transformFilesToAttachments (array $files) : array
    {
        if (empty($files))
        {
            return [];
        }

        $paths = [];
        $dir = \sys_get_temp_dir() . "/" . \uniqid("attachments", true);
        @\mkdir($dir, 0755);

        foreach ($files as $fileName => $fileContent)
        {
            $filePath = "{$dir}/{$fileName}";
            $handle = \fopen($filePath, "w+");
            \fwrite($handle, \base64_decode($fileContent));
            \fclose($handle);
            $paths[] = $filePath;
        }

        return $paths;
    }


    /**
     * @param array $files
     */
    private function removeTemporaryFiles (array $files)
    {
        if (empty($files))
        {
            return;
        }

        $dir = null;

        foreach ($files as $filePath)
        {
            @\unlink($filePath);
        }

        @\rmdir(\dirname($filePath));
    }


    /**
     * Sends the test e-mail
     *
     * @param ApiData $apiData
     * @param string  $recipient
     */
    public function sendTestEmail (ApiData $apiData, string $recipient)
    {
        $subject = "{$apiData->client->name} — Test-E-Mail";

        $content = $this->view->render("mail/mail.html.twig", [
            "subject" => $subject,
            "website" => [
                "name" => \get_bloginfo("name"),
                "url" => \get_bloginfo("url"),
            ],
            "textContent" => "Dies ist eine automatisch versendete Test-E-Mail.",
            "actions" => [
				[
					"url" => \get_bloginfo("url"),
					"label" => "Blog besuchen",
				],
				[
					 "url" => \admin_url(),
					 "label" => "Blog-Admin besuchen",
				],
            ],
            "personalization" => $apiData->client->personalization,
        ]);

        $this->mailer->sendMail($recipient, $subject, $content);
    }


    /**
     * Returns the admin email addresses
     *
     * @param int $contractId
     * @return array
     */
    private function getAdminEmailAddresses (int $contractId) : array
    {
        $emails = \trim((string) $this->plugin->getNotificationEmail($contractId));

        if ("" === $emails)
        {
            $emails = \trim((string) $this->plugin->getAdminEmail());
        }

        if ("" === $emails)
        {
            return [];
        }

        return \array_map('trim', \explode(";", $emails));
    }


    /**
     * Returns the personalized texts
     *
     * @param int   $contractId
     * @param array $submittedData
     * @return array
     */
    public function getPersonalizedTexts (ApiData $apiData, array $submittedData)
    {
        $salutation = ($submittedData["form"]["salutation"] === "m")
            ? "Sehr geehrter Herr"
            : "Sehr geehrte Frau";

        switch ($submittedData["form"]["title"])
        {
            case "dr":
                $salutation .= " Dr.";
                break;

            case "prof":
                $salutation .= " Prof.";
                break;

            case "prof_dr":
                $salutation .= " Prof. Dr.";
                break;
        }

        $salutation .= " {$submittedData["form"]["firstName"]} {$submittedData["form"]["lastName"]}";

        $replace = [
            "%anrede%" => $salutation,
        ];

        $notifications = $apiData->client->notifications;

        return [
            "greeting" => $apiData->client->personalization->configuratorRegards,
            "confirm_mail" => null !== $notifications->confirm->text
                ? \strtr($notifications->confirm->text, $replace)
                : "",
            "welcome_mail" => null !== $notifications->welcome->text
                ? \strtr($apiData->client->notifications->welcome->text, $replace)
                : "",
        ];
    }
}
