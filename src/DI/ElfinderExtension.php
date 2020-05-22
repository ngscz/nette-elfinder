<?php

namespace Ngscz\Elfinder\DI;

use Nette\DI;
use Ngscz\Elfinder;

class ElfinderExtension extends DI\CompilerExtension
{

    private function getDefaults()
    {
        return [
            'uploader' => Elfinder\Uploader\Uploader::class,
            'options' => [
                'bind' => [],
                'plugin' => [],
                'roots' => [
                    [
                        'driver' => 'LocalFileSystem', // driver for accessing file system (REQUIRED)
                        'path' => $_SERVER['DOCUMENT_ROOT'] . '/uploads', // path to files (REQUIRED)
                        'URL' => '/uploads/', // URL to files (REQUIRED)
                        'tmbURL' => (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/uploads/.tmb',
                        'tmbPath' =>  $_SERVER['DOCUMENT_ROOT'] . '/uploads/.tmb',
                        'upload_allow' => ['all'],
                        'disabled' => ['cut', 'copy', 'paste', 'rename', 'duplicate', 'selectFolder'],
                    ],
                ],
            ]
        ];
    }

    public function loadConfiguration()
    {
        $this->validateConfig($this->getDefaults());

        $builder = $this->getContainerBuilder();

        $builder->addDefinition($this->prefix('ngs.uploader'))
            ->setFactory($this->config['uploader']);

        $builder->addDefinition($this->prefix('ngs.elfinder'))
            ->setFactory(Elfinder\Components\Elfinder::class, [$this->config['options'], $builder->getDefinition($this->prefix('ngs.uploader')), $builder->getDefinition('http.request')]);

        /* @todo add interface to load definition to elfinder to return correct values (collection of assets)
         * $builder->addDefinition($this->prefix('ngs.elfinder.input'))
         * ->setFactory(Elfinder\Forms\ElfinderInput::class, [$this->prefix('asset.table')]);
         */

    }
}