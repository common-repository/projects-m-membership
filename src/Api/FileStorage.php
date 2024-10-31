<?php declare(strict_types=1);

namespace ProjectsM\MembershipWordpress\Api;


class FileStorage
{
    /**
     * @var string
     */
    private $baseDir;


    /**
     * @var string
     */
    private $baseUrl;


    /**
     * @param string $baseDir
     * @param string $baseUrl
     */
    public function __construct (string $baseDir, string $baseUrl)
    {
        $this->baseDir = "{$baseDir}/pm_memberships";
        $this->baseUrl = "{$baseUrl}/pm_memberships";
    }


    /**
     * @param string $confirmationCode
     * @return string
     */
    public function getPdfStoragePath (string $confirmationCode) : string
    {
        $dir = \substr($confirmationCode, 0, 3);
        return "{$this->baseDir}/{$dir}";
    }


    /**
     * @param string $confirmationCode
     * @return string
     */
    public function getPdfStorageUrl (string $confirmationCode) : string
    {
        $dir = \substr($confirmationCode, 0, 3);
        return "{$this->baseUrl}/{$dir}";
    }
}
