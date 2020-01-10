<?php

use Magento\Framework\Component\ComponentRegistrar;

$registrar = new ComponentRegistrar();

if ($registrar->getPath(ComponentRegistrar::MODULE, 'Moonlay_GMOMultiPayment') === null) {
    ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Moonlay_GMOMultiPayment', __DIR__);
}
