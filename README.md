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

9)

Custom Elfinder input with custom attributes

```
<?php declare(strict_types=1);

namespace App\CmsModule\Forms\Controls;

use App\Model\Asset\Asset;
use Nette\Utils\Json;
use Ngscz\Elfinder\Forms\ElfinderInput as BaseElfinderInput;

class ElfinderInput extends BaseElfinderInput
{
    public function __construct($caption, $assetTable)
    {
        parent::__construct($caption, $assetTable);

        $this->setOption(self::OPTION_LOCALES, [
            [
                'locale' => 'cs',
                'label' => 'Česky',
            ],
            [
                'locale' => 'en',
                'label' => 'Anglicky',
            ],
        ]);

        $this->setOption(self::OPTION_FIELDS, [
            [
                'name' => 'title',
                'type' => 'text',
                'label' => 'Název',
            ],
            [
                'name' => 'description',
                'type' => 'textarea',
                'label' => 'Popis',
            ],
        ]);
    }

    public function setValue($value)
    {
        parent::setValue($value);

        if ($this->files !== []) {
            $this->value = Json::encode($this->onLoad());
        }

        return $this;
    }

    private function onLoad(): array
    {
        $values = [];
        foreach ($this->files as $asset) {
            $formValue = [
                'hash' => $asset->getHash(),
                'url' => '/uploads' . $asset->getPath(),
            ];

            foreach ($this->getOption(self::OPTION_LOCALES) as $locale) {
                $translation = $asset->translate($locale['locale'], false);
                $formValue[$locale['locale']]['title'] = $translation->getTitle();
                $formValue[$locale['locale']]['description'] = $translation->getDescription();
            }

            $values[] = $formValue;
        }

        return $values;
    }

    public function onSave(): void
    {
        $values = $this->getValues();

        foreach ($values as $key => $formValue) {
            /** @var Asset $asset */
            $asset = $formValue['file'];

            foreach ($this->getOption(self::OPTION_LOCALES) as $locale) {
                $translation = $asset->translate($locale['locale'], false);
                $translation->setTitle($formValue[$locale['locale']]['title'] ?? null);
                $translation->setDescription($formValue[$locale['locale']]['description'] ?? null);
            }

            $asset->mergeNewTranslations();

            $values[$key]['file'] = $asset;
        }

        $this->values = $values;
    }
}

```

and then bind `onSuccess` event

```
        $this->onSuccess[] = function (Form $form) use ($name): void {
            $form[$name]->onSave();
        };
```

@todo

Add example how to save data to DB (Uploader)

