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
     * @Given I have the following :twig template :code
     */
    public function haveTheFollowingTemplate(string $twig, string $code): void
    {
        $file = rtrim($this->config['default_dir'], '/') . '/' . $twig;
        $this->fs()->writeToFile(
            $file,
            $code
        );
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
