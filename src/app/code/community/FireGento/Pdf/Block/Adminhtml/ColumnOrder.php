<?php
/**
 * This file is part of a FireGento e.V. module.
 *
 * This FireGento e.V. module is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License version 3 as
 * published by the Free Software Foundation.
 *
 * This script is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * PHP version 5
 *
 * @category  FireGento
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 * @copyright 2014 FireGento Team (http://www.firegento.com)
 * @license   http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 */
/**
 * Block which enables the user to change the column order in the admin via drag & drop.
 *
 * @category  FireGento
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 */
class Firegento_Pdf_Block_Adminhtml_ColumnOrder
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected $_sortableListHtml = '';

    /**
     * generate html for orderable list
     *
     * @param  Varien_Data_Form_Element_Abstract $element form element to render
     *
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return '
            <style>.orderable_config li {list-style: disc inside; cursor:move;}</style>
            <p>' . $this->__('Define the order by moving the following items using your mouse:') . '<p>
            <ul id="' . $element->getHtmlId() . '_list" class="orderable_config">
            ' . $this->_getSortableListHtml($element) . '
            </ul>
            <input type="hidden" value="' . $element->getValue() . '" name="' . $element->getName() .
                '" id="' . $element->getHtmlId() . '">
            <script type="text/javascript">
                Sortable.create("' . $element->getHtmlId() . '_list", {
                    onUpdate: function() {
                        var inheritCheckbox = $("' . $element->getHtmlId() . '_inherit");
                        if (inheritCheckbox) {
                            inheritCheckbox.checked=false;
                        }
                        var newOrder="";
                        $A(this.element.children).each(function(item){
                            var current = $(item).attributes["data-column"].value;
                            if ("disabled" == current) {
                                $("' . $element->getHtmlId() . '").value = newOrder;
                            } else {
                                if (0 < newOrder.length) {
                                    newOrder+=",";
                                }
                                newOrder+=current;
                            }
                        });
                        validateSortableWidth();
                    }
                });
                validateSortableWidth = function () {
                    var newWidth=0;
                    $A($("' . $element->getHtmlId() . '_list").children).each(function(item){
                        var current = $(item).attributes["data-column"].value;
                        if ($(item.attributes["data-width"])) {
                            newWidth += parseInt($(item).attributes["data-width"].value);
                        } else if ("disabled" == current) {
                            if (240 < newWidth) {
                                $("' . $element->getHtmlId() . '_warning").innerHTML = "'
                                . $this->__('Caution: Your columns may overlap!') . '";
                                $("' . $element->getHtmlId() . '_warning").show();
                            } else {
                                $("' . $element->getHtmlId() . '_warning").hide();
                            }
                        }
                    });
                };
                validateSortableWidth();
            </script>
        ';
    }

    /**
     * get html for list
     *
     * @param  Varien_Data_Form_Element_Abstract $element form element to render
     *
     * @return string
     */
    protected function _getSortableListHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $availableItems = array(
            'price_incl_tax'    => array('width' => 60, 'label' => $this->__('Price (incl. tax)')),
            'price'             => array('width' => 60, 'label' => $this->__('Price')),
            'qty'               => array('width' => 40, 'label' => $this->__('Qty')),
            'subtotal_incl_tax' => array('width' => 70, 'label' => $this->__('Subtotal (incl. tax)')),
            'subtotal'          => array('width' => 50, 'label' => $this->__('Subtotal')),
            'tax'               => array('width' => 50, 'label' => $this->__('Tax amount')),
            'tax_rate'          => array('width' => 50, 'label' => $this->__('Tax rate')),
        );
        $activeItems = array();
        foreach (explode(',', $element->getValue()) as $item) {
            $item = trim($item);
            if (array_key_exists($item, $availableItems)) {
                $activeItems[$item] = $availableItems[$item];
                unset($availableItems[$item]);
            }
        }

        $this->_addListItems($activeItems);
        $this->_sortableListHtml .= '<li id="pdf_column-disabled" data-column="disabled" style="list-style:none">
            <div id="' . $element->getHtmlId() . '_warning" style="display:none" class="validation-advice"></div>
            <br />
            ' . $this->__('not to be listed') . '
            </li>';
        $this->_addListItems($availableItems);

        return $this->_sortableListHtml;
    }

    /**
     * add items to list
     *
     * @param  array $items items to add
     *
     * @return $this
     */
    protected function _addListItems($items)
    {
        foreach ($items as $name=>$item) {
            $this->_sortableListHtml .= sprintf(
                '<li id="pdf_column-%s" data-column="%s" data-width="%s">%s</li>',
                $name,
                $name,
                $item['width'],
                $item['label']
            );
        }
        return $this;
    }
}
