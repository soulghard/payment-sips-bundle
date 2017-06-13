<?php

namespace Kptive\PaymentSipsBundle\Tests\Plugin;

use JMS\Payment\CoreBundle\Entity\FinancialTransaction;
use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Entity\Payment;
use JMS\Payment\CoreBundle\Entity\ExtendedData;
use JMS\Payment\CoreBundle\Entity\PaymentInstruction;
use JMS\Payment\CoreBundle\Plugin\PluginInterface;
use JMS\Payment\CoreBundle\PluginController\Result;
use Kptive\PaymentSipsBundle\Plugin\ReturnHandler;

/**
 * @author Hubert Moutot <hubert.moutot@gmail.com>
 */
class ReturnHandlerTest extends \PHPUnit_Framework_TestCase
{

    private $returnHandler;

    private $ppc;

    public function setUp()
    {
        $this->ppc = $this->getPluginControllerMock();
        $this->returnHandler = new ReturnHandler($this->ppc);
    }

    public function testHandleSuccess()
    {
        $transaction = $this->createTransaction(100, 978);

        $this->ppc
            ->expects($this->once())
            ->method('approveAndDeposit')
            ->will($this->returnValue(new Result($transaction, Result::STATUS_SUCCESS, PluginInterface::REASON_CODE_SUCCESS)));

        $result = $this->returnHandler->handle($transaction->getPayment()->getPaymentInstruction(), array('transaction_id' => 1));

        $this->assertEquals(Result::STATUS_SUCCESS, $result->getStatus());
    }

    public function testHandleFailedResult()
    {
        $transaction = $this->createTransaction(100, 978);

        $this->ppc
            ->expects($this->once())
            ->method('approveAndDeposit')
            ->will($this->returnValue(new Result($transaction, Result::STATUS_FAILED, PluginInterface::REASON_CODE_INVALID)));

        $result = $this->returnHandler->handle($transaction->getPayment()->getPaymentInstruction(), array('transaction_id' => 1));

        $this->assertEquals(Result::STATUS_FAILED, $result->getStatus());
    }

    public function getPluginControllerMock()
    {
        $ppc = $this->getMockBuilder('JMS\Payment\CoreBundle\PluginController\EntityPluginController')
            ->disableOriginalConstructor()
            ->setMethods(array('approveAndDeposit'))
            ->getMock();

        return $ppc;
    }

    protected function createTransaction($amount, $currency)
    {
        $transaction = new FinancialTransaction();
        $transaction->setState(FinancialTransactionInterface::STATE_PENDING);
        $transaction->setRequestedAmount($amount);

        $paymentInstruction = new PaymentInstruction($amount, $currency, 'sips', new ExtendedData());

        $payment = new Payment($paymentInstruction, $amount);
        $payment->addTransaction($transaction);

        return $transaction;
    }

}
