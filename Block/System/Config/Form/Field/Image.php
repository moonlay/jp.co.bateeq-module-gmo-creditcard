<?php

namespace Moonlay\GMOCreditCard\Block\System\Config\Form\Field;

class Image extends \Magento\Config\Block\System\Config\Form\Field 
{
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = '';

        if (!(string)$element->getValue()) {
            $defaultImage = $this->getViewFileUrl('Moonlay_CreditCard::images/creditcard_logo.png');
            
            $html .= '<img src="' . $defaultImage . '" alt="Credit Card logo" height="50" width="85" class="small-image-preview v-middle" />';
            $html .= '<p class="note"><span>Upload a new image if you wish to replace this logo.</span></p>';
        }

        //the standard image preview is very small- bump the height up a bit and remove the width
        $html .= str_replace('height="22" width="22"', 'height="50"', parent::_getElementHtml($element));

        return $html;
    }
}
