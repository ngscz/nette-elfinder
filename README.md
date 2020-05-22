# Elfinder

## Installation

1) Add this options to composer.json

```
	"require": {
		"ngscz/nette-elfinder": "dev-master"
	},
	"repositories": [
		{
			"type": "git",
			"url": "https://github.com/ngscz/nette-elfinder.git"
		}
	],
```

2) Add script options to composer.json

```
	"scripts": {
		"ngs-elfinder-move-assets": [
			"cp -r vendor/ngscz/nette-elfinder/assets www",
			"mkdir -p www/assets/vendor/elfinder",
			"cp -r vendor/studio-42/elfinder/css www/assets/vendor/elfinder",
			"cp -r vendor/studio-42/elfinder/js www/assets/vendor/elfinder",
			"cp -r vendor/studio-42/elfinder/img www/assets/vendor/elfinder"
		],
		"post-install-cmd": [
			"@ngs-elfinder-move-assets"
		]
	}
```

3) Update your composer dependencies

```
composer update
```

4)

Script above will copy required assets to public (www) directory. If it is not run automatically, you should run:

```
composer run-script ngs-elfinder-move-assets
```

5)

Add extension configuration to config.neon
```
extensions:
	ngs.elfinder: Ngscz\Elfinder\DI\ElfinderExtension
```    

6)

Create Elfinder presenter and use trait ElfinderPresenter

```
<?php

namespace App\Presenters;

use Nette;

class ElfinderPresenter extends Nette\Application\UI\Presenter
{
    use \Ngscz\Elfinder\Presenters\ElfinderPresenter;

    public function renderDefault()
    {
        $template = $this->getTemplate();

        $template->includePath = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR .
            'vendor' . DIRECTORY_SEPARATOR .
            'ngscz' . DIRECTORY_SEPARATOR .
            'nette-elfinder' . DIRECTORY_SEPARATOR .
            'src' . DIRECTORY_SEPARATOR .
            'Presenters' . DIRECTORY_SEPARATOR .
            'templates' . DIRECTORY_SEPARATOR .
            'Elfinder' . DIRECTORY_SEPARATOR .
            'default.latte';
    }    

}
```

Add Elfinder presenter to your Router

```
$router[] = new Route('elfinder/<action>', 'Elfinder:default');

```

create template templates/Elfinder/default.latte and include core default.latte file
(use $includePath variable created in renderDefault method)


```
etc:

{include $includePath}
```

also you have to add JS hook to bind filemanager to your CMS @layout.latte

```
<script src="assets/vendor/ngscz-elfinder/js/ElfinderInputHook.js"></script>
```

7) 

Example, how to add elfinder to Form

```
<?php

namespace App\Presenters;

use Nette\Application\UI;
use Ngscz\Elfinder\Forms\ElfinderInput;

class HomepagePresenter extends FrontendPresenter
{

    protected function createComponentForm()
    {
        $form = new UI\Form;
        $form->addComponent(new ElfinderInput, 'file');

        $form->addSubmit('submit');

        // how to set default values
        $qb = $this->assetTable->prepareQueryBuilder();
        $files = [];
        foreach ($qb->getQuery()->getResult() as $file) {
            $files[] = [
                'hash' => $file->getHash(),
                'url' => '/uploads' . $file->getPath(),
            ];
        }

        $form->setDefaults([
            'file' => $files,
        ]);

        $form->onSuccess[] = function($form, $values) {
            dumpe($values);
            //will return array of hashe
        };

        return $form;
    }

}
```

8) 

Example, how to add filters based on file mimeType
```
    $control = new ElfinderInput($label, $assetTable);
    $control->onlyMimes(['image', 'audio']); //to show only images and audio files
    
    list of possible filters:
    ['image', 'audio', 'application', 'text', 'video']
    
    see main types: https://cs.wikipedia.org/wiki/Typ_internetov%C3%A9ho_m%C3%A9dia#N%C4%9Bkter%C3%A9_%C4%8Dasto_pou%C5%BE%C3%ADvan%C3%A9_typy_m%C3%A9di%C3%AD
```

@todo

Add example how to save data to DB (Uploader)

