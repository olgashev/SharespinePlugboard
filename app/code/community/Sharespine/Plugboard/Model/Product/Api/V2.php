<?php

/**
 * Catalog product api V2
 */
class Sharespine_Plugboard_Model_Product_Api_V2 extends Mage_Catalog_Model_Product_Api_V2
{
    /**
     * Retrieve list of products with basic info (id, sku, type, set, name)
     *
     * @param null|object|array $filters
     * @param string|int $store
     * @param boolean $extendedInfo
     * @return array
     */
    public function items($filters = null, $store = null, $attributes = null, $extendedInfo = false)
    {

        // if extended info is not used, pass back to magento standard function
        if (!$extendedInfo) {
            return $this->getList($filters, $store);
        }

        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addStoreFilter($this->_getStoreId($store))
            ->addAttributeToSelect('*');

        // add gallery to collection
        $backendModel = $collection->getResource()->getAttribute('media_gallery')->getBackend();

        if (method_exists('Mage_Api_Helper_Data', 'parseFilters')) {
            /** @var $apiHelper Mage_Api_Helper_Data */
            $apiHelper = Mage::helper('api');
        } else {
            /** @var $apiHelper Sharespine_Plugboard_Helper_Backports */
            $apiHelper = Mage::helper('plugboard/backports');
        }

        $filters = $apiHelper->parseFilters($filters, $this->_filtersMap);
        try {
            foreach ($filters as $field => $value) {
                $collection->addFieldToFilter($field, $value);
            }
        } catch (Mage_Core_Exception $e) {
            $this->_fault('filters_invalid', $e->getMessage());
        }
        $resultRows = array();
        foreach ($collection as $product) {
            /** @var Mage_Catalog_Model_Product $product */

            $backendModel->afterLoad($product); //adding media gallery to the product object

            $result = array(
                'product_id' => $product->getId(),
                'sku'        => $product->getSku(),
                'name'       => $product->getName(),
                'set'        => $product->getAttributeSetId(),
                'type'       => $product->getTypeId(),
                'category_ids' => $product->getCategoryIds(),
                'website_ids'  => $product->getWebsiteIds(),
                'gallery' => $this->getGalleryItems($product)
            );

            $configInfo = $this->getConfigurableInfo($product);
            if (count($configInfo["children"])) {
                $result["configurable_children"] = $configInfo["children"];
            }
            if (count($configInfo["attributes"])) {
                $result["configurable_attributes"] = $configInfo["attributes"];
            }

            $allAttributes = array();
            if (!empty($attributes->attributes)) {
                $allAttributes = array_merge($allAttributes, $attributes->attributes);
            } else {
                foreach ($product->getTypeInstance(true)->getEditableAttributes($product) as $attribute) {
                    if ($this->_isAllowedAttribute($attribute, $attributes)) {
                        $allAttributes[] = $attribute->getAttributeCode();
                    }
                }
            }

            $_additionalAttributeCodes = array();
            if (!empty($attributes->additional_attributes)) {
                foreach ($attributes->additional_attributes as $k => $_attributeCode) {
                    $allAttributes[] = $_attributeCode;
                    $_additionalAttributeCodes[] = $_attributeCode;
                }
            }

            $_additionalAttribute = 0;
            foreach ($product->getTypeInstance(true)->getEditableAttributes($product) as $attribute) {
                if ($this->_isAllowedAttribute($attribute, $allAttributes)) {
                    if (in_array($attribute->getAttributeCode(), $_additionalAttributeCodes)) {
                        $result['additional_attributes'][$_additionalAttribute]['key'] = $attribute->getAttributeCode();
                        $result['additional_attributes'][$_additionalAttribute]['value'] = $product
                            ->getData($attribute->getAttributeCode());
                        $_additionalAttribute++;
                    } else {
                        $result[$attribute->getAttributeCode()] = $product->getData($attribute->getAttributeCode());
                    }
                }
            }

            $resultRows[] = $result;


        }
        return $resultRows;
    }

    private function getList($filters = null, $store = null)
    {
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addStoreFilter($this->_getStoreId($store))
            ->addAttributeToSelect('name');

        /** @var $apiHelper Mage_Api_Helper_Data */
        $apiHelper = Mage::helper('api');
        $filters = $apiHelper->parseFilters($filters, $this->_filtersMap);
        try {
            foreach ($filters as $field => $value) {
                $collection->addFieldToFilter($field, $value);
            }
        } catch (Mage_Core_Exception $e) {
            $this->_fault('filters_invalid', $e->getMessage());
        }
        $result = array();
        foreach ($collection as $product) {
            $result[] = array(
                'created_at' => $product->getCreatedAt(),
                'updated_at' => $product->getUpdatedAt(),
                'product_id' => $product->getId(),
                'sku'        => $product->getSku(),
                'name'       => $product->getName(),
                'set'        => $product->getAttributeSetId(),
                'type'       => $product->getTypeId(),
                'category_ids' => $product->getCategoryIds(),
                'website_ids'  => $product->getWebsiteIds()
            );
        }
        return $result;
    }

    /**
     * Retrieve product info
     *
     * @param int|string $productId
     * @param string|int $store
     * @param stdClass   $attributes
     * @param string     $identifierType
     * @return array
     */
    public function info($productId, $store = null, $attributes = null, $identifierType = null, $extendedInfo = false)
    {
        // if extended info is not used, pass back to magento standard function
        if (!$extendedInfo) {
            return parent::info($productId, $store, $attributes, $identifierType);
        }


        // make sku flag case-insensitive
        if (!empty($identifierType)) {
            $identifierType = strtolower($identifierType);
        }

        $product = $this->_getProduct($productId, $store, $identifierType);

        $result = array( // Basic product data
            'product_id' => $product->getId(),
            'sku'        => $product->getSku(),
            'set'        => $product->getAttributeSetId(),
            'type'       => $product->getTypeId(),
            'categories' => $product->getCategoryIds(),
            'websites'   => $product->getWebsiteIds(),
            'gallery' => $this->getGalleryItems($product)
        );

        $configInfo = $this->getConfigurableInfo($product);
        if (count($configInfo["children"])) {
            $result["configurable_children"] = $configInfo["children"];
        }
        if (count($configInfo["attributes"])) {
            $result["configurable_attributes"] = $configInfo["attributes"];
        }

        $allAttributes = array();
        if (!empty($attributes->attributes)) {
            $allAttributes = array_merge($allAttributes, $attributes->attributes);
        } else {
            foreach ($product->getTypeInstance(true)->getEditableAttributes($product) as $attribute) {
                if ($this->_isAllowedAttribute($attribute, $attributes)) {
                    $allAttributes[] = $attribute->getAttributeCode();
                }
            }
        }

        $_additionalAttributeCodes = array();
        if (!empty($attributes->additional_attributes)) {
            foreach ($attributes->additional_attributes as $k => $_attributeCode) {
                $allAttributes[] = $_attributeCode;
                $_additionalAttributeCodes[] = $_attributeCode;
            }
        }

        $_additionalAttribute = 0;
        foreach ($product->getTypeInstance(true)->getEditableAttributes($product) as $attribute) {
            if ($this->_isAllowedAttribute($attribute, $allAttributes)) {
                if (in_array($attribute->getAttributeCode(), $_additionalAttributeCodes)) {
                    $result['additional_attributes'][$_additionalAttribute]['key'] = $attribute->getAttributeCode();
                    $result['additional_attributes'][$_additionalAttribute]['value'] = $product
                        ->getData($attribute->getAttributeCode());
                    $_additionalAttribute++;
                } else {
                    $result[$attribute->getAttributeCode()] = $product->getData($attribute->getAttributeCode());
                }
            }
        }

        return $result;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getConfigurableInfo(Mage_Catalog_Model_Product $product)
    {
        $info = array(
            "children" => array(),
            "attributes" => array()
        );

        if ($product->getTypeId()=='configurable') {
            /** @var Mage_Catalog_Model_Product_Type_Configurable $config */
            $config = Mage::getModel('catalog/product_type_configurable');

            // get all children from the config
            $children = array();
            foreach ($config->getChildrenIds($product->getId()) as $level) {
                foreach ($level as $child) {
                    $children[] = (int) $child;
                }
            }
            $info["children"] = array_unique($children);

            // get all attributes used for config
            foreach ($config->getConfigurableAttributes($product) as $attribute) {
                /** @var Mage_Catalog_Model_Product_Type_Configurable_Attribute $attribute */
                $info["attributes"][] = $attribute->getData("product_attribute")->getData("attribute_code");
            }
        }

        return $info;
    }

    /**
     * Retrieve images for product
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getGalleryItems(Mage_Catalog_Model_Product $product)
    {

        $gallery = $this->_getGalleryAttribute($product);
        if ($gallery === null) {
            return array();
        }

        $galleryData = $product->getData(Mage_Catalog_Model_Product_Attribute_Media_Api::ATTRIBUTE_CODE);

        if (!isset($galleryData['images']) || !is_array($galleryData['images'])) {
            return array();
        }

        $result = array();

        foreach ($galleryData['images'] as &$image) {
            $result[] = $this->_imageToArray($image, $product);
        }

        return $result;
    }

    /**
     * Retrieve gallery attribute from product
     *
     * @param Mage_Catalog_Model_Product $product
     * @param Mage_Catalog_Model_Resource_Eav_Mysql4_Attribute|boolean
     * @return null
     */
    protected function _getGalleryAttribute($product)
    {
        $attributes = $product->getTypeInstance(true)
            ->getSetAttributes($product);

        if (!isset($attributes[Mage_Catalog_Model_Product_Attribute_Media_Api::ATTRIBUTE_CODE])) {
            return null;
        }

        return $attributes[Mage_Catalog_Model_Product_Attribute_Media_Api::ATTRIBUTE_CODE];
    }

    /**
     * Converts image to api array data
     *
     * @param array $image
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    protected function _imageToArray(&$image, $product)
    {
        $result = array(
            'file'      => $image['file'],
            'label'     => $image['label'],
            'position'  => $image['position'],
            'exclude'   => $image['disabled'],
            'url'       => Mage::getSingleton('catalog/product_media_config')->getMediaUrl($image['file']),
            'types'     => array()
        );


        foreach ($product->getMediaAttributes() as $attribute) {
            if ($product->getData($attribute->getAttributeCode()) == $image['file']) {
                $result['types'][] = $attribute->getAttributeCode();
            }
        }

        return $result;
    }
}
