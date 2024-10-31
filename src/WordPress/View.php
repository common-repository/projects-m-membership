<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\WordPress;

use Twig\Extension\DebugExtension;


class View
{
    /**
     * @var \Twig_Environment
     */
    private $twig;


    /**
     * @param Plugin $plugin
     */
    public function __construct (Plugin $plugin)
    {
        $fileSystemLoader = new \Twig_Loader_Filesystem($plugin->getFilesystemPath("templates"));
        $this->twig = new \Twig_Environment($fileSystemLoader, [
            "debug" => \WP_DEBUG,
            "cache" => \WP_DEBUG ? false : $plugin->getFilesystemPath(".cache/twig"),
            "strict_variables" => \WP_DEBUG,
        ]);

        if (\WP_DEBUG)
        {
            $this->twig->addExtension(new DebugExtension());
        }
    }


    /**
     * Renders the given template
     *
     * @param string $name
     * @param array  $context
     * @return string
     */
    public function render (string $name, array $context = []) : string
    {
        return $this->twig->render($name, $context);
    }


    /**
     * Renders the given template
     *
     * @param string $name
     * @param array  $context
     * @return string
     */
    public function renderBlock (string $name, string $block, array $context = []) : string
    {
        $template = $this->twig->loadTemplate($name);
        $context = $this->twig->mergeGlobals($context);
        return $template->renderBlock($block, $context);
    }
}
