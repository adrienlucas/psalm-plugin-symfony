<?php

declare(strict_types=1);

namespace Psalm\SymfonyPsalmPlugin\Test;


use Codeception\Exception\ModuleRequireException;
use Codeception\Module as BaseModule;
use Codeception\Module\Filesystem;

class CodeceptionModule extends BaseModule
{
    /** @var array<string,string> */
    protected $config = [
        'default_dir' => 'tests/_run/',
    ];

    /** @var FileSystem */
    private $fs;

    /**
     * @Given I have the following :templateName template :code
     */
    public function haveTheFollowingTemplate(string $templateName, string $code): void
    {
        $rootDirectory = rtrim($this->config['default_dir'], '/');
        $templateRootDirectory = $rootDirectory . '/templates';
        $cacheDirectory = $rootDirectory.'/cache/twig';
        if(!file_exists($templateRootDirectory)) {
            mkdir($templateRootDirectory);
        }
        if(!file_exists($cacheDirectory)) {
            mkdir($cacheDirectory, 0777, true);
        }

        $this->fs()->writeToFile(
            $templateRootDirectory . '/' . $templateName,
            $code
        );

//         Generate template compiled classes so psalm can analyse them
        $twigEnvironment = TwigBridge::getEnvironment($rootDirectory, $cacheDirectory);
        $twigEnvironment->load($templateName);
    }

    private function fs(): Filesystem
    {
        if (null === $this->fs) {
            $fs = $this->getModule('Filesystem');
            if (!$fs instanceof Filesystem) {
                throw new ModuleRequireException($this, 'Needs Filesystem module');
            }
            $this->fs = $fs;
        }
        return $this->fs;
    }
}
