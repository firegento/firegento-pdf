<?php
/**
 * This file is part of the FIREGENTO project.
 *
 * FireGento_GermanSetup is free software; you can redistribute it and/or
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
 * @copyright 2013 FireGento Team (http://www.firegento.de). All rights served.
 * @license   http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 */
/**
 * Default item model rewrite.
 *
 * @category  FireGento
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 * @copyright 2013 FireGento Team (http://www.firegento.de). All rights served.
 * @license   http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 */
class FireGento_Pdf_Model_Items_Grouped extends Mage_Sales_Model_Order_Pdf_Items_Invoice_Grouped
{
    /**
     * Draw process
     */
    public function draw($position = 1)
    {
        $type = $this->getItem()->getOrderItem()->getRealProductType();
        $renderer = $this->getRenderedModel()->getRenderer($type);
        $renderer->setOrder($this->getOrder());
        $renderer->setItem($this->getItem());
        $renderer->setPdf($this->getPdf());
        $renderer->setPage($this->getPage());

        $renderer->draw($position);
        $this->setPage($renderer->getPage());
    }
}
