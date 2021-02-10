<?php

namespace Ngscz\Elfinder\Forms;

use App\Model\Asset\AssetTable;
use Nette\Forms\Controls\BaseControl;
use Nette\Utils\Html;
use Nette\Utils\Json;

class ElfinderInput extends BaseControl
{
    public const OPTION_FIELDS = 'fields';
    public const OPTION_LOCALES = 'locales';

    /** @var bool $multiple */
    private $multiple = false;

    /** @var array $onlyMimes */
    private $onlyMimes = [];

    /** @var AssetTable */
    private $assetTable;

    public function __construct($caption = null, $assetTable)
    {
        parent::__construct($caption);
        $this->assetTable = $assetTable;
    }

    public function getControl()
    {
        $control = parent::getControl();

        $control->setAttribute('data-value', $this->getRawValue());
        $control->setAttribute('data-multiple', $this->multiple ? '1': '0');
        $control->setAttribute('data-only-mimes', implode(',', $this->onlyMimes));

        $control->setAttribute('data-locales', Json::encode($this->getOption(self::OPTION_LOCALES)));
        $control->setAttribute('data-fields', Json::encode($this->getOption(self::OPTION_FIELDS)));

        $control->setAttribute('data-elfinder', 'true');
        $control->setAttribute('style', 'display: none;');

        return Html::el()->addHtml($control);
    }


    public function setValue($value)
    {
        // convert object to array
        if ($value) {
            $newValue = [];

            if (!is_array($value)) {
                $value = [$value];
            }

            foreach ($value as $file) {
                if (is_object($file)) {
                    $formValue = [
                        'hash' => $file->getHash(),
                        'url' => '/uploads' . $file->getPath(),
                    ];

                    foreach ($this->getOption(self::OPTION_FIELDS) as $fieldName => $fieldSettings) {
                        $formValue = $fieldSettings['onLoad']($file, $formValue);
                    }

                    $newValue[] = $formValue;
                }
            }

            if ($newValue) {
                $value = $newValue;
            }
        }


        if (is_array($value)) {
            $this->value = json_encode($value);
        } else {
            $this->value = $value;
        }
        return $this;
    }

    public function getValue()
    {
        $value = Json::decode($this->getRawValue(), 1);
        $values = [];

        //@todo remove dependency on global container, we should use interface instead of this to return correct values
        if ($value && count($value)) {
            foreach ($value as $item) {
                if (is_string($item)) {
                    $item = Json::decode($item, 1)[0];
                }

                $file = $this->assetTable->getOneByHash($item['hash']);

                foreach ($this->getOption(self::OPTION_FIELDS) as $fieldName => $fieldSettings) {
                    $file = $fieldSettings['onSave']($file, $item);
                }

                $values[] = $file;
            }
        }

        if ($this->multiple) {
            return $values;
        } else {
            if ($values) {
                return reset($values);
            }
            return null;
        }

    }

    public function getRawValue()
    {
        return parent::getValue();
    }

    /**
     * @param bool $multiple
     */
    public function setMultiple($multiple)
    {
        $this->multiple = $multiple;

        return $this;
    }

    public function setOnlyMimes(array $onlyMimes)
    {
        $this->onlyMimes = $onlyMimes;

        return $this;
    }

    public function showOnlyImages($onlyImages)
    {
        if ($onlyImages) {
            $this->onlyMimes = ['image'];
        }
    }
}
