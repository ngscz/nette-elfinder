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

2) Update your composer dependencies

```
composer update
```

3) Add script options to composer.json

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

This script will copy required assets to public (www) directory. If it is not run automatically, you should run:

```
composer run-script ngs-elfinder-move-assets
```

4) Run command 


```
	composer dump-autoload --optimize --no-dev --classmap-authoritative
```

You should add this command to deployment process


5)

Create Elfinder presenter and use trait ElfinderPresenter

```
<?php

namespace App\Presenters;

use Nette;

class ElfinderPresenter extends Nette\Application\UI\Presenter
{
    use \Ngscz\Elfinder\Presenters\ElfinderPresenter;

}
```

Add Elfinder presenter to your Router

```
$router[] = new Route('elfinder/<action>', 'Elfinder:default');

```

create template templates/Elfinder/default.latte and include core default.latte file


```
etc:

{include '../../../../../src/Ngscz/Elfinder/src/Presenters/templates/Elfinder/default.latte'}
```

also you have to add JS hook to bind filemanager to your CMS @layout.latte

```
<script src="assets/vendor/ngscz-elfinder/js/ElfinderInputHook.js"></script>
```

6) 

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

7) 

Add example how to save data to DB (Uploader)

## TODO

- secure ElfinderPresenter
