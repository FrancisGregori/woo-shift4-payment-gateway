<?php

namespace Woo_Shift4_Payment_Gateway\Api;

use GuzzleHttp\Client;
use Carbon\Carbon;
use Woo_Shift4_Payment_Gateway\Api\Shift4API;

class Shift4Transation extends Shift4API
{

    public function __construct($accessToken = null, $clientUrl = null, $clientGuid = null, $clientAuthToken = null, $companyName = null, $interfaceName = null, $additionalHeaders = array())
    {

        parent::__construct($accessToken, $clientUrl, $clientGuid, $clientAuthToken, $companyName, $interfaceName, $additionalHeaders);

        $this->initializeSendData();

    }

    // METHODS
    public function authorization()
    {

        $this->callMethod = 'POST';

        $this->uri = 'transactions/authorization';

        $this->send();

    }

    public function capture()
    {

        $this->callMethod = 'POST';

        $this->uri = 'transactions/capture';

        $this->send();

    }

    public function sale()
    {

        $this->callMethod = 'POST';

        $this->uri = 'transactions/sale';

        $this->send();

    }

    public function invoice($invoice)
    {

        // Set up Guzzle client
        $this->client = new Client(array(
            'base_uri' => $this->clientUrl,
            'handler' => $this->stack,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'InterfaceVersion' => '1.0',
                'InterfaceName' => 'Madera Resident Portal',
                'CompanyName' => 'Madera Residential',
                'AccessToken' => $this->accessToken,
                'Invoice' => $invoice
            )
        ));

        $this->callMethod = 'GET';

        $this->setApiOptions(array(
            "RETURNEXPDATE"
        ));

        $this->uri = 'transactions/invoice';

        $this->send();

    }

    public function refund()
    {

        $this->callMethod = 'POST';

        $this->setApiOptions(array(
            "RETURNEXPDATE"
        ));

        $this->uri = 'transactions/refund';

        $this->send();

    }

    public function deleteInvoice($invoice)
    {

        // Set up Guzzle client
        $this->client = new Client(array(
            'base_uri' => $this->clientUrl,
            'handler' => $this->stack,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'InterfaceVersion' => '1.0',
                'InterfaceName' => 'Madera Resident Portal',
                'CompanyName' => 'Madera Residential',
                'AccessToken' => $this->accessToken,
                'Invoice' => $invoice
            )
        ));

        $this->setApiOptions(array(
            "RETURNEXPDATE"
        ));

        $this->callMethod = 'DELETE';

        $this->uri = 'transactions/invoice';

        $this->send();

    }

    // SETTERS
    public function setApiOptions($data = array())
    {

        // if(isset($this->sendData['apiOptions'])) return $this;

        if (empty($data)) {
            $data = array(
                "RETURNEXPDATE",
                "ALLOWPARTIALAUTH"
            );
        }

        $this->sendData['apiOptions'] = $data;

        return $this;

    }

    public function amount($amountData)
    {

        $this->sendData['amount'] = $amountData;

        return $this;

    }

    public function cashback($cashback)
    {

        $this->sendData['amount']['cashback'] = $cashback;

        return $this;

    }

    public function surcharge($surcharge)
    {

        $this->sendData['amount']['surcharge'] = $surcharge;

        return $this;

    }

    public function tax($tax)
    {

        $this->sendData['amount']['tax'] = $tax;

        return $this;

    }

    public function tip($tip)
    {

        $this->sendData['amount']['tip'] = $tip;

        return $this;

    }

    public function total($total)
    {

        $this->sendData['amount']['total'] = $total;

        return $this;

    }

    public function card($card)
    {

        $this->sendData['card'] = $card;

        return $this;

    }

    public function entryMode($entryMode)
    {

        // if(!array_key_exists('card', $this->sendData)) $this->sendData['card'] = array();

        $this->sendData['card']['entryMode'] = $entryMode;

        return $this;

    }

    public function expirationDate($expirationDate)
    {

        // if(!array_key_exists('card', $this->sendData)) $this->sendData['card'] = array();

        $this->sendData['card']['expirationDate'] = $expirationDate;

        return $this;

    }

    public function number($number)
    {

        // if(!array_key_exists('card', $this->sendData)) $this->sendData['card'] = array();

        $this->sendData['card']['number'] = $number;

        return $this;

    }

    public function present($present)
    {

        // if(!array_key_exists('card', $this->sendData)) $this->sendData['card'] = array();

        $this->sendData['card']['present'] = $present;

        return $this;

    }

    public function type($type)
    {

        $this->sendData['card']['type'] = $type;

        return $this;

    }

    public function securityCode($securityCode)
    {

        // if(!array_key_exists('card', $this->sendData)) $this->sendData['card'] = array();

        $this->sendData['card']['securityCode'] = array(
            'indicator' => 1,
            'value' => $securityCode
        );

        return $this;

    }

    public function clerk($clerk)
    {

        $this->sendData['clerk']['numericId'] = $clerk;

        return $this;

    }

    public function token($token)
    {

        if (!array_key_exists('card', $this->sendData)) $this->sendData['card'] = array();

        $this->sendData['card']['present'] = 'N';
        $this->sendData['card']['token'] = $token;

        return $this;

    }

    public function tokenSerialNumber($tokenSerialNumber)
    {

        if (!array_key_exists('card', $this->sendData)) $this->sendData['card'] = array();

        $this->sendData['card']['present'] = 'N';
        $this->sendData['card']['token']['serialNumber'] = $tokenSerialNumber;

        return $this;

    }

    public function tokenValue($tokenValue)
    {

        if (!array_key_exists('card', $this->sendData)) $this->sendData['card'] = array();

        $this->sendData['card']['present'] = 'N';
        $this->sendData['card']['token']['value'] = $tokenValue;

        return $this;

    }

    public function customer($customer)
    {

        $this->sendData['customer'] = $customer;

        return $this;

    }

    public function firstName($firstName)
    {

        $this->sendData['customer']['firstName'] = $firstName;

        return $this;

    }

    public function lastName($lastName)
    {

        $this->sendData['customer']['lastName'] = $lastName;

        return $this;

    }

    public function postalCode($postalCode)
    {

        $this->sendData['customer']['postalCode'] = $postalCode;

        return $this;

    }

    public function addressLine1($addressLine1)
    {

        $this->sendData['customer']['addressLine1'] = $addressLine1;

        return $this;

    }

    public function transaction($transaction)
    {

        $this->sendData['transaction'] = $transaction;

        return $this;

    }

    public function invoiceNumber($invoice)
    {

        $this->sendData['transaction']['invoice'] = $invoice;

        return $this;

    }

    public function notes($notes)
    {

        $this->sendData['transaction']['notes'] = $notes;

        return $this;

    }

    public function hotel($hotel)
    {

        $this->sendData['transaction']['hotel'] = $hotel;

        return $this;

    }

    /**
     * Adds purchase Card
     * @param  [array] $purchaseCard [contains customerReference, destinationPostalCode, productDescriptors]
     * @return this
     */
    public function purchaseCard($purchaseCard)
    {

        $this->sendData['transaction']['purchaseCard'] = $purchaseCard;

        return $this;

    }

    public function purchaseCardCustomerReference($customerReference)
    {

        if (!in_array('purchaseCard', $this->sendData['transaction'])) $this->sendData['transaction']['purchaseCard'] = array();

        $this->sendData['transaction']['purchaseCard']['customerReference'] = $customerReference;

        return $this;

    }

    public function purchaseCardDestinationPostalCode($destinationPostalCode)
    {

        if (!in_array('purchaseCard', $this->sendData['transaction'])) $this->sendData['transaction']['purchaseCard'] = array();

        $this->sendData['transaction']['purchaseCard']['destinationPostalCode'] = $destinationPostalCode;

        return $this;

    }

    public function purchaseCardProductDescriptors($productDescriptors)
    {

        if (!in_array('purchaseCard', $this->sendData['transaction'])) $this->sendData['transaction']['purchaseCard'] = array();

        $this->sendData['transaction']['purchaseCard']['productDescriptors'] = $productDescriptors;

        return $this;

    }


    // PRIVATE FUNCTIONS
    private function initializeSendData()
    {

        $now = new Carbon();

        $this->sendData = array(
            'dateTime' => $now->toAtomString(),
            'apiOptions' => array(
                "RETURNEXPDATE",
                "ALLOWPARTIALAUTH"
            )
        );

    }

}