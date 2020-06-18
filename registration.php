<?php

use Magento\Framework\Component\ComponentRegistrar;

$registrar = new ComponentRegistrar();

if ($registrar->getPath(ComponentRegistrar::MODULE, 'Moonlay_GMOCreditCard') === null) {
    ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Moonlay_GMOCreditCard', __DIR__);
}
