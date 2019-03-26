<?php

namespace Woo_Shift4_Payment_Gateway\Api\Tests;

if (!isset($_SESSION)) $_SESSION = array();

use Woo_Shift4_Payment_Gateway\Api\Shift4API;
use Woo_Shift4_Payment_Gateway\Api\Shift4Token;
use Woo_Shift4_Payment_Gateway\Api\Shift4Transaction;


class Shift4Test extends Shift4Transaction
{

    protected $apiUrl;
    protected $clientGUID;
    protected $authToken;
    protected $companyName;
    protected $interfaceName;


    public function __construct($accessToken = null, $clientUrl = null, $clientGuid = null, $clientAuthToken = null, $companyName = null, $interfaceName = null, $additionalHeaders = array())
    {
        $this->apiUrl = $clientUrl;
        $this->clientGUID = $clientGuid;
        $this->authToken = $clientAuthToken;
        $this->companyName = $companyName;
        $this->interfaceName = $interfaceName;

        parent::__construct($accessToken, $clientUrl, $clientGuid, $clientAuthToken, $companyName, $interfaceName, $additionalHeaders);

    }


    public function testTwoA()
    {


        $total = 111.45;
        $taxes = 11.14;
        $clerk = 24;
        $invoiceNumber = rand();
        $expirationDate = '12/23';
        $creditCard = '4321000000001119';
        $cvv = '333';


/*        $tokenizer = new Shift4Token($this->accessToken, $this->apiUrl, $this->clientGUID, $this->authToken, $this->companyName, $this->interfaceName);

        $tokenizer
            ->setClerk($clerk)
            ->setTax($taxes)
            ->setExpirationDate($expirationDate)
            ->setInvoceNumber($invoiceNumber)
            ->setCardNumber($creditCard)
            ->setTotal($total)
            ->setCvv($cvv)
            ->setName('Max')
            ->setLastName('Fedotov')
            ->setPostalCode('65000')
            ->setAddress('65 Main Street')
            ->post();*/


        $transaction = new Shift4Transaction($this->accessToken, $this->apiUrl, $this->clientGUID, $this->authToken, $this->companyName, $this->interfaceName);

        $transaction
            ->setTax($taxes)
            ->setTotal($total)
            ->setClerk($clerk)
            ->setInvoice($invoiceNumber)
            ->setCustomerReference('Max')

            ->setDestinationPostalCode('65000')
            ->setProductDescriptors(array('Product 1', 'Product 2', 'Product 3'))
            ->setCardNumber($creditCard)
            ->setCvv($cvv)
            ->setExpirationDate($expirationDate)
            ->setName('Max')
            ->setLastName('Fedotov')
            ->setPostalCode('65000')
            ->setAddress('65 Main Street')
            ->sale();

        $output = $transaction->getOutput();


        echo json_encode($output);


    }

    public function testThreeA()
    {


        $invoiceNumber = '1268317485'; // Invoice number of test two A


        $transaction = new Shift4Transaction($_SESSION['accessToken'], $this->apiUrl, $this->clientGUID, $this->authToken, $this->companyName, $this->interfaceName);

        $transaction
            ->setInvoice($invoiceNumber)
            ->deleteInvoice();

        $output = $transaction->getOutput();

        echo json_encode($output);

    }


    public function testFiveA()
    {

        $total = 111.61;
        $taxes = 0.00;
        $clerk = 5188;
        $invoiceNumber = rand();
        $expirationDate = '12/23';
        $creditCard = '4321000000001119';
        $cvv = '333';


        $tokenizer = new Shift4Token($this->accessToken, $this->apiUrl, $this->clientGUID, $this->authToken, $this->companyName, $this->interfaceName);

        $tokenizer
            ->setClerk($clerk)
            ->setTax($taxes)
            ->setExpirationDate($expirationDate)
            ->setInvoceNumber($invoiceNumber)
            ->setCardNumber($creditCard)
            ->setTotal($total)
            ->setCvv($cvv)
            ->setName('Max')
            ->setLastName('Fedotov')
            ->setPostalCode('65000')
            ->setAddress('65 Main Street')
            ->post();


        $transaction = new Shift4Transaction($this->accessToken, $this->apiUrl, $this->clientGUID, $this->authToken, $this->companyName, $this->interfaceName);

        $transaction
            ->setTax($taxes)
            ->setTotal($total)
            ->setInvoice($invoiceNumber)
            ->setCardToken($tokenizer->getCardToken())
            ->capture();

        $output = $transaction->getOutput();


        echo json_encode($output);


    }


    public function testFiveB()
    {


        $invoiceNumber = '0843259474';


        $transaction = new Shift4Transaction($_SESSION['accessToken'], $this->apiUrl, $this->clientGUID, $this->authToken, $this->companyName, $this->interfaceName);

        $transaction
            ->setInvoice($invoiceNumber)
            ->getInvoiceInfo();

        $output = $transaction->getOutput();

        echo json_encode($output);

    }

    public function testSixA()
    {


        $invoiceNumber = '1382537409';


        $transaction = new Shift4Transaction($this->accessToken, $this->apiUrl, $this->clientGUID, $this->authToken, $this->companyName, $this->interfaceName);

        $transaction->setInvoice($invoiceNumber)
            ->setTotal(100)
            ->refund();

        $output = $transaction->getOutput();

        echo json_encode($output);

    }

}