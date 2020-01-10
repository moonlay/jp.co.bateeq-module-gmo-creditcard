<?php

/**
 * Country config field renderer
 */

namespace Moonlay\GMOMultiPayment\Model\Config\Source;

use Magento\Directory\Model\Config\Source\Country;

class RestrictedCountry extends Country
{
    /**
     * @param \Magento\Directory\Model\ResourceModel\Country\Collection $countryCollection
     */
    public function __construct(\Magento\Directory\Model\ResourceModel\Country\Collection $countryCollection)
    {
        $countryCollection->addCountryIdFilter(array('JP'));
        
        parent::__construct($countryCollection);
    }
}
