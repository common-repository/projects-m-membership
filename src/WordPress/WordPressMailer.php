<?php declare(strict_types=1);


namespace ProjectsM\MembershipWordpress\WordPress;

use ProjectsM\MembershipWordpress\Api\MailerInterface;


class WordPressMailer implements MailerInterface
{
    /**
     *
     */
    public function __construct ()
    {
        \add_filter(
            "wp_mail_content_type",
            function ()
            {
                return "text/html";
            }
        );
    }


    /**
     * @inheritdoc
     */
    public function sendMail (string $to, string $subject, string $content, array $attachments = [])
    {
        \wp_mail($to, $subject, $content, [], $attachments);
    }
}
