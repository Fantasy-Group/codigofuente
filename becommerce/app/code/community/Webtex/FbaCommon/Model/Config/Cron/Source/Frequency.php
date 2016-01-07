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

class Webtex_FbaCommon_Model_Config_Cron_Source_Frequency
{
    public function toOptionArray()
    {
        return array(
            array('value' => 0, 'label' => 'None'),
            array('value' => 1, 'label' => 'Monthly'),
            array('value' => 2, 'label' => 'Daily'),
            array('value' => 3, 'label' => 'Hourly'),
            array('value' => 4, 'label' => 'Every 30 min'),
        );
    }

    public function toArray()
    {
        return array('None', 'Monthly', 'Daily', 'Hourly', 'Every 30 min');
    }

    public function getCronStringByValue($value)
    {
        switch ($value) {
            case 1:
                return '0 0 0 * *';
                break;
            case 2:
                return '0 0 * * *';
                break;
            case 3:
                return '0 * * * *';
                break;
            case 4:
                return '*/30 * * * *';
                break;
            default:
                return '';
        }
    }
}
