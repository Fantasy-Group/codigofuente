<?php
class Mage_Adminhtml_Model_System_Config_Source_Effects
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
		
            array('value' => 'swing', 'label'=>Mage::helper('adminhtml')->__('swing')),
            array('value' => 'easeOutQuad', 'label'=>Mage::helper('adminhtml')->__('easeOutQuad')),
            array('value' => 'easeOutCirc', 'label'=>Mage::helper('adminhtml')->__('easeOutCirc')),
            array('value' => 'easeOutElastic', 'label'=>Mage::helper('adminhtml')->__('easeOutElastic')),
            array('value' => 'easeOutExpo', 'label'=>Mage::helper('adminhtml')->__('easeOutExpo')),
        );
    }

}
