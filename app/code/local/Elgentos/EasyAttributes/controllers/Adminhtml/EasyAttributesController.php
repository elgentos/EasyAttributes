<?php

class Elgentos_EasyAttributes_Adminhtml_EasyAttributesController extends Mage_Core_Controller_Front_Action
{

    public function indexAction() {
        if(Mage::getStoreConfig('easyattributes/general/disabled')) {
            if(count($error)) Mage::getSingleton('adminhtml/session')->addError('Elgentos EasyAttributes module is disabled! See configuration.');
            $this->_redirectReferer();
        }
        $options = $this->getRequest()->getParam('options');
        $options = explode("\n",$options);
        $attr = Mage::helper('easyattributes')->getAttributeInformation($this->getRequest()->getParam('attribute_id'));
        $existing = array_flip(Mage::helper('easyattributes')->getAllAttributeValuesFromAttribute($attr['attribute_code']));

        $success = array();
        $error = array();
        foreach($options as $key=>$option) {
            $option = trim($option);
            if(!isset($existing[$option])) { // skip existing values
                if(Mage::helper('easyattributes')->addAttributeValue($attr['attribute_code'],$option)) {
                    $success[] = Mage::helper('easyattributes')->__('Attribute option') . ' \''.$option.'\' ' . Mage::helper('easyattributes')->__('is added to') . ' \'' . Mage::helper('easyattributes')->__($attr['frontend_label']) . '\'';
                }
            } else {
                $error[] = Mage::helper('easyattributes')->__('Attribute option') . ' \''.$option.'\' ' . Mage::helper('easyattributes')->__('already existed in') . ' \'' . Mage::helper('easyattributes')->__($attr['frontend_label']) . '\'';
            }
        }

        if(count($success)) Mage::getSingleton('core/session')->addSuccess(implode("<br />",$success));
        if(count($error)) Mage::getSingleton('core/session')->addError(implode("<br />",$error));
        $this->_redirectReferer();
    }

    public function mergeAction() {
        $this->write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $this->read = Mage::getSingleton('core/resource')->getConnection('core_read');

        $error = array();
        $success = array();

        if(Mage::getStoreConfig('easyattributes/general/disabled')) {
            if(count($error)) Mage::getSingleton('adminhtml/session')->addError('Elgentos EasyAttributes module is disabled! See configuration.');
            $this->_redirectReferer();
        }

        $merge = $this->getRequest()->getParam('merge');
        if(!isset($merge) || count($merge)==0) {
            $error[] = "No options to merge selected!";
        }
        if(isset($merge) && count($merge)==1) {
            $error[] = "Only one option to merge is selected!";
        }
        $mergegoal = $this->getRequest()->getParam('mergegoal');
        if(empty($mergegoal)) {
            $error[] = "You haven't selected a merge goal.";
        }
        $attribute_id = $this->getRequest()->getParam('attribute_id');
        if(count($error)==0) {
            foreach($merge as $value) {
                if($value==$mergegoal) continue;
                // do the hard work
                $options = $this->read->fetchAll('SELECT value_id,value FROM catalog_product_entity_int WHERE attribute_id = ? AND value = ?',array($attribute_id,$value));
                foreach($options as $option) {
                    $this->write->query('UPDATE `' . Mage::getSingleton('core/resource')->getTableName('catalog_product_entity_int') . '` SET value = \''.$mergegoal.'\' WHERE value_id = \''.$option['value_id'].'\'');
                }
                // delete option value
                $deleteOptions['delete'][$value] = true;
                $deleteOptions['value'][$value] = true;
            }
            // delete option value
            $setup = new Mage_Eav_Model_Entity_Setup('core_setup');
            $setup->addAttributeOption($deleteOptions);
        }

        if(count($success)) Mage::getSingleton('core/session')->addSuccess(implode("<br />",$success));
        if(count($error)) Mage::getSingleton('core/session')->addError(implode("<br />",$error));
        $this->_redirectReferer();
    }
}
