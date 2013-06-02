<?php
/**
 * Firegento
 *
 * @category  Block
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 * @copyright 2013 FireGento Team (http://www.firegento.de). All rights served.
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Firegento_Pdf_Block_Adminhtml_ColumnOrder
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected $sortableListHtml = '';

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return '
            <style>.orderable_config li {list-style: disc inside; cursor:move;}</style>
            <p>' . $this->__('Define the order by moving the following items using your mouse:') . '<p>
            <ul id="' . $element->getHtmlId() . '_list" class="orderable_config">
            ' . $this->_getSortableListHtml($element) . '
            </ul>
            <input type="hidden" value="' . $element->getValue() . '" name="' . $element->getName() . '" id="' . $element->getHtmlId() . '">
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
                    console.log("Calculation columns width");
                    $A($("' . $element->getHtmlId() . '_list").children).each(function(item){
                        var current = $(item).attributes["data-column"].value;
                        if ($(item.attributes["data-width"])) {
                            newWidth += parseInt($(item).attributes["data-width"].value);
                        } else if ("disabled" == current) {
                            console.log(newWidth);
                            if (240 < newWidth) {
                                $("' . $element->getHtmlId() . '_warning").innerHTML = "' . $this->__('Caution: Your columns may overlap!') . '";
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
        $this->sortableListHtml .= '<li id="pdf-column-disabled" data-column="disabled" style="list-style:none">
            <div id="' . $element->getHtmlId() . '_warning" style="display:none" class="validation-advice"></div>
            <br />
            ' . $this->__('not to be listed') . '
            </li>';
        $this->_addListItems($availableItems);

        return $this->sortableListHtml;
    }

    protected function _addListItems($items)
    {
        foreach ($items as $name=>$item) {
            $this->sortableListHtml .= sprintf(
                '<li id="pdf-column-%s" data-column="%s" data-width="%s">%s</li>',
                $name,
                $name,
                $item['width'],
                $item['label']
            );
        }
        return $this;
    }
}