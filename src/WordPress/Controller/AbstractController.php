<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\WordPress\Controller;

use ProjectsM\MembershipWordpress\WordPress\Plugin;
use ProjectsM\MembershipWordpress\WordPress\View;


abstract class AbstractController
{
    /**
     * @var View
     */
    private $view;


    /**
     * @var Plugin
     */
    protected $plugin;


    /**
     * @param View   $view
     * @param Plugin $plugin
     */
    public function __construct (View $view, Plugin $plugin)
    {
        $this->view = $view;
        $this->plugin = $plugin;
    }


    /**
     * Must dispatch to the action
     */
    public function dispatch ()
    {
        $action = $_GET["action"] ?? null;

        if (!\is_string($action))
        {
            $action = "list";
        }

        $action .= "Action";

        if (!\method_exists($this, $action))
        {
            throw new \InvalidArgumentException(sprintf(
                "Can't call unknown action: '%s'",
                $action
            ));
        }

        return $this->{$action}();
    }


    /**
     * Renders the given template
     *
     * @param string $name
     * @param array  $context
     */
    protected function render (string $name, array $context = []) : bool
    {
        echo $this->view->render($name, $context);
        return true;
    }


    /**
     * Returns whether there is a form submission
     *
     * @return bool
     */
    protected function hasFormSubmission () : bool
    {
        return isset($_POST["pm_membership"]) && \is_array($_POST["pm_membership"]);
    }


    /**
     * Returns the form data with the given key
     *
     * @return string|array|mixed
     */
    protected function getFormData (string $key)
    {
        $value = $_POST["pm_membership"][$key] ?? null;

        if (null === $value)
        {
            return null;
        }

        return \is_array($value)
            ? \stripslashes_deep($value)
            : \stripslashes($value);
    }
}
