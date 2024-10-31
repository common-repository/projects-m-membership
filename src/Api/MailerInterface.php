<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\Api;


interface MailerInterface
{
    /**
     * Sends an email with the given content.
     * The sender details should automatically be taken from the surrounding system.
     *
     * @param string $to
     * @param string $subject
     * @param string $content
     * @param array  $attachments
     */
    public function sendMail (string $to, string $subject, string $content, array $attachments = []);
}
