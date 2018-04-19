<?php

class Sharespine_Plugboard_Model_Info_Api extends Mage_Api_Model_Resource_Abstract
{
    /**
     * Retrieve info
     *
     * @return string
     */
    public function info()
    {

        $edition = "unknown";

        // Mage::getEdition() didn't exist in 1.6.2 (don't know exactly when it came..)
        if(method_exists('Mage','getEdition')) $edition = Mage::getEdition();

        $result = array(
            "module_version" => (string) Mage::getConfig()->getModuleConfig("Sharespine_Plugboard")->version,
            "magento_version" => Mage::getVersion(),
            "magento_edition" => $edition,
            "storeview_list" => $this->getStoreViewInfo(),
            "taxclass_list" => $this->getTaxClassInfo()
        );

        return json_encode($result,JSON_PRETTY_PRINT);
    }

    public function config(array $paths, $store = null){

        if(!count($paths)){
            $this->_fault('data_invalid');
        }

        $storeId = $this->_getStoreId($store);

        $result = array();

        foreach($paths as $path){
            $result[] = array(
                "path" => $path,
                "value" => Mage::getStoreConfig($path,$storeId)
            );
        }

        return json_encode($result,JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES);
    }

    public function productoptions($store = null){

        $storeId = $this->_getStoreId($store);


        /** @var Mage_Eav_Model_Resource_Entity_Attribute_Option_Collection $options */
        $options = Mage::getResourceModel('eav/entity_attribute_option_collection');
        $collection = $options->setStoreFilter($storeId);

        $return = array();
        foreach($collection as $option){
            if(!isset($return[$option->getAttributeId()])){
                /** @var Mage_Eav_Model_Entity_Attribute $attribute */
                $attribute = Mage::getModel('eav/entity_attribute')->load($option->getAttributeId());

                /** @var Mage_Eav_Model_Entity_Attribute_Option $option */
                $return[$option->getAttributeId()] = array(
                    "attribute_id" => $option->getAttributeId(),
                    "attribute_code" => $attribute->getAttributeCode(),
                    "attribute_label" => $attribute->getStoreLabel($storeId)
                );
            }

            $return[$option->getAttributeId()]["options"][] = array(
                "value" => $option->getOptionId(),
                "label" => $option->getValue()
            );
        }

        return json_encode(array_values($return),JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get array with all tax-classes
     * @return array
     */
    public function getTaxClassInfo(){

        $collection = Mage::getModel('tax/class')->getCollection();

        $rows = array();

        foreach($collection as $class){

            /** @var Mage_Tax_Model_Class $class */

            // get the "default" tax-rate for each product-class
            // set to null if it's a customer-class
            if($class->getClassType() == "PRODUCT"){
                $store = Mage::app()->getStore();
                $request = Mage::getSingleton('tax/calculation')->getRateRequest(null, null, null, $store);
                $percent = Mage::getSingleton('tax/calculation')->getRate($request->setProductClassId($class->getId()));
            } else {
                $percent = null;
            }


            $rows[] = array(
                "id" => (int) $class->getId(),
                "type" => $class->getClassType(),
                "name" => $class->getClassName(),
                "rate" => $percent
            );
        }

        return $rows;
    }

    /**
     * Get array with all store views
     * @return array
     */
    public function getStoreViewInfo(){

        $collection = Mage::getModel('core/store')->getCollection();

        $rows = array();

        foreach($collection as $store){

            /** @var Mage_Core_Model_Store $store */

            $rows[] = array(
                "store_id" => (int) $store->getId(),
                "store_name" => $store->getName(),
                "store_code" => $store->getCode(),
                "store_sort_order" => (int) $store->getSortOrder(),
                "store_is_active" => (boolean) $store->getIsActive(),
                "group_id" => (int) $store->getGroupId(),
                "group_name" => $store->getGroup()->getName(),
                "website_id" => (int) $store->getWebsiteId(),
                "website_name" => $store->getWebsite()->getName(),
                "website_code" => $store->getWebsite()->getCode(),
                "currency_base" => Mage::getStoreConfig("currency/options/base",$store),
                "currency_default_display" => Mage::getStoreConfig("currency/options/default",$store),
                "currency_allowed_display" => Mage::getStoreConfig("currency/options/allow",$store),
                "config_locale" => Mage::getStoreConfig("general/locale/code",$store),
                "config_catalog_prices_include_tax" => (boolean) Mage::getStoreConfig("tax/calculation/price_includes_tax",$store),
                "config_shipping_prices_include_tax" => (boolean) Mage::getStoreConfig("tax/calculation/shipping_includes_tax",$store),
                "config_shipping_tax_class_id" => (int) Mage::getStoreConfig("tax/classes/shipping_tax_class",$store),
            );
        }

        return $rows;
    }

    /**
     * Retrives store id from store code, if no store id specified,
     * it use seted session or admin store
     *
     * @param string|int $store
     * @return int
     */
    protected function _getStoreId($store = null)
    {
        if (is_null($store)) {
            $store = ($this->_getSession()->hasData($this->_storeIdSessionField)
                ? $this->_getSession()->getData($this->_storeIdSessionField) : 0);
        }

        try {
            $storeId = Mage::app()->getStore($store)->getId();
        } catch (Mage_Core_Model_Store_Exception $e) {
            $this->_fault('store_not_exists');
        }

        return $storeId;
    }
}
