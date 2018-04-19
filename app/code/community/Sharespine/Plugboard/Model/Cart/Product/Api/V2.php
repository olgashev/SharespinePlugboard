<?php


/**
 * Shopping cart api for product
 */
class Sharespine_Plugboard_Model_Cart_Product_Api_V2 extends Mage_Checkout_Model_Cart_Product_Api_V2
{

    /**
     * Get price
     * @param Mage_Sales_Model_Quote_Item $quoteItem
     * @param array $productItem
     * @param bool $save
     */
    private function _setCustomPrice(Mage_Sales_Model_Quote_Item &$quoteItem, array $productItem, $save = true){

        if(isset($productItem['custom_price'])){
            $quoteItem->setCustomPrice($productItem['custom_price']);
            $quoteItem->setOriginalCustomPrice($productItem['custom_price']);
            $quoteItem->getProduct()->setIsSuperMode(true);

            if($save) $quoteItem->save();
        }
    }



    /***************************************
     * Below are one method, "add"
     * All we've done is to add a call to $this->_setCustomPrice()
     * Except for that, the whole methods are stock Magento CE 1.9.1.0
     * For reference:
     * @see Mage_Checkout_Model_Cart_Product_Api
     ***************************************/


    /**
     * @param  $quoteId
     * @param  $productsData
     * @param  $store
     * @return bool
     */
    public function add($quoteId, $productsData, $store = null)
    {
        $quote = $this->_getQuote($quoteId, $store);
        if (empty($store)) {
            $store = $quote->getStoreId();
        }

        $productsData = $this->_prepareProductsData($productsData);
        if (empty($productsData)) {
            $this->_fault('invalid_product_data');
        }

        $errors = array();
        foreach ($productsData as $productItem) {
            if (isset($productItem['product_id'])) {
                $productByItem = $this->_getProduct($productItem['product_id'], $store, "id");
            } else if (isset($productItem['sku'])) {
                $productByItem = $this->_getProduct($productItem['sku'], $store, "sku");
            } else {
                $errors[] = Mage::helper('checkout')->__("One item of products do not have identifier or sku");
                continue;
            }

            $productRequest = $this->_getProductRequest($productItem);
            try {
                $result = $quote->addProduct($productByItem, $productRequest);

                $this->_setCustomPrice($result,$productItem); // Sharespine: this is the only added/changed row

                if (is_string($result)) {
                    Mage::throwException($result);
                }
            } catch (Mage_Core_Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (!empty($errors)) {
            $this->_fault("add_product_fault", implode(PHP_EOL, $errors));
        }

        try {
            $quote->collectTotals()->save();
        } catch (Exception $e) {
            $this->_fault("add_product_quote_save_fault", $e->getMessage());
        }

        return true;
    }

    /**
     * Compatibility with older versions of magento
     * See comments below for changed rows
     *
     * Return an Array of Object attributes.
     *
     * @param Mixed $data
     * @return Array
     */
    protected function _prepareProductsData($data){
        if (is_object($data)) {
            $arr = get_object_vars($data);
            foreach ($arr as $key => $value) {
                $assocArr = array();
                if (is_array($value)) {
                    foreach ($value as $v) {
                        if (is_object($v) && count(get_object_vars($v))==2
                            && isset($v->key) && isset($v->value)) {
                            $assocArr[$v->key] = $v->value;
                        }
                    }
                }
                if (!empty($assocArr)) {
                    $arr[$key] = $assocArr;
                }
            }
            $arr = $this->_prepareProductsData($arr);
            return is_array($arr) ? $arr : null; // Sharespine: Changed
        }
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_object($value) || is_array($value)) {
                    $data[$key] = $this->_prepareProductsData($value);
                } else {
                    $data[$key] = $value;
                }
            }
            return is_array($data) ? $data : null; // Sharespine: Changed
        }
        return $data;
    }
}
