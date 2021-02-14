<?php

namespace Ngscz\Elfinder\Forms;

use App\Model\Asset\Asset;
use App\Model\Asset\AssetTable;
use Nette\Forms\Controls\BaseControl;
use Nette\Utils\Json;
use Nette\Utils\Html;
use Nette\Utils\JsonException;

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

    /** @var array */
    protected $values = [];

    /** @var Asset[]  */
    protected $files = [];

    public function __construct($caption, $assetTable)
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
            if (!is_array($value)) {
                $value = [$value];
            }

            $newValue = [];
            foreach ($value as $file) {
                if (is_object($file)) {
                    $formValue = [
                        'hash' => $file->getHash(),
                        'url' => '/uploads' . $file->getPath(),
                    ];
                    $newValue[] = $formValue;

                    $this->files[] = $file;
                }
            }

            if ($newValue !== []) {
                $value = $newValue;
            }
        }

        if (is_array($value)) {
            $this->value = Json::encode($value);
        } else {
            $this->value = $value;
        }

        return $this;
    }

    public function getValue()
    {
        $values = $this->getValues();

        $values = array_column($values, 'file');

        if ($this->multiple) {
            return $values;
        } else {
            if ($values) {
                return reset($values);
            }
        }

        return null;
    }

    public function getValues(): array
    {
        if ($this->values === []) {

            if ($this->getRawValue() === null) {
                return [];
            }

            $decodedValues = [];
            $rawValues = Json::decode($this->getRawValue(), 1);
            if ($rawValues && count($rawValues)) {
                foreach ($rawValues as $item) {
                    if (is_string($item)) {
                        $item = Json::decode($item, 1) ?? null;
                    }

                    if ($item) {
                        $decodedValues[] = $item;
                    }
                }
            }
            $values = [];

            if (isset($decodedValues[0][0])) {
                $decodedValues = $decodedValues[0];
            }

            foreach ($decodedValues as $item) {
                //@todo remove dependency on global container, we should use interface instead of this to return correct values
                $file = $this->assetTable->getOneByHash($item['hash']);

                $values[] = array_merge($item, ['file' => $file]);
            }

            $this->values = $values;
        }

        return $this->values;
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
