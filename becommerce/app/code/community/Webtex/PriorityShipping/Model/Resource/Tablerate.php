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
 * @package    Webtex_PriorityShipping
 * @copyright  Copyright (c) 2015 Webtex Solutions, LLC (http://www.webtexsoftware.com/)
 * @license    http://www.webtexsoftware.com/LICENSE.txt End-User License Agreement
 */
class Webtex_PriorityShipping_Model_Resource_Tablerate extends
    Mage_Shipping_Model_Resource_Carrier_Tablerate
{
    /**
     * Define main table and id field name
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('pShipping/tablerate', 'pk');
    }

    /**
     * Upload table rate file and import data from it
     *
     * @param Varien_Object $object
     * @throws Mage_Core_Exception
     * @return Mage_Shipping_Model_Resource_Carrier_Tablerate
     */
    public function uploadAndImport(Varien_Object $object)
    {
        if (empty($_FILES['groups']['tmp_name']['webtexPriority']['fields']['import']['value'])) {
            return $this;
        }

        $csvFile = $_FILES['groups']['tmp_name']['webtexPriority']['fields']['import']['value'];
        $website = Mage::app()->getWebsite($object->getScopeId());

        $this->_importWebsiteId = (int)$website->getId();
        $this->_importUniqueHash = array();
        $this->_importErrors = array();
        $this->_importedRows = 0;

        $io = new Varien_Io_File();
        $info = pathinfo($csvFile);
        $io->open(array('path' => $info['dirname']));
        $io->streamOpen($info['basename'], 'r');

        // check and skip headers
        $headers = $io->streamReadCsv();
        if ($headers === false || count($headers) < 5) {
            $io->streamClose();
            Mage::throwException(Mage::helper('shipping')->__('Invalid Table Rates File Format'));
        }

        if ($object->getData('groups/webtexPriority/fields/condition_name/inherit') == '1') {
            $conditionName = (string)Mage::getConfig()->getNode('default/carriers/webtexPriority/condition_name');
        } else {
            $conditionName = $object->getData('groups/webtexPriority/fields/condition_name/value');
        }
        $this->_importConditionName = $conditionName;

        $adapter = $this->_getWriteAdapter();
        $adapter->beginTransaction();

        try {
            $rowNumber = 1;
            $importData = array();

            $this->_loadDirectoryCountries();
            $this->_loadDirectoryRegions();

            // delete old data by website and condition name
            $condition = array(
                'website_id = ?' => $this->_importWebsiteId,
                'condition_name = ?' => $this->_importConditionName
            );
            $adapter->delete($this->getMainTable(), $condition);

            while (false !== ($csvLine = $io->streamReadCsv())) {
                $rowNumber++;

                if (empty($csvLine)) {
                    continue;
                }

                $row = $this->_getImportRow($csvLine, $rowNumber);
                if ($row !== false) {
                    $importData[] = $row;
                }

                if (count($importData) == 5000) {
                    $this->_saveImportData($importData);
                    $importData = array();
                }
            }
            $this->_saveImportData($importData);
            $io->streamClose();
        } catch (Mage_Core_Exception $e) {
            $adapter->rollback();
            $io->streamClose();
            Mage::throwException($e->getMessage());
        } catch (Exception $e) {
            $adapter->rollback();
            $io->streamClose();
            Mage::logException($e);
            Mage::throwException(Mage::helper('shipping')->__('An error occurred while import table rates.'));
        }

        $adapter->commit();

        if ($this->_importErrors) {
            $error = Mage::helper('shipping')->__(
                'File has not been imported. See the following list of errors: %s',
                implode(" \n", $this->_importErrors)
            );
            Mage::throwException($error);
        }

        return $this;
    }

    /**
     * Return table rate array or false by rate request
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return array|boolean
     */
    public function getRate(Mage_Shipping_Model_Rate_Request $request)
    {
        $adapter = $this->_getReadAdapter();
        $bind = array(
            ':website_id' => (int)$request->getWebsiteId(),
            ':country_id' => $request->getDestCountryId(),
            ':region_id' => (int)$request->getDestRegionId(),
            ':postcode' => $request->getDestPostcode(),
            ':method_code' => $request->getMethodCode()
        );
        $select = $adapter->select()
            ->from($this->getMainTable())
            ->where('website_id = :website_id and method_code = :method_code')
            ->order(array('dest_country_id DESC', 'dest_region_id DESC', 'dest_zip DESC', 'condition_value DESC'))
            ->limit(1);

        // Render destination condition
        $orWhere = '(' . implode(') OR (', array(
                "dest_country_id = :country_id AND dest_region_id = :region_id AND dest_zip = :postcode",
                "dest_country_id = :country_id AND dest_region_id = :region_id AND dest_zip = ''",
                // Handle asterix in dest_zip field
                "dest_country_id = :country_id AND dest_region_id = :region_id AND dest_zip = '*'",
                "dest_country_id = :country_id AND dest_region_id = 0 AND dest_zip = '*'",
                "dest_country_id = '0' AND dest_region_id = :region_id AND dest_zip = '*'",
                "dest_country_id = '0' AND dest_region_id = 0 AND dest_zip = '*'",
                "dest_country_id = :country_id AND dest_region_id = 0 AND dest_zip = ''",
                "dest_country_id = :country_id AND dest_region_id = 0 AND dest_zip = :postcode",
                "dest_country_id = :country_id AND dest_region_id = 0 AND dest_zip = '*'",
            )) . ')';
        $select->where($orWhere);

        // Render condition by condition name
        if (is_array($request->getConditionName())) {
            $orWhere = array();
            $i = 0;
            foreach ($request->getConditionName() as $conditionName) {
                $bindNameKey = sprintf(':condition_name_%d', $i);
                $bindValueKey = sprintf(':condition_value_%d', $i);
                $orWhere[] = "(condition_name = {$bindNameKey} AND condition_value <= {$bindValueKey})";
                $bind[$bindNameKey] = $conditionName;
                $bind[$bindValueKey] = $request->getData($conditionName);
                $i++;
            }

            if ($orWhere) {
                $select->where(implode(' OR ', $orWhere));
            }
        } else {
            $bind[':condition_name'] = $request->getConditionName();
            $bind[':condition_value'] = $request->getData($request->getConditionName());

            $select->where('condition_name = :condition_name');
            $select->where('condition_value <= :condition_value');
        }

        $result = $adapter->fetchRow($select, $bind);
        // Normalize destination zip code
        if ($result && $result['dest_zip'] == '*') {
            $result['dest_zip'] = '';
        }

        return $result;
    }

    /**
     * Validate row for import and return table rate array or false
     * Error will be add to _importErrors array
     *
     * @param array $row
     * @param int $rowNumber
     * @return array|false
     */
    protected function _getImportRow($row, $rowNumber = 0)
    {
        // validate row
        if (count($row) < 5) {
            $this->_importErrors[] = Mage::helper('shipping')->__('Invalid Table Rates format in the Row #%s',
                $rowNumber);

            return false;
        }

        // strip whitespace from the beginning and end of each row
        foreach ($row as $k => $v) {
            $row[$k] = trim($v);
        }

        // validate country
        if (isset($this->_importIso2Countries[$row[0]])) {
            $countryId = $this->_importIso2Countries[$row[0]];
        } elseif (isset($this->_importIso3Countries[$row[0]])) {
            $countryId = $this->_importIso3Countries[$row[0]];
        } elseif ($row[0] == '*' || $row[0] == '') {
            $countryId = '0';
        } else {
            $this->_importErrors[] = Mage::helper('shipping')->__('Invalid Country "%s" in the Row #%s.', $row[0],
                $rowNumber);

            return false;
        }

        // validate region
        if ($countryId != '0' && isset($this->_importRegions[$countryId][$row[1]])) {
            $regionId = $this->_importRegions[$countryId][$row[1]];
        } elseif ($row[1] == '*' || $row[1] == '') {
            $regionId = 0;
        } else {
            $this->_importErrors[] = Mage::helper('shipping')->__('Invalid Region/State "%s" in the Row #%s.', $row[1],
                $rowNumber);

            return false;
        }

        // detect zip code
        if ($row[2] == '*' || $row[2] == '') {
            $zipCode = '*';
        } else {
            $zipCode = $row[2];
        }

        // detect and validate method code
        $validMethodCodes = Mage::getModel('pShipping/source_shippingType')->getAllowedMethods();
        $methodCode = $row[3];
        if (empty($row[3]) || !isset($validMethodCodes[$row[3]])) {
            $this->_importErrors[] = Mage::helper('pShipping')->__("Invalid Method code '%s' in the Row #%s.", $row[3],
                $rowNumber);

            return false;
        }

        // validate condition value
        $value = $this->_parseDecimalValue($row[4]);
        if ($value === false) {
            $this->_importErrors[] = Mage::helper('shipping')->__('Invalid %s "%s" in the Row #%s.',
                $this->_getConditionFullName($this->_importConditionName), $row[4], $rowNumber);

            return false;
        }

        // validate price
        $price = $this->_parseDecimalValue($row[5]);
        if ($price === false) {
            $this->_importErrors[] = Mage::helper('shipping')->__('Invalid Shipping Price "%s" in the Row #%s.',
                $row[5], $rowNumber);

            return false;
        }

        // protect from duplicate
        $hash = sprintf("%s-%d-%s-%s-%F", $countryId, $regionId, $zipCode, $methodCode, $value);
        if (isset($this->_importUniqueHash[$hash])) {
            $this->_importErrors[] = Mage::helper('shipping')->__(
                'Duplicate Row #%s (Country "%s", Region/State "%s", Zip "%s", Method Code "%s" and Value "%s").',
                $rowNumber,
                $row[0],
                $row[1],
                $zipCode,
                $methodCode,
                $value
            );

            return false;
        }
        $this->_importUniqueHash[$hash] = true;

        return array(
            $this->_importWebsiteId,    // website_id
            $countryId,                 // dest_country_id
            $regionId,                  // dest_region_id,
            $zipCode,                   // dest_zip
            $methodCode,                // method_code
            $this->_importConditionName,// condition_name,
            $value,                     // condition_value
            $price                      // price
        );
    }

    /**
     * Save import data batch
     *
     * @param array $data
     * @return Mage_Shipping_Model_Resource_Carrier_Tablerate
     */
    protected function _saveImportData(array $data)
    {
        if (!empty($data)) {
            $columns = array(
                'website_id',
                'dest_country_id',
                'dest_region_id',
                'dest_zip',
                'method_code',
                'condition_name',
                'condition_value',
                'price'
            );
            $this->_getWriteAdapter()->insertArray($this->getMainTable(), $columns, $data);
            $this->_importedRows += count($data);
        }

        return $this;
    }
}
