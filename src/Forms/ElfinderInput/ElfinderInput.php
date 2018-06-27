<?php

namespace Ngscz\Elfinder\Forms;

use App\Model\Entity\Asset;
use App\Model\Table\AssetTable;
use Nette\Forms\Controls\BaseControl;
use Nette\Utils\Html;

class ElfinderInput extends BaseControl
{
    /** @var bool $multiple */
    private $multiple = false;

    /** @var bool $onlyImages */
    private $onlyImages = false;

    /** @var AssetTable */
    private $assetTable;

    public function __construct($caption = null, AssetTable $assetTable)
    {
        parent::__construct($caption);
        $this->assetTable = $assetTable;
    }

    public function getControl()
    {
        $control = parent::getControl();

        $control->setAttribute('data-value', $this->getRawValue());
        $control->setAttribute('data-multiple', $this->multiple ? '1': '0');
        $control->setAttribute('data-show-only-images', $this->onlyImages ? '1': '0');

        $control->setAttribute('data-elfinder', 'true');
        $control->setAttribute('style', 'display: none;');

        return Html::el()->addHtml($control);
    }


    public function setValue($value)
    {
        // convert object to array
        if ($value) {
            $newValue = array();
            if (is_array($value)) {
                foreach ($value as $file) {
                    if (is_object($file)) {
                        $newValue[] = array(
                            'hash' => $file->getHash(),
                            'url' => '/uploads' . $file->getPath(),
                        );
                    }
                }
            } else if (is_object($value)) {
                $newValue[] = array(
                    'hash' => $value->getHash(),
                    'url' => '/uploads' . $value->getPath(),
                );
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
        $value = json_decode($this->getRawValue(), true);
        $values = [];

        //@todo remove dependency on global container, we should use interface instead of this to return correct values
        if ($value && count($value)) {
            foreach ($value as $item) {
                $values[] = $this->assetTable->getOneByHash($item['hash']);
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
    }

    /**
     * @param bool $onlyImages
     */
    public function showOnlyImages($onlyImages)
    {
        $this->onlyImages = $onlyImages;
    }
}
