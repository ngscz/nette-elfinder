<?php

namespace Ngscz\Elfinder\DI;

use Nette\DI;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Ngscz\Elfinder;

class ElfinderExtension extends DI\CompilerExtension
{

    public function getConfigSchema(): Schema
    {
        return Expect::structure([
            'uploader' => Expect::string(Elfinder\Uploader\Uploader::class),
            'options' => Expect::array([
                'bind' => [],
                'plugin' => [],
                'roots' => [
                    'default' => [
                        'driver' => 'LocalFileSystem', // driver for accessing file system (REQUIRED)
                        'path' => ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/uploads', // path to files (REQUIRED)
                        'URL' => '/uploads/', // URL to files (REQUIRED)
                        'tmbURL' => isset($_SERVER['HTTP_HOST']) ? ((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/uploads/.tmb') : '',
                        'tmbPath' => ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/uploads/.tmb',
                        'uploadAllow' => ['image', 'application', 'text/plain'],
                        'uploadDeny' => ['php'],
                        'uploadOrder' => ['allow', 'deny'],
                        'disabled' => ['cut', 'copy', 'paste', 'rename', 'duplicate', 'selectFolder'],
                    ],
                ],
            ]),
        ]);
    }

    public function loadConfiguration()
    {
        $config = (array) $this->getConfig();

        $builder = $this->getContainerBuilder();

        $builder->addDefinition($this->prefix('ngs.uploader'))
            ->setFactory($config['uploader']);

        $builder->addDefinition($this->prefix('ngs.elfinder'))
            ->setFactory(Elfinder\Components\Elfinder::class, [$config['options'], $builder->getDefinition($this->prefix('ngs.uploader')), $builder->getDefinition('http.request')]);

        /* @todo add interface to load definition to elfinder to return correct values (collection of assets)
         * $builder->addDefinition($this->prefix('ngs.elfinder.input'))
         * ->setFactory(Elfinder\Forms\ElfinderInput::class, [$this->prefix('asset.table')]);
         */

    }
}