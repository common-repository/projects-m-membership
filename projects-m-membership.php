<?php
/**
 * Plugin Name: Projects M Membership
 * Description: Integrates the Projects M Membership system into your WordPress installation.
 * Version:     1.4.4
 * Author:      Projects M
 * Author URI:  https://projects-m.de/
 * License:     proprietary
 */

use ProjectsM\MembershipWordpress\Api\CloudController;
use ProjectsM\MembershipWordpress\Api\FileStorage;
use ProjectsM\MembershipWordpress\Api\MembershipCloud;
use ProjectsM\MembershipWordpress\Api\MembershipNotifications;
use ProjectsM\MembershipWordpress\WordPress\Controller\AjaxController;
use ProjectsM\MembershipWordpress\WordPress\Controller\IndexController;
use ProjectsM\MembershipWordpress\WordPress\Controller\SignUpsController;
use ProjectsM\MembershipWordpress\WordPress\AdminMenu;
use ProjectsM\MembershipWordpress\WordPress\AssetLoader;
use ProjectsM\MembershipWordpress\WordPress\Plugin;
use ProjectsM\MembershipWordpress\WordPress\ShortcodeIntegration;
use ProjectsM\MembershipWordpress\WordPress\View;
use ProjectsM\MembershipWordpress\WordPress\WordPressMailer;


\defined( 'ABSPATH' ) or die( 'No direct access allowed.' );

require_once __DIR__ . "/vendor/autoload.php";

// create main services
$plugin = new Plugin(
    "1.4.4",
    \plugin_dir_url(__FILE__),
    __DIR__
);
$cloud = new MembershipCloud($plugin->getApiKey());
$shortcode = new ShortcodeIntegration();

// create services with dependencies
$assetLoader = new AssetLoader($plugin);
$view = new View($plugin);
$notifications = new MembershipNotifications($view, new WordPressMailer(), $plugin);
$wpUploadDir = \wp_get_upload_dir();
$fileStorage = new FileStorage($wpUploadDir["basedir"], $wpUploadDir["baseurl"]);

// start up
$assetLoader->register();
$shortcode->register();

// generate core Membership infrastructure
$cloudController = new CloudController($cloud, $notifications, $view, $plugin, $fileStorage);
$ajaxController = new AjaxController($cloudController);

// create admin menu
$adminMenu = new AdminMenu(
    new IndexController($view, $plugin, $cloudController, $cloud),
    new SignUpsController($view, $plugin, $cloud, $fileStorage),
    $plugin
);
$adminMenu->register();

// register event listeners and dispatch to WordPress action system
$cloudController->addEventListeners(CloudController::EVENT_REGISTERED,
    function ($payload)
    {
        \do_action("pm_membership_sign_up_registered", $payload);
    }
);
$cloudController->addEventListeners(CloudController::EVENT_CONFIRMED,
    function ($payload) use ($cloudController)
    {
		// automatically transfer if enabled
		$cloudController->handleMagiclineTransfer($payload["signupId"]);

		// call hook
        \do_action("pm_membership_sign_up_confirmed", $payload);
    }
);

// register AJAX controller
\add_action('wp_ajax_pm_membership', [$ajaxController, "handle"]);
\add_action('wp_ajax_nopriv_pm_membership', [$ajaxController, "handle"]);
\add_action('wp_ajax_pm_magicline_transfer', [$ajaxController, "handleMagiclineTransfer"]);

// installation hooks
\register_activation_hook(__FILE__, [$plugin, "installPlugin"]);
\register_deactivation_hook(__FILE__, [$plugin, "uninstallPlugin"]);
\add_action('plugins_loaded', [$plugin, "updatePlugin"]);
