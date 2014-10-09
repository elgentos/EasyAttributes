<?php

class Elgentos_EasyAttributes_Helper_Data extends Mage_Core_Helper_Abstract {

    public function getAttributeInformation($attribute) {
        if(is_numeric($attribute)) {
            $attributeId = $attribute;
        } else{
            $attributeId = Mage::getResourceModel('eav/entity_attribute')->getIdByCode('catalog_product',$attribute);
        }
        $attribute = Mage::getModel('catalog/resource_eav_attribute')->load($attributeId);
        return $attribute->getData();
    }

    public function getAllAttributeValuesFromAttribute($attribute) {
        $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product',$attribute);
        foreach ( $attribute->getSource()->getAllOptions(true, true) as $option){
            $attributeArray[$option['value']] = $option['label'];
        }
        return $attributeArray;
    }

    public function addAttributeValue($arg_attribute, $arg_value) {
        $attribute_model        = Mage::getModel('eav/entity_attribute');
        $attribute_options_model= Mage::getModel('eav/entity_attribute_source_table') ;

        $attribute_code         = $attribute_model->getIdByCode('catalog_product', $arg_attribute);
        $attribute              = $attribute_model->load($attribute_code);

        $attribute_table        = $attribute_options_model->setAttribute($attribute);
        $options                = $attribute_options_model->getAllOptions(false);

        $value['option'] = array($arg_value);
        $result = array('value' => $value);
        $attribute->setData('option',$result);
        $attribute->save();

        $attribute_model        = Mage::getModel('eav/entity_attribute');
        $attribute_options_model= Mage::getModel('eav/entity_attribute_source_table') ;

        $attribute_code         = $attribute_model->getIdByCode('catalog_product', $arg_attribute);
        $attribute              = $attribute_model->load($attribute_code);

        $attribute_table        = $attribute_options_model->setAttribute($attribute);
        $options                = $attribute_options_model->getAllOptions(false);

        foreach($options as $option) {
            if ($option['label'] == $arg_value) {
                return $option['value'];
            }
        }
        return false;
    }

}
