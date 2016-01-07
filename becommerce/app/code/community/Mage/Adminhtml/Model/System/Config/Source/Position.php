<?php
class Mage_Adminhtml_Model_System_Config_Source_Position
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
		
            array('value' => 1, 'label'=>Mage::helper('adminhtml')->__('Right')),
            array('value' => 2, 'label'=>Mage::helper('adminhtml')->__('Left')),
        );
    }

}
