<?php

namespace Woo_Shift4_Payment_Gateway\Api;

use GuzzleHttp\Client;
use Carbon\Carbon;
use Woo_Shift4_Payment_Gateway\Api\Shift4API;

class Shift4Token extends Shift4API
{

    public function __construct($accessToken = null, $clientUrl = null, $clientGuid = null, $clientAuthToken = null, $companyName = null, $interfaceName = null, $additionalHeaders = array())
    {

        // Stop the authorization process, since we're using i4go
        // $this->shouldLogin = FALSE;

        parent::__construct($accessToken, $clientUrl, $clientGuid, $clientAuthToken, $companyName, $interfaceName, $additionalHeaders);

        $this->clientUrl = $clientUrl;
        $this->versionUri = '';
        $this->callMethod = 'POST';
        $this->uri = 'transactions/sale';

        if (!$this->clientUrl || !$this->isValid('clientUrl', $this->clientUrl)) throw Shift4Exception::noApiUrl();

        $this->client = new Client(array(
            'base_uri' => $this->clientUrl,
            'handler' => $this->stack,
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
            )
        ));

        $this->sendData['i4go_metatoken'] = 'IL';

    }

    public function post()
    {

        $this->authorizeClient();

        $this->sendData['fuseaction'] = 'api.jsonPostCardEntry';

        unset($this->sendData['i4go_clientip'], $this->sendData['i4go_metatoken'], $this->sendData['i4go_server'], $this->sendData['i4go_accesstoken']);

        $this->send();

    }

    public function ip($ip)
    {

        $this->sendData['i4go_clientip'] = $ip;

        return $this;

    }

    public function cardType($cardtype)
    {

        $this->sendData['i4go_cardtype'] = $cardtype;

        return $this;

    }

    /**
     * Use the i4go_cardnumber parameter to post the payment card number, as entered by the end user, to i4Go.
     * @param $cardnumber
     * @return $this
     */
    public function cardNumber($cardnumber)
    {

        $this->sendData['i4go_cardnumber'] = $cardnumber;

        return $this;

    }

    /**
     * Use the i4go_expirationmonth parameter to post the expiration month of the payment card, as entered by the end user, to i4Go. Choose between the following formats; for example, April would be 4 or 04.
     * @param $expirationmonth
     * @return $this
     */
    public function expirationMonth($expirationmonth)
    {

        $this->sendData['i4go_expirationmonth'] = $expirationmonth;

        return $this;

    }

    /**
     * Use the i4go_expirationyear parameter to post the expiration year of the payment card, as entered by the end user, to i4Go. Choose between the following formats; for example, the year would be 17 or 2017.
     * @param $expirationyear
     * @return $this
     */
    public function expirationYear($expirationyear)
    {

        $this->sendData['i4go_expirationyear'] = $expirationyear;

        return $this;

    }

    public function cvv($cvv2code)
    {

        $this->sendData['i4go_cvv2code'] = $cvv2code;
        $this->sendData['i4go_cvv2indicator '] = 1;

        return $this;

    }

    public function name($name)
    {

        $this->sendData['i4go_cardholdername'] = $name;

        return $this;

    }

    public function zip($postalCode)
    {

        $this->sendData['i4go_postalcode'] = $postalCode;

        return $this;

    }

    public function address($addressLine1)
    {

        $this->sendData['i4go_streetaddress'] = $addressLine1;

        return $this;

    }

    public function getToken()
    {

        if (!array_key_exists('i4go_uniqueid', $this->output)) return false;

        return $this->output['i4go_uniqueid'] ?: false;

    }

    /**
     *
     * The application will need to modify the payment information form to include the access block (which includes the merchant’s Access Token).
     * @return $this
     * @throws Shift4Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function authorizeClient()
    {

        // Parse sendData
        $tempSendData = $this->sendData;

        $this->sendData = array(
            'fuseaction' => 'account.authorizeClient',
            'i4go_accesstoken' => $this->accessToken,
            'i4go_clientip' => $this->sendData['i4go_clientip'],
            'i4go_metatoken' => 'IL',
        );

        try {

            $client = new Client(array(
                'base_uri' => $this->clientUrl,
                'handler' => $this->stack,
                'headers' => array(
                    'Content-Type' => 'application/x-www-form-urlencoded',
                )
            ));

            $response = $client->request(
                $this->callMethod,
                $this->versionUri . $this->uri,
                array(
                    'form_params' => $this->sendData
                )
            );

            $this->output = json_decode($response->getBody(), TRUE);

        } catch (GuzzleHttp\Exception\ClientException $e) {

            throw Shift4Exception::guzzleError($e->getMessage(), $this->getBody(), $this->sendData, $this->versionUri . $this->uri);

        } catch (\Exception $e) {

            throw Shift4Exception::guzzleError($e->getMessage(), $this->getBody(), $this->sendData, $this->versionUri . $this->uri);

        }

        if ($this->output['i4go_responsecode'] && $this->output['i4go_responsecode'] != 1) {
            throw Shift4Exception::guzzleError($this->output, $this->getBody(), $this->sendData, $this->versionUri . $this->uri);
        }

        $this->sendData = $tempSendData;
        $this->sendData['i4go_accessblock'] = $this->output['i4go_accessblock'];
        $this->sendData['i4go_server'] = $this->output['i4go_server'];
        $this->sendData['i4go_accesstoken'] = $this->accessToken;
        $this->clientUrl = $this->output['i4go_server'];

        return $this;

    }

    protected function send()
    {

        $this->client = new Client(array(
            'base_uri' => $this->clientUrl,
            'handler' => $this->stack,
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
            )
        ));

        try {

            $response = $this->client->request(
                'POST',
                $this->uri,
                array(
                    'form_params' => $this->sendData
                )
            );

            $this->output = json_decode($response->getBody(), TRUE);

            return $this;

        } catch (GuzzleHttp\Exception\ClientException $e) {

            throw Shift4Exception::guzzleError($e->getMessage(), $this->getBody(), $this->sendData, $this->versionUri . $this->uri);

        } catch (\Exception $e) {

            throw Shift4Exception::guzzleError($e->getMessage(), $this->getBody(), $this->sendData, $this->versionUri . $this->uri);

        }

    }

}