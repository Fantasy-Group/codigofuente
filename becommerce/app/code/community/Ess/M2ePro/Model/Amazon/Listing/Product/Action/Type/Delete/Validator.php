<?php

/*
 * @copyright  Copyright (c) 2013 by  ESS-UA.
 */

class Ess_M2ePro_Model_Amazon_Listing_Product_Action_Type_Delete_Validator
    extends Ess_M2ePro_Model_Amazon_Listing_Product_Action_Type_Validator
{
    // ########################################

    public function isValid()
    {
        $params = $this->getParams();

        if (empty($params['remove']) && !$this->validateBlocked()) {
            return false;
        }

        if (!$this->validateLockedObject()) {
            return false;
        }

        if ($this->getVariationManager()->isRelationParentType() && !$this->validateParentListingProductFlags()) {
            return false;
        }

        if (!$this->validatePhysicalUnitAndSimple()) {
            return false;
        }

        if ($this->getListingProduct()->isNotListed()) {

            if (empty($params['remove'])) {

                // M2ePro_TRANSLATIONS
                // Item is not listed or not available
                $this->addMessage('Item is not listed or not available');

            } else {
                $this->getListingProduct()->deleteInstance();
                $this->getListingProduct()->isDeleted(true);
            }

            return false;
        }

        if (!$this->validateSku()) {
            return false;
        }

        return true;
    }

    // ########################################
}