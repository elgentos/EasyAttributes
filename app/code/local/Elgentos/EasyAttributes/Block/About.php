<?php

class Elgentos_EasyAttributes_Block_About extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface
{
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        return '<iframe src="http://elgentos.nl/iframe/" frameborder="0" style="width:100%;height:310px;" /></iframe>';
    }
}