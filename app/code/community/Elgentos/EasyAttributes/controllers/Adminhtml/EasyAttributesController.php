<?php

class Elgentos_EasyAttributes_Adminhtml_EasyAttributesController extends Mage_Adminhtml_Controller_Action
{

    public function indexAction() {
        if(Mage::getStoreConfig('easyattributes/general/disabled')) {
            Mage::getSingleton('core/session')->addError('Elgentos EasyAttributes module is disabled! See configuration.');
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
        $resource = Mage::getSingleton('core/resource');
        $db = $resource->getConnection('core_write');

        $attributeId = $this->getRequest()->getParam('attribute_id');

        try {
            if (Mage::getStoreConfig('easyattributes/general/disabled')) {
                throw new Magento_Exception('Elgentos EasyAttributes module is disabled! See configuration.');
            }

            $merge = $this->getRequest()->getParam('merge');
            if (!isset($merge) || count($merge) == 0) {
                throw new Magento_Exception('No options to merge selected!');
            }

            if (isset($merge) && count($merge) == 1) {
                throw new Magento_Exception('Only one option to merge is selected!');
            }

            $mergeGoal = $this->getRequest()->getParam('mergegoal');
            if (empty($mergeGoal)) {
                throw new Magento_Exception('You haven\'t selected a merge goal.');
            }

            $deleteOptions = array('delete' => array(), 'value' => array());
            foreach ($merge as $value) {
                if ($value == $mergeGoal) continue;

                // Do the hard work
                $options = $db->fetchAll(
                    $db->select()
                    ->from($resource->getTableName('catalog_product_entity_int'), array('value_id', 'value'))
                    ->where('attribute_id = ?', $attributeId)
                    ->where('value = ?', $value)
                );

                foreach ($options as $option) {
                    $db->update($resource->getTableName('catalog_product_entity_int'), ['value' => $mergeGoal], $db->quoteInto('value_id = ?', $option['value_id']));
                }

                // Set array to delete option value
                $deleteOptions['delete'][$value] = true;
                $deleteOptions['value'][$value] = true;
            }

            // Delete option value
            if (count($deleteOptions['delete'])) {
                $setup = new Mage_Eav_Model_Entity_Setup('core_setup');
                $setup->addAttributeOption($deleteOptions);
            }

            Mage::getSingleton('core/session')->addSuccess(Mage::helper('easyattributes')->__('Attribute options are merged.'));
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError(Mage::helper('easyattributes')->__($e->getMessage()));
        }

        $this->_redirect('*/catalog_product_attribute/edit', array('attribute_id' => $attributeId));
    }
}
