<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Sales
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class Mage_Sales_Model_Order_Creditmemo_Total_Tax extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract
{
    public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        $shippingTaxAmount     = 0;
        $baseShippingTaxAmount = 0;
        $totalTax              = 0;
        $baseTotalTax          = 0;
        $order = $creditmemo->getOrder();

        foreach ($creditmemo->getAllItems() as $item) {
            if ($item->getOrderItem()->isDummy()) {
                continue;
            }
            $orderItemTax     = $item->getOrderItem()->getTaxAmount();
            $baseOrderItemTax = $item->getOrderItem()->getBaseTaxAmount();
            $orderItemQty = $item->getOrderItem()->getQtyOrdered();

            if ($orderItemTax && $orderItemQty) {
                /**
                 * Check item tax amount
                 */
                if ($item->isLast()) {
                    $tax = $orderItemTax - $item->getOrderItem()->getTaxRefunded();
                    $baseTax = $baseOrderItemTax - $item->getOrderItem()->getTaxRefunded();
                }
                else {
                    $tax = $orderItemTax*$item->getQty()/$orderItemQty;
                    $baseTax = $baseOrderItemTax*$item->getQty()/$orderItemQty;

                    $tax = $creditmemo->getStore()->roundPrice($tax);
                    $baseTax = $creditmemo->getStore()->roundPrice($baseTax);
                }

                $item->setTaxAmount($tax);
                $item->setBaseTaxAmount($baseTax);

                $totalTax += $tax;
                $baseTotalTax += $baseTax;
            }
        }

        if ($invoice = $creditmemo->getInvoice()) {
            $totalTax       += $invoice->getShippingTaxAmount();
            $baseTotalTax   += $invoice->getBaseShippingTaxAmount();
            $shippingTaxAmount      = $invoice->getShippingTaxAmount();
            $baseShippingTaxAmount  = $invoice->getBaseShippingTaxAmount();
        } else {
            $orderShippingAmount = $order->getShippingAmount();
            $baseOrderShippingAmount = $order->getBaseShippingAmount();
            $baseOrderShippingRefundedAmount = $order->getBaseShippingRefunded();

            $shippingTaxAmount = 0;
            $baseShippingTaxAmount = 0;

            if (($baseOrderShippingAmount - $baseOrderShippingRefundedAmount) > $creditmemo->getBaseShippingAmount()) {
                $shippingTaxAmount = $creditmemo->getShippingAmount()*($order->getShippingTaxAmount()/$orderShippingAmount);
                $baseShippingTaxAmount = $creditmemo->getBaseShippingAmount()*($order->getBaseShippingTaxAmount()/$baseOrderShippingAmount);
                $shippingTaxAmount = $creditmemo->getStore()->roundPrice($shippingTaxAmount);
                $baseShippingTaxAmount = $creditmemo->getStore()->roundPrice($baseShippingTaxAmount);
            } elseif (($baseOrderShippingAmount - $baseOrderShippingRefundedAmount) == $creditmemo->getBaseShippingAmount()) {
                $shippingTaxAmount = $order->getShippingTaxAmount() - $order->getShippingTaxRefunded();
                $baseShippingTaxAmount = $order->getBaseShippingTaxAmount() - $order->getBaseShippingTaxRefunded();
            }
            $totalTax += $shippingTaxAmount;
            $baseTotalTax += $baseShippingTaxAmount;
        }
        
        $allowedTax = $order->getTaxAmount() - $order->getTaxRefunded();
        $allowedBaseTax = $order->getBaseTaxAmount() - $order->getBaseTaxRefunded();;

        $totalTax = min($allowedTax, $totalTax);
        $baseTotalTax = min($allowedBaseTax, $baseTotalTax);

        $creditmemo->setTaxAmount($totalTax);
        $creditmemo->setBaseTaxAmount($baseTotalTax);

        $creditmemo->setShippingTaxAmount($shippingTaxAmount);
        $creditmemo->setBaseShippingTaxAmount($baseShippingTaxAmount);

        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $totalTax);
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseTotalTax);
        return $this;
    }
}
