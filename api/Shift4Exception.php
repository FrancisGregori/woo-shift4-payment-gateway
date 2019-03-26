<?php

namespace Woo_Shift4_Payment_Gateway\Api;


class Shift4Exception extends \Exception
{

    public static function noApiUrl()
    {

        return new self("An API URL is required.");

    }

    public static function noGuid()
    {

        return new self("A client GUID is required.");

    }

    public static function noAuthToken()
    {

        return new self("An Auth Token is required.");

    }

    public static function noCompanyName()
    {

        return new self("A Company Name is required.");

    }

    public static function noInterfaceName()
    {

        return new self("An Interface Name is required.");

    }

    public static function guzzleError($error, $request, $body, $url)
    {

        return new self(
            'Guzzle error: ' . ($error) . PHP_EOL .
            'Request: ' . ($request) . PHP_EOL .
            'URL: ' . $url . PHP_EOL .
            'Send Data: ' . json_encode($body)
        );

    }

    public static function wooError($error, $request, $body, $url)
    {
        $message = json_decode($error);


        return new self($message['response']['result']['primaryCode']);

    }

}