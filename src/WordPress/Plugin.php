<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\WordPress;


/**
 * Convenience class combining a lot of plugin-related functionality
 */
class Plugin
{
    const OPTION_PREFIX = "membership__";
    const OPTION_API_KEY = self::OPTION_PREFIX . "api_key";
    const OPTION_ADMIN_EMAIL = self::OPTION_PREFIX . "admin_email";
    const OPTION_DB_VERSION = self::OPTION_PREFIX . "db_version";

    /**
     * @var string
     */
    private $version;


    /**
     * @var string
     */
    private $baseUrl;


    /**
     * @var string
     */
    private $rootDir;

    /**
     * @param string $version
     * @param string $baseUrl
     * @param string $rootDir
     */
    public function __construct (string $version, string $baseUrl, string $rootDir)
    {
        $this->version = $version;
        $this->baseUrl = rtrim($baseUrl, "/") . "/";
        $this->rootDir = rtrim($rootDir, "/") . "/";
    }


    /**
     * Returns the version of the plugin
     *
     * @return string
     */
    public function getVersion () : string
    {
        return $this->version;
    }


    /**
     * Generates the URL to a path inside the plugin directory
     *
     * @param string $path
     * @return string
     */
    public function generateUrl (string $path) : string
    {
        return $this->baseUrl . ltrim($path, "/");
    }


    /**
     * @param null|string $slug
     * @param array       $query
     * @return string
     */
    public function generateAdminUrl (string $slug = null, array $query = []) : string
    {
        $slug = null !== $slug
            ? "_{$slug}"
            : "";

        $query = \array_replace($query, [
            "page" => AdminMenu::ADMIN_SLUG . $slug,
        ]);

        return \admin_url("admin.php?" . \http_build_query($query));
    }


    /**
     * Generates the full file path to a path inside the plugin directory
     *
     * @param string $path
     * @return string
     */
    public function getFilesystemPath (string $path) : string
    {
        return $this->rootDir . ltrim($path, "/");
    }


    /**
     * Stores the API key
     *
     * @param string $key
     */
    public function storeApiKey (string $key)
    {
        \update_option(self::OPTION_API_KEY, $key, true);
    }


    /**
     * Stores the admin email
     *
     * @param string $value
     */
    public function storeAdminEmail (string $value)
    {
        \update_option(self::OPTION_ADMIN_EMAIL, $value, true);
    }


    /**
     * @param int    $contract
     * @param string $value
     */
    public function storeNotificationEmail (int $contract, string $value)
    {
        \update_option(self::OPTION_ADMIN_EMAIL . "_{$contract}", $value, true);
    }


    /**
     * @param int    $contract
     * @param string $value
     * @return string|null
     */
    public function getNotificationEmail (int $contract)
    {
        $value = \get_option(self::OPTION_ADMIN_EMAIL . "_{$contract}", null);

        return null !== $value
            ? (string) $value
            : null;
    }


    /**
     * Returns the API key
     *
     * @return null|string
     */
    public function getApiKey ()
    {
        $value = \get_option(self::OPTION_API_KEY, null);

        return null !== $value
            ? (string) $value
            : null;
    }


    /**
     * Returns the admin email
     *
     * @return null|string
     */
    public function getAdminEmail ()
    {
        $value = \get_option(self::OPTION_ADMIN_EMAIL, null);

        return null !== $value
            ? (string) $value
            : null;
    }


    /**
     * Callback for installing the plugin
     */
    public function installPlugin ()
    {
        global $wpdb;

        $charsetCollate = $wpdb->get_charset_collate();
        $collate = $wpdb->collate;

        $sql = "
        CREATE TABLE {$wpdb->prefix}pm_membership_sign_ups (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `time_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `data` json NOT NULL,
            `confirmation_code` varchar(60) COLLATE {$collate} NOT NULL DEFAULT '',
            `contract_id` int(11) NOT NULL,
            `magicline_data` json NULL DEFAULT NULL,
            `time_confirmed` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_code` (`confirmation_code`)
        ) {$charsetCollate};
        ";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        \dbDelta($sql);
        \update_option(self::OPTION_DB_VERSION, $this->version);
    }


    /**
     * Callback for updating the plugin
     */
    public function updatePlugin ()
    {
        $dbVersion = \get_option(self::OPTION_DB_VERSION);

        if ($dbVersion !== $this->version)
        {
            $this->installPlugin();
        }
    }


    /**
     * Callback on uninstalling the plugin
     */
    public function uninstallPlugin ()
    {
        global $wpdb;

        $allOptions = [
            self::OPTION_DB_VERSION,
            self::OPTION_ADMIN_EMAIL,
            self::OPTION_API_KEY
        ];

        foreach ($allOptions as $option)
        {
            \delete_option($option);
        }

        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}pm_membership_sign_ups");
    }


    /**
     * @param null|string $subDir
     * @return string
     */
    public function getStorageDir (string $subDir = null) : string
    {
        $subDir = null !== $subDir
            ? "/" . trim($subDir, "/")
            : "";

        return \wp_get_upload_dir()["basedir"] . "/pm_memberships" . $subDir;
    }
}
