<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\Api\Data;


class ClientPersonalization extends AbstractEntity
{
    /**
     * @var string|null
     */
    public $configuratorRegards;

    /**
     * @var string|null
     */
    public $mailFooter;

    /**
     * @var string|null
     */
    public $websiteCss;


    /**
     * @var boolean
     */
    public $websiteNavigationHorizontal;

    /**
     * @var string|null
     */
    public $emailColorText;

    /**
     * @var string|null
     */
    public $emailColorBodyBg;

    /**
     * @var string|null
     */
    public $emailColorBoxBg;

    /**
     * @var string|null
     */
    public $emailColorFooterText;

    /**
     * @var string|null
     */
    public $emailColorHeaderBg;

    /**
     * @var string|null
     */
    public $emailHeaderImage;

    /**
     * @var string|null
     */
    public $emailTextAlignment;

    /**
     * @var string|null
     */
    public $emailColorButtonText;

    /**
     * @var string|null
     */
    public $emailColorButtonBg;

    /**
     * @var string|null
     */
    public $emailColorButtonBgHover;


    /**
     * @var string|null
     */
    public $successHeaderImage;
}
