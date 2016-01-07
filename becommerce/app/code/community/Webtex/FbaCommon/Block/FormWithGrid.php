<?php
/**
 * Webtex
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.webtexsoftware.com/LICENSE.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@webtexsoftware.com and we will send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extension to newer
 * versions in the future. If you wish to customize the extension for your
 * needs please refer to http://www.webtexsoftware.com for more information,
 * or contact us through this email: info@webtexsoftware.com.
 *
 * @category   Webtex
 * @package    Webtex_FbaCommon
 * @copyright  Copyright (c) 2015 Webtex Solutions, LLC (http://www.webtexsoftware.com/)
 * @license    http://www.webtexsoftware.com/LICENSE.txt End-User License Agreement
 */
class Webtex_FbaCommon_Block_FormWithGrid extends Mage_Adminhtml_Block_Widget_Form
{

    /**
     * @param $type
     * @param $attributes
     * @return Varien_Data_Form_Element_Abstract
     */
    protected function getElement($type, $attributes)
    {
        $className = 'Varien_Data_Form_Element_' . ucfirst(strtolower($type));

        $element = new $className($attributes);
        $element->setForm($this->form);

        return $element;
    }

    /**
     * @param $value
     * @return Varien_Data_Form_Element_Abstract
     */
    protected function getTextElement($value)
    {
        return $this->getElement(
            'text',
            array(
                'value' => $value
            )
        );
    }


    /**
     * @return Varien_Data_Form_Element_Renderer_Interface
     */
    protected function createGridBlock()
    {
        return $this->getLayout()->createBlock('wfcom/formWithGrid_gridElement');
    }

    /**
     * @return Webtex_FbaCommon_Helper_Data
     */
    public function getCommonHelper()
    {
        return Mage::helper('wfcom');
    }

    /**
     * @param Varien_Data_Form_Element_Fieldset $fieldSet
     * @param $elementId
     * @param $config
     *
     * @return Varien_Data_Form_Element_Abstract
     */
    protected function addGridField($fieldSet, $elementId, $config)
    {
        $field = $fieldSet->addField(
            $elementId,
            'text',
            $config
        );
        $field->setRenderer($this->createGridBlock());
        return $field;
    }
}