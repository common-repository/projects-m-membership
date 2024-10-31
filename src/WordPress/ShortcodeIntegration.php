<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\WordPress;


/**
 * Provides the integration of the shortcode into the system
 */
class ShortcodeIntegration
{
    /**
     * Registers the short code
     */
    public function register ()
    {
        \add_shortcode("membership", [$this, "embedShortcode"]);
    }


    /**
     * Embeds the given shortcode
     *
     * @param array|string $attributes
     * @return string
     */
    public function embedShortcode ($attrs) : string
    {
        // only display configurator in potentially full-width containers
        if (!\is_single() && !\is_page())
        {
            return "";
        }

        if (!\is_array($attrs))
        {
            $attrs = [];
        }

        if (!isset($attrs["id"]) || 0 === \preg_match('~^\d+$~', $attrs["id"]))
        {
            return "";
        }

        $data = [
            "url" => admin_url("admin-ajax.php?action=pm_membership&id=" . $attrs["id"]),
        ];
        return '<script class="_membership-data-container" type="application/json">' . \json_encode($data, \JSON_UNESCAPED_SLASHES) . '</script>';
    }
}
