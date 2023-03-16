<?php

namespace Magewirephp\Magewire\Block\Tmp;

use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;

class Test extends Template implements BlockInterface
{
    protected $_template = 'Magewirephp_Magewire::tmp/test.phtml';
}
