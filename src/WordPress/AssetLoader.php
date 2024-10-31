<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\WordPress;


class AssetLoader
{
    /**
     * @var Plugin
     */
    private $plugin;


    /**
     *
     * @param Plugin $plugin
     */
    public function __construct (Plugin $plugin)
    {
        $this->plugin = $plugin;
    }


    /**
     * Registers the actions
     */
    public function register ()
    {
        \add_action("wp_enqueue_scripts", [$this, "loadAssets"]);
    }


    /**
     * Loads all assets of the project
     *
     * @internal
     */
    public function loadAssets ()
    {
        // CSS
        \wp_enqueue_script(
            'pm_membership',
            $this->plugin->generateUrl("build/js/modern/membership.js"),
            ["jquery"],
            $this->plugin->getVersion(),
            true
        );

        // Script
        \wp_enqueue_style(
            'pm_membership',
            $this->plugin->generateUrl("build/css/membership.css"),
            [],
            $this->plugin->getVersion()
        );
    }
}
