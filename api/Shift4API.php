<?php

namespace Woo_Shift4_Payment_Gateway\Api;

use Woo_Shift4_Payment_Gateway\Api\Shift4Transaction;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Carbon\Carbon;

class Shift4API
{
    /**
     * @var stirng
     */
    public $accessToken;


    /**
     * Guzzle wrapper
     * @var GuzzleHttp\Client
     */
    protected $client;

    /**
     * url if API call
     * @var string
     */
    protected $clientUrl;

    /**
     * GUID assigned from Shift4
     * @var string
     */
    protected $clientGuid;

    /**
     * url if API call
     * @var string
     */
    protected $clientAuthToken;

    protected $container;

    protected $history;

    protected $stack;

    protected $patternGuid = '/^(\{{0,1}([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}\}{0,1})$/';

    protected $patternUuid = '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89ab][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/';

    protected $patternOther = '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{16}$/';

    protected $sendData = array();

    protected $output;

    protected $uri;

    protected $callMethod = 'POST';

    protected $companyName;

    protected $interfaceName;

    protected $errorindicator = FALSE;

    protected $shouldLogin = TRUE;

    protected $error = '';

    public function __construct($accessToken = null, $clientUrl = null, $clientGuid = null, $clientAuthToken = null, $companyName = null, $interfaceName = null, $additionalHeaders = array())
    {


        // Set Client URL
        $this->clientUrl = $clientUrl;

        if (!$this->clientUrl || !$this->isValid('clientUrl', $this->clientUrl)) throw Shift4Transaction::noApiUrl();

        // Set Client GUID
        $this->clientGuid = $clientGuid;

        if (!$this->clientGuid || !$this->isValid('clientGuid', $this->clientGuid)) throw Shift4Transaction::noGuid();

        // Set Client Auth Token
        $this->clientAuthToken = $clientAuthToken;

        if (!$this->clientAuthToken || !$this->isValid('clientAuthToken', $this->clientAuthToken)) throw Shift4Transaction::noAuthToken();

        $this->companyName = $companyName;

        if (!$this->companyName) throw Shift4Transaction::noCompanyName();

        $this->interfaceName = $interfaceName;

        if (!$this->interfaceName) throw Shift4Transaction::noInterfaceName();

        // Get Access Token
        $this->accessToken = $accessToken ?: $this->login();

        // Set up Guzzle History
        $this->container = [];

        $this->history = Middleware::history($this->container);

        $this->stack = HandlerStack::create();

        // Add the history middleware to the handler stack.
        $this->stack->push($this->history);

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

            )
        ));

    }

    /**
     * @return mixed
     */
    public function getOutput()
    {

        return $this->output;

    }

    public function getAccessToken()
    {

        return $this->accessToken;

    }

    // Get Login token
    public function login()
    {

        $now = new Carbon();

        $config = array(
            'base_uri' => $this->clientUrl,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'InterfaceVersion' => '1.0',
                'InterfaceName' => $this->interfaceName,
                'CompanyName' => $this->companyName
            )
        );

        // Set up Guzzle client
        $client = new Client($config);

        $response = $client->request(
            'POST',
            $this->clientUrl . 'credentials/accesstoken',
            array(
                'json' => array(
                    'dateTime' => $now->toAtomString(),
                    'credential' => array(
                        'authToken' => $this->clientAuthToken,
                        'clientGuid' => $this->clientGuid,
                    )
                ),
                // 'debug' => TRUE
            )
        );


        $tokenObject = json_decode($response->getBody(), TRUE);

        return $tokenObject['result'][0]['credential']['accessToken'];

    }

    public function request()
    {

        return array(
            'body' => $this->getBody(),
            'sendData' => $this->sendData
        );

    }

    public function error()
    {

        return array(
            'hasError' => $this->errorindicator,
            'errorMessage' => $this->error,
            'dump' => array(
                'output' => $this->output,
                'container' => $this->container[0],
                'body' => $this->getBody(),
                'sendData' => $this->sendData,
                'uri' => $this->versionUri . $this->uri
            )
        );

    }

    // Private functions
    protected function setError($error)
    {

        $this->hasError = TRUE;

        $this->error = $error;

        return $this;

    }

    public function getBody()
    {

        $result = array();

        foreach ($this->container as $transaction) {

            $item = array();

            $item['method'] = $transaction['request']->getMethod();

            if ($transaction['response']) {
                //> 200, 200
                $item['status'] = 'success';

                $item['code'] = $transaction['response']->getStatusCode();

            } elseif ($transaction['error']) {

                $item['status'] = 'error';

            }

            $item['data'] = $transaction['options'];

            $result[] = $item;
        }

        return json_encode($result);

    }

    protected function send()
    {
        try {

            $response = $this->client->request(
                $this->callMethod,
                $this->clientUrl . $this->uri,
                array(
                    'json' => $this->sendData
                )
            );

            $this->output = \GuzzleHttp\json_decode($response->getBody(), TRUE);


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

    protected function isValid($type, $value)
    {

        switch ($type) {

            case 'clientUrl':

                $url = parse_url($value);

                return isset($url['scheme']) && isset($url['host']);

                break;

            case 'clientGuid':
            case 'clientAuthToken':

                return (
                    preg_match($this->patternGuid, $value) ? true : false
                    ) || (
                    preg_match($this->patternUuid, $value) ? true : false
                    ) || (
                    preg_match($this->patternOther, $value) ? true : false
                    );

                break;

            default:

                break;

        }

        return false;

    }

}