<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\WordPress\Controller;

use ProjectsM\MembershipWordpress\Api\FileStorage;
use ProjectsM\MembershipWordpress\Api\MembershipCloud;
use ProjectsM\MembershipWordpress\WordPress\Admin\SignUpsTable;
use ProjectsM\MembershipWordpress\WordPress\Plugin;
use ProjectsM\MembershipWordpress\WordPress\View;


class SignUpsController extends AbstractController
{
    /**
     * @var MembershipCloud
     */
    private $cloud;


    /**
     * @var FileStorage
     */
    private $fileStorage;


    /**
     * @inheritDoc
     */
    public function __construct (View $view, Plugin $plugin, MembershipCloud $cloud, FileStorage $fileStorage)
    {
        parent::__construct($view, $plugin);
        $this->cloud = $cloud;
        $this->fileStorage = $fileStorage;
    }


    /**
     *
     */
    public function listAction ()
    {
        $successAlert = null;

        if (isset($_GET["remove"]) && 0 !== \preg_match('~^\\d+$~', $_GET["remove"]))
        {
            $successAlert = $this->removeEntry((int) $_GET["remove"]);
        }

        $table = new SignUpsTable($this->plugin, $this->cloud, $this->fileStorage);
        $table->prepare_items();

        $this->render("admin/sign-ups/list.html.twig", [
            "pageTitle" => "Membership › Anmeldungen",
            "table" => $table,
            "successAlert" => $successAlert,
        ]);
    }


    /**
     * Removes the given entry
     *
     * @param int $id
     * @return null|string  the message for a success alert, null otherwise
     */
    private function removeEntry (int $id)
    {
        global $wpdb;

        $row = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}pm_membership_sign_ups WHERE id = {$id}", \ARRAY_A);

        if (null === $row)
        {
            return null;
        }

        $pdfPath = $this->plugin->getFilesystemPath("/storage/contracts/{$row["confirmation_code"]}.pdf");
        if (\is_file($pdfPath))
        {
            @\unlink($pdfPath);
        }

        $wpdb->delete("{$wpdb->prefix}pm_membership_sign_ups", [
            "id" => $row["id"],
        ]);

        return "Die Anmeldung wurde erfolgreich gelöscht.";
    }
}
