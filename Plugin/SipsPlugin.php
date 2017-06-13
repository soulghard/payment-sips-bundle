<?php

namespace Kptive\PaymentSipsBundle\Plugin;

use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Plugin\PluginInterface;
use JMS\Payment\CoreBundle\Plugin\AbstractPlugin;
use JMS\Payment\CoreBundle\Plugin\Exception\FinancialException;
use Psr\Log\LoggerInterface;

/**
 * @author Hubert Moutot <hubert.moutot@gmail.com>
 */
class SipsPlugin extends AbstractPlugin
{
    const RESPONSE_CODE_FAILED = 'failed';
    const RESPONSE_CODE_CANCELED = 'canceled';

    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function processes($name)
    {
        return 'sips' === $name;
    }

    public function approveAndDeposit(FinancialTransactionInterface $transaction, $retry)
    {
        $data = $transaction->getExtendedData();

        if (0 != $data->get('code')) {
            $transaction->setResponseCode(self::RESPONSE_CODE_FAILED);
            $transaction->setReasonCode($data->get('error'));
            $transaction->setState(FinancialTransactionInterface::STATE_FAILED);

            $this->logger->info(sprintf('Payment failed with error code %s. Error: %s', $data->get('code'), $data->get('error')));

            $ex = new FinancialException(sprintf('Payment failed with error code %s. Error: %s', $data->get('code'), $data->get('error')));
            $ex->setFinancialTransaction($transaction);
            throw $ex;
        }

        $transaction->setReferenceNumber($data->get('order_id'));

        if (17 == $data->get('response_code')) {
            $transaction->setResponseCode(self::RESPONSE_CODE_CANCELED);
            $transaction->setReasonCode('Payment canceled');
        } elseif (0 != $data->get('response_code')) {
            $transaction->setResponseCode(self::RESPONSE_CODE_FAILED);
            $transaction->setReasonCode(sprintf('Response code: %s', $data->get('response_code')));

            $this->logger->info(sprintf('Payment failed with error response_code %s. Error: %s', $data->get('response_code'), $data->get('error')));
        } else {
            $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS);
            $transaction->setReasonCode(PluginInterface::REASON_CODE_SUCCESS);

            $transaction->setProcessedAmount($data->get('amount') / 100);
        }
    }

}
