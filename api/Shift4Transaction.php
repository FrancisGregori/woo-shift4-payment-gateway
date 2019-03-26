<?php

namespace Woo_Shift4_Payment_Gateway\Api;

use GuzzleHttp\Client;
use Carbon\Carbon;

class Shift4Transaction extends Shift4API
{

    /**
     * The amount of sales tax charged for a transaction.
     * The tax amount is used by businesses to track tax expenses for accounting purposes.
     * Identifying the tax amount also helps consumers understand the total amount that they were billed.
     * This field is part of Level 2 card data.
     *
     * @var float
     */
    protected $tax;


    /**
     * The amount being charged for a particular transaction.
     * If other amount fields are sent, they must be included in the total amount.
     *
     * @var float
     */
    protected $total;

    /**
     * A free-form notes field that supports the use of HTML tags.
     * This can be used for reference in Lighthouse Transaction Manager and is not sent to the authorization host.
     *
     * @var string
     */
    protected $note;

    /**
     * 10-digit invoice number assigned by the interface to identify a transaction.
     * An invoice number serves as a unique key that identifies a transaction within a batch in Shift4 Payments' Gateway.
     *
     * @var string
     */
    protected $invoice;

    /**
     * This field is used to specify a card token.
     * Whenever CHD is sent in a request, a card token will be returned in this field.
     * Your interface should be designed to store this card token for future use.
     * The latest card token received should be used in any subsequent request that references the same card data.
     *
     * @var string
     */
    protected $cardToken;


    /**
     * Card expiration date in MMYY format.
     * This value should only be populated in the initial sale/authorization request.
     *
     * @var integer
     */
    protected $expirationDate;

    /**
     * The payment card number entered in an initial authorization/sale request.
     * This field will always be masked when returned in a response.
     *
     * @var string
     */
    protected $cardNumber;

    /**
     * The three- or four-digit Card Security Code found on a payment card.
     * This value should only be sent in an initial sale/authorization request.
     * It should not be stored by the interface.
     * When sending card.securityCode.value, card.securityCode.indicator must also be sent.
     *
     * @var string
     */
    protected $cvv;

    /**
     * Enum:"CC" "DB" "GC" "HF" "PL" "YC"
     * CC - When using a UTG-controlled device, this value will force a card to process as credit.
     *
     * An abbreviation used to specify the type of card that will be used when processing a transaction.
     * If an interface sends a value that is not listed here, that value will be ignored.
     *
     * @var string
     */
    protected $cardType;

    /**
     * Specifies a consumer’s first name.
     *
     * @var string
     */
    protected $name;

    /**
     * Specifies a consumer’s last name.
     *
     * @var string
     */
    protected $lastName;

    /**
     * Cardholder’s ZIP/postal code from their billing statement.
     * This field is used in AVS. Do not include special characters.
     *
     * @var string
     */
    protected $postalCode;

    /**
     * Cardholder’s street address exactly as it appears on their billing statement.
     * This field is used in AVS.
     *
     * @var string
     */
    protected $address;

    /**
     * A text description of the items purchased or services sold.
     * This can be a generic text description of what the merchant sells (such as “Groceries”) or
     * specific transactio data (such as the name of the item sold).
     * At least one product descriptor field is required in a sale or authorization request.
     *
     * @var string
     */
    protected $transactionProducts;

    /**
     * A unique value used to identify the consumer or transaction.
     * If a merchant has a significant amount of revenue from purchasing card customers,
     * the interface would use this field to collect the consumer’s purchase order or employee identification number.
     * In lodging transactions, this may be unique transaction details,
     * such as a reservation code or third-party booking source.
     * This field is part of Level 2 card data.
     *
     * @var string
     */
    protected $customerReference;

    /**
     * When items are shipped, the ZIP/postal code to which merchandise will be shipped;
     * otherwise, the ZIP/postal code where the goods or services are rendered.
     * This field is part of Level 2 card data.
     *
     * @var string
     *
     */
    protected $destinationPostalCode;

    /**
     * A text description of the items purchased or services sold.
     * This can be a generic text description of what the merchant sells (such as “Groceries”) or specific transaction data (such as the name of the item sold).
     * At least one product descriptor field is required in a sale or authorization request.
     *
     * @var array
     */
    protected $productDescriptors;


    /**
     * @var number
     */
    protected $clerk;

    /**
     * @var array
     */
    protected $sendData = array();


    public function __construct($accessToken = null, $clientUrl = null, $clientGuid = null, $clientAuthToken = null, $companyName = null, $interfaceName = null, $additionalHeaders = array())
    {

        parent::__construct($accessToken, $clientUrl, $clientGuid, $clientAuthToken, $companyName, $interfaceName, $additionalHeaders);

        $this->initializeSendData();


        $this->clientUrl = $clientUrl;

        if (!$this->clientUrl || !$this->isValid('clientUrl', $this->clientUrl)) throw Shift4Exception::noApiUrl();

        $this->client = new Client(array(
            'base_uri' => $this->clientUrl,
            'handler' => $this->stack,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'InterfaceVersion' => '1.0',
                'InterfaceName' => $this->interfaceName,
                'CompanyName' => $this->companyName,
                'AccessToken' => $accessToken
            )
        ));


        $now = new Carbon();

        $this->sendData['dateTime'] = $now->toAtomString();

        /**
         * The method used to capture a payment card in an authorization/sale request.
         *
         * M - Manual entry
         */
        $this->sendData['card']['entryMode'] = 'M';

        /**
         * Indicates whether a card was present (‘Y’) or not (‘N’) at the time a transaction took place.
         * This should be set appropriately in the initial authorization/sale request.
         * In subsequent requests, this field should be left blank or should not be sent.
         */
        $this->sendData['card']['present'] = 'N';

    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->sendData['customer']['firstName'] = $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param $lastName
     * @return $this
     */
    public function setLastName($lastName)
    {
        $this->sendData['customer']['lastName'] = $this->lastName = $lastName;
        return $this;
    }

    /**
     * @return string
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * @param $postalCode
     * @return $this
     */
    public function setPostalCode($postalCode)
    {
        /**
         * Remove all non-numeric characters
         */
        $formattedPostCode = (int)preg_replace('/[^0-9]/', '', $postalCode);

        $this->sendData['customer']['postalCode'] = $this->sendData['transaction']['purchaseCard']['destinationPostalCode'] = $this->postalCode = $formattedPostCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param $address
     * @return $this
     */
    public function setAddress($address)
    {
        $this->sendData['customer']['addressLine1'] = $this->address = $address;
        return $this;
    }

    /**
     * @return string
     */
    public function getTransactionProducts()
    {
        return $this->transactionProducts;
    }

    /**
     * @param $transactionProducts
     * @return $this
     */
    public function setTransactionProducts($transactionProducts)
    {
        $this->sendData['transaction']['purchaseCard']['productDescriptors'] = $this->transactionProducts = $transactionProducts;
        return $this;
    }

    /**
     * @return string
     */
    public function getCustomerReference()
    {
        return $this->customerReference;
    }

    /**
     * @param $customerReference
     * @return $this
     */
    public function setCustomerReference($customerReference)
    {
        $this->sendData['transaction']['purchaseCard']['customerReference'] = $this->customerReference = $customerReference;
        return $this;
    }

    /**
     * @return string
     */
    public function getDestinationPostalCode()
    {
        return $this->destinationPostalCode;
    }

    /**
     * @param $destinationPostalCode
     * @return $this
     */
    public function setDestinationPostalCode($destinationPostalCode)
    {
        $this->sendData['transaction']['purchaseCard']['destinationPostalCode'] = $this->destinationPostalCode = $destinationPostalCode;
        return $this;
    }

    /**
     * @return array
     */
    public function getProductDescriptors()
    {
        return $this->productDescriptors;
    }

    /**
     * @param $productDescriptors
     * @return $this
     */
    public function setProductDescriptors($productDescriptors)
    {
        $this->sendData['transaction']['purchaseCard']['productDescriptors'] = $this->productDescriptors = $productDescriptors;
        return $this;
    }

    /**
     * @return number
     */
    public function getClerk()
    {
        return $this->clerk;
    }

    /**
     * @param $clerk
     * @return $this
     */
    public function setClerk($clerk)
    {
        $this->sendData['clerk']['numericId'] = $this->clerk = $clerk;
        return $this;
    }

    // Getters and Setters


    /**
     * @return int
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }

    /**
     * @param $expirationDate
     * @return $this
     */
    public function setExpirationDate($expirationDate)
    {

        /**
         * Remove all non-numeric characters
         */
        $formattedDate = (int)preg_replace('/[^0-9]/', '', $expirationDate);

        $this->sendData['card']['expirationDate'] = $this->expirationDate = $formattedDate;
        return $this;
    }


    /**
     * @return string
     */
    public function getCardNumber()
    {
        return $this->cardNumber;
    }

    /**
     * @param $cardNumber
     * @return $this
     */
    public function setCardNumber($cardNumber)
    {
        /**
         * Remove all non-numeric characters
         */
        $formattedCardNumber = (int)preg_replace('/[^0-9]/', '', $cardNumber);

        $this->sendData['card']['number'] = $this->cardNumber = $formattedCardNumber;
        return $this;
    }

    /**
     * @return string
     */
    public function getCvv()
    {
        return $this->cvv;
    }

    /**
     * @param $cvv
     * @return $this
     */
    public function setCvv($cvv)
    {
        /**
         * This field indicates the presence of a CSC.
         *
         * 1 - CSC provided
         */
        $this->sendData['card']['securityCode']['indicator'] = '1';

        $this->sendData['card']['securityCode']['value'] = $this->cvv = (string)$cvv;
        return $this;
    }

    /**
     * @return string
     */
    public function getCardType()
    {
        return $this->cardType;
    }

    /**
     * @param $cardType
     * @return $this
     */
    public function setCardType($cardType)
    {
        $this->sendData['card']['type'] = $this->cardType = $cardType ?: 'CC';
        return $this;
    }

    /**
     * @return float
     */
    public function getTax()
    {
        return $this->tax;
    }

    /**
     * @param $tax
     * @return $this
     */
    public function setTax($tax)
    {
        $this->sendData['amount']['tax'] = $this->tax = $tax;
        return $this;
    }

    /**
     * @return float
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @param $total
     * @return $this
     */
    public function setTotal($total)
    {
        $this->sendData['amount']['total'] = $this->total = $total;
        return $this;
    }


    /**
     * @return string
     */
    public function getInvoice()
    {
        return $this->invoice;
    }

    /**
     * @param $invoice
     * @return $this
     */
    public function setInvoice($invoice)
    {
        $this->sendData['transaction']['invoice'] = $this->invoice = $invoice;
        return $this;
    }

    /**
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * @param $note
     * @return $this
     */
    public function setNote($note)
    {
        $this->sendData['transaction']['notes'] = $this->note = $note;
        return $this;
    }

    /**
     * @return string
     */
    public function getCardToken()
    {
        return $this->cardToken;
    }

    /**
     * @param $cardToken
     * @return $this
     */
    public function setCardToken($cardToken)
    {
        $this->sendData['card'] = array();

        $this->sendData['card']['token']['value'] = $this->cardToken = $cardToken;
        return $this;
    }

    // End of Getters and Setters


    // Methods

    /**
     * Used to request processor approval for an authorization.
     * If the invoice number already exists, the amount requested will be compared to the approved amount on file,
     * and Shift4 Payments will request approval for the additional amount only.
     * If approved, an updated transaction will be displayed in Lighthouse Transaction Manager.
     *
     * @throws Shift4Exception
     */
    public function authorization()
    {

        $this->callMethod = 'POST';
        $this->uri = 'transactions/authorization';
        $this->send();

    }


    /**
     * This function is used to request card validation by going online to verify the card information with the processor.
     * If Address Verification System (AVS) and/or Card Security Code (CSC) data are sent in the request, that information will also be validated without consuming short-term data.
     */
    public function verifyCSC()
    {

        unset($this->sendData['transaction']);
        unset($this->sendData['card']['present']);
        $this->sendData['apiOptions'] = array("RETURNEXPDATE");
        $this->callMethod = 'POST';
        $this->uri = 'cards/verify';
        $this->send();

    }

    /**
     * Used to close out an existing authorization request.
     * If the invoice number already exists, the amount requested will be compared to the approved amount on file,
     * and Shift4 Payments will request approval for the additional amount only.
     * If approved, the authorization will be converted to a sale transaction and be ready to be batched.
     *
     * @throws Shift4Exception
     */
    public function capture()
    {

        $this->callMethod = 'POST';
        $this->uri = 'transactions/capture';
        $this->send();

    }

    /**
     * Used to request processor approval for an authorization and capture in one request.
     * If the invoice number already exists, the amount requested will be compared to the approved amount on file,
     * and Shift4 Payments will request approval for the additional amount only.
     * If approved, a new sale transaction will be displayed in Lighthouse Transaction Manager and be ready to be batched.
     *
     * @throws Shift4Exception
     */
    public function sale()
    {

        $this->callMethod = 'POST';
        $this->uri = 'transactions/sale';
        $this->send();
    }


    /**
     * This function can be used to refund a transaction for either the full or a partial amount.
     *
     * @throws Shift4Exception
     */
    public function refund()
    {

        $this->callMethod = 'POST';
        $this->uri = 'transactions/refund';

        $this->send();

    }

    /**
     * This function can be used to delete(void) a transaction.
     *
     * @throws Shift4Exception
     */
    public function deleteInvoice()
    {

        // Set up Guzzle client
        $this->client = new Client(array(
            'base_uri' => $this->clientUrl,
            'handler' => $this->stack,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'InterfaceVersion' => '1.0',
                'InterfaceName' => $this->interfaceName,
                'CompanyName' => $this->companyName,
                'AccessToken' => $this->accessToken,
                'Invoice' => $this->getInvoice()
            )
        ));

        $this->callMethod = 'DELETE';
        $this->uri = 'transactions/invoice';
        $this->sendData = array();

        $this->send();

    }

    /**
     * This function can be used to get the invoice data.
     *
     * @return $this
     * @throws Shift4Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getInvoiceInfo()
    {
        try {
            // Set up Guzzle client
            $this->client = new Client(array(
                'base_uri' => $this->clientUrl,
                'handler' => $this->stack,
                'headers' => array(
                    'InterfaceVersion' => '1.0',
                    'InterfaceName' => $this->interfaceName,
                    'CompanyName' => $this->companyName,
                    'AccessToken' => $this->accessToken,
                    'Invoice' => $this->getInvoice()
                )
            ));


            $this->callMethod = 'GET';
            $this->uri = 'transactions/invoice';

            $response = $this->client->request(
                $this->callMethod,
                $this->clientUrl . $this->uri
            );


            $this->output = json_decode($response->getBody(), TRUE);

            return $this;

        } catch (ServerException $e) {

            // $e->getResponse()->getBody()->getContents()

            throw Shift4Exception::guzzleError($e->getMessage(), $this->getBody(), $this->sendData, $this->clientUrl . $this->uri);

        } catch (BadResponseException $e) {

            throw Shift4Exception::guzzleError($e->getMessage(), $this->getBody(), $this->sendData, $this->clientUrl . $this->uri);

        } catch (RequestException $e) {

            throw Shift4Exception::guzzleError($e->getMessage(), $this->getBody(), $this->sendData, $this->clientUrl . $this->uri);

        } catch (ClientException $e) {

            throw Shift4Exception::guzzleError($e->getMessage(), $this->getBody(), $this->sendData, $this->clientUrl . $this->uri);

        } catch (Exception $e) {

            throw Shift4Exception::guzzleError($e->getMessage(), $this->getBody(), $this->sendData, $this->clientUrl . $this->uri);

        }

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