<?php

namespace Ngscz\Elfinder\Presenters;

use Ngscz\Elfinder;

trait ElfinderPresenter
{
    /** @var Elfinder\Components\Elfinder @inject */
    public $elFinder;

    public function actionApi()
    {
        $this->elFinder->renderApi();
    }

    public function createComponentElfinderManager()
    {
        return $this->elFinder;
    }
}