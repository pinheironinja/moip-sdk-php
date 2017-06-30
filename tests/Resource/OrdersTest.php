<?php

namespace Moip\Tests\Resource;

use Moip\Resource\Orders;
use Moip\Tests\TestCase;

class OrdersTest extends TestCase
{
    /**
     * Send http request.
     *
     * @param \Moip\Resource\Orders $order
     * @param string                $body
     *
     * @return \Moip\Resource\Orders
     */
    private function executeOrder(Orders $order = null, $body = null)
    {
        if (empty($body)) {
            $body = $this->body_order;
        }
        if (empty($order)) {
            $order = $this->createOrder();
        }
        $this->mockHttpSession($body);

        return $order->create();
    }

    /**
     * @const string
     */
    public function testAssertConstPath()
    {
        $this->assertEquals('orders', Orders::PATH);
    }

    /**
     * Defines what kind of payee as pripmary.
     *
     * @const string
     */
    public function testAssertConstReceiverTypePrimary()
    {
        $this->assertEquals('PRIMARY', Orders::RECEIVER_TYPE_PRIMARY);
    }

    /**
     * Defines what kind of payee as secundary.
     *
     * @const string
     */
    public function testAssertConstReceiverTypeSecpndary()
    {
        $this->assertEquals('SECONDARY', Orders::RECEIVER_TYPE_SECONDARY);
    }

    /**
     * Currency used in the application.
     *
     * @const string
     */
    public function testAssertConstAmountCurrency()
    {
        $this->assertEquals('BRL', Orders::AMOUNT_CURRENCY);
    }

    /**
     * MoipTest creating an order.
     */
    public function testCreateOrder()
    {
        $order_created = $this->executeOrder();

        $this->assertEquals($this->last_ord_id, $order_created->getOwnId());
        $this->assertEquals('CREATED', $order_created->getStatus());
    }

    /**
     * Teste if created itens price is correct.
     */
    public function testItens()
    {
        $order_created = $this->executeOrder();
        $itens = $order_created->getItemIterator()->getArrayCopy();
        $this->assertEquals(100000, $itens[0]->price);
        $this->assertEquals(990, $itens[1]->price);
    }

    /**
     *MoipTest if the total is correct.
     */
    public function testTotal()
    {
        $order = $this->executeOrder();

        $total = $order->getSubtotalItems() + $order->getSubtotalShipping() + $order->getSubtotalAddition() - $order->getSubtotalDiscount();
        $this->assertEquals($total, $order->getAmountTotal());
    }

    /**
     * MoipTest if the total is equal to the expected total.
     */
    public function testTotalConstant()
    {
        $order = $this->executeOrder();
        $expected = (100000 + 2 * 990 + 1490) - 1000;
        $total_calculated = $order->getSubtotalItems() + $order->getSubtotalShipping() + $order->getSubtotalAddition() - $order->getSubtotalDiscount();

        $this->assertEquals($expected, $total_calculated);
        $this->assertEquals($expected, $order->getAmountTotal());
    }

    /**
     * MoipTest if order is created with installment preferences.
     */
    public function testCreateOrderWithInstallmentPreferences() 
    {
        $quantity = [1,6];
        $discount = 0;
        $additional = 100;
        $order = $this->createOrder()->setInstallmentCheckoutPreferences($quantity, $discount, $additional);
        $returned_order = $this->executeOrder($order);

        $this->assertNotEmpty($returned_order->getId());
        $this->assertEquals([1,6],$returned_order->getCheckoutPreferences()->installments->quantity);
    }

    public function testCreateOrderAddingReceiverNoAmount() 
    {
        $order = $this->createOrder()->addReceiver('MPA-7ED9D2D0BC81', 'PRIMARY');
        $returned_order = $this->executeOrder($order);
        $this->assertNotEmpty($returned_order->getId());
        $receivers = $returned_order->getReceiverIterator();
        $this->assertEquals('MPA-7ED9D2D0BC81',$receivers[0]->moipAccount->id);
    }
    
    public function testCreateOrderAddingReceiverAmountFixed() 
    {
        $order = $this->createOrder()->addReceiver('MPA-7ED9D2D0BC81', 'PRIMARY', 30000);
        $receivers = $order->getReceiverIterator();
        $this->assertEquals(30000, $receivers[0]->amount->fixed);
    }
    
    public function testCreateOrderAddingReceiverAmountPercentual() 
    {
        $order = $this->createOrder()->addReceiver('MPA-7ED9D2D0BC81', 'PRIMARY', NULL, 40);
        $receivers = $order->getReceiverIterator();
        $this->assertEquals(40, $receivers[0]->amount->percentual);
    }
    
    public function testCreateOrderAddingReceiverFeePayor() 
    {
        $order = $this->createOrder()->addReceiver('MPA-7ED9D2D0BC81', 'PRIMARY', NULL, 40, true);
        $receivers = $order->getReceiverIterator();
        $this->assertEquals(40, $receivers[0]->amount->percentual);
        $this->assertTrue($receivers[0]->feePayor);
        $order2 = $this->createOrder()->addReceiver('MPA-7ED9D2D0BC81', 'PRIMARY', 30000, NULL, true);
        $receivers2 = $order2->getReceiverIterator();
        $this->assertEquals(30000, $receivers2[0]->amount->fixed);
        $this->assertTrue($receivers2[0]->feePayor);
    }
}
