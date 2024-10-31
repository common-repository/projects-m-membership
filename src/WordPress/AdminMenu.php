<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\WordPress;

use ProjectsM\MembershipWordpress\WordPress\Controller\IndexController;
use ProjectsM\MembershipWordpress\WordPress\Controller\SignUpsController;


class AdminMenu
{
    const ADMIN_SLUG = "pm_membership";
    const REQUIRED_CAPABILITY = "manage_options";


    /**
     * @var IndexController
     */
    private $indexController;


    /**
     * @var SignUpsController
     */
    private $signUpsController;


    /**
     * @var Plugin
     */
    private $plugin;


    /**
     *
     * @param IndexController   $indexController
     * @param SignUpsController $signUpsController
     * @param Plugin            $plugin
     */
    public function __construct (IndexController $indexController, SignUpsController $signUpsController, Plugin $plugin)
    {
        $this->indexController = $indexController;
        $this->signUpsController = $signUpsController;
        $this->plugin = $plugin;
    }


    /**
     * Registers the actions
     */
    public function register ()
    {
        add_action("admin_menu", [$this, "buildAdminMenu"]);
    }


    /**
     * @internal
     */
    public function buildAdminMenu ()
    {
        \add_menu_page(
            "Membership",
            "Membership",
            self::REQUIRED_CAPABILITY,
            self::ADMIN_SLUG,
            [$this->indexController, "overviewAction"],
            $this->plugin->generateUrl("build/img/icon.png")
        );

        \add_submenu_page(
            self::ADMIN_SLUG,
            "Übersicht",
            "Übersicht",
            self::REQUIRED_CAPABILITY,
            self::ADMIN_SLUG,
            [$this->indexController, "overviewAction"]
        );

        \add_submenu_page(
            self::ADMIN_SLUG,
            "Anmeldungen",
            "Anmeldungen",
            self::REQUIRED_CAPABILITY,
            self::ADMIN_SLUG . "_sign-ups",
            [$this->signUpsController, "listAction"]
        );
    }
}
