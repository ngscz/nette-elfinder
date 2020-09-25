<?php

namespace Ngscz\Elfinder\Components;

use Nette\Application\UI\Control;
use Nette\Http\Request;
use Nette\Utils;
use Ngscz\Elfinder\Uploader;
use elFinder as studio42ElFinder;
use elFinderConnector as studio42ElFinderConnector;

class Elfinder extends Control
{
    /** @var array */
    private $options;

    /** @var Uploader\IUploader */
    private $uploader;

    /** @var Request $request */
    private $request;

    public function __construct(array $options, Uploader\IUploader $uploader, Request $request)
    {
        parent::__construct();
        $this->options = $options;
        $this->uploader = $uploader;
        $this->request = $request;
    }

    public function renderApi()
    {
        $this->prepareOptions();

        $connector = new studio42ElFinderConnector(new studio42ElFinder($this->options));
        $connector->run();
    }


    public function renderManager()
    {
        $template = $this->getTemplate();
        $template->onlyMimes = $this->request->getQuery('onlyMimes', null);
        $template->isMultiple = (bool) $this->request->getQuery('isMultiple', false);
        $template->setFile(__DIR__ . '/template/elfinder.latte');
        $template->render();
    }

    private function prepareOptions()
    {

        foreach ($this->options['roots'] as $rootKey => $root) {
            // Disable show .dot files
            $this->options['roots'][$rootKey]['accessControl'] = [$this, 'access'];
        }

        // Add callbacks
        $this->options['bind'] += [
            'rename' => [$this->uploader, 'onRename'],
            'upload' => [$this->uploader, 'onUpload'],
            'rm' => [$this->uploader, 'onRemove'],
            'dim' => [$this->uploader, 'onFileDimension'],
            'paste' => [$this->uploader, 'onFilePaste'],
            'open' => [$this->uploader, 'onFolderOpen'],
        ];

        // Add Sanitizer and Nromalizer plugin configuration to remove special chars from file name
        $this->options['bind'] += [
            'upload.pre mkdir.pre mkfile.pre rename.pre archive.pre ls.pre' => array(
                'Plugin.Normalizer.cmdPreprocess',
                'Plugin.Sanitizer.cmdPreprocess',
            ),
            'upload.presave' => array(
                'Plugin.Normalizer.onUpLoadPreSave',
                'Plugin.Sanitizer.onUpLoadPreSave',
            ),
        ];

        $this->options['plugin'] += [
            'Normalizer' => [
                'enable' => true,
                'nfc' => true,
                'nfkc' => true,
                'umlauts' => false,
                'lowercase' => true,
                'convmap' => [],
            ],

            'Sanitizer' => [
                'enable' => true,
                'callBack' => function ($name, $options) {
                    $ext = '';
                    $pos = strrpos($name, '.');
                    if ($pos !== false) {
                        $ext = substr($name, $pos);
                        $name = substr($name, 0, $pos);
                    }
                    $name = Utils\Strings::webalize($name);

                    return $name . $ext;
                }
            ],
        ];

    }

    /**
     * Simple function to demonstrate how to control file access using "accessControl" callback.
     * This method will disable accessing files/folders starting from  '.' (dot)
     *
     * @param  string $attr attribute name (read|write|locked|hidden)
     * @param  string $path file path relative to volume root directory started with directory separator
     * @return bool|null
     */
    public function access($attr, $path, $data, $volume)
    {
        return strpos(basename($path), '.') === 0       // if file/folder begins with '.' (dot)
            ? !($attr == 'read' || $attr == 'write')    // set read+write to false, other (locked+hidden) set to true
            : null;                                    // else elFinder decide it itself
    }


}
