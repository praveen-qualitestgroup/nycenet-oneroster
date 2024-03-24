<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class HttpServiceProvider extends ServiceProvider
{
    private static $secretKey = '795ce489124cdbb8d91ab7be2c28bb264af71ddd3e516c16f1060c20111d97a4';
    private static $secretIv = '2FsjXrecPXmKPIx9fAsw2oR6l3YfDhxS';
    private static $encryptMethod = "AES-256-CBC";
    protected $url;
    protected $http;
    protected $headers;
    protected $accessTokenURL;
    protected $apiUrl;
    protected $accessToken = "";
    
    CONST DEFAULT_ERROR_MESSAGE = 'We ran into an error';
    CONST BAD_URL = 'The request URL is not correct';
    /**
     * Create a new service provider instance.
     * 
     * @param Client $client
     * 
     */
    public function __construct(Client $client)
    {
        $this->url = env("BASE_URL");
        $this->accessTokenURL = $this->url . env("TOKEN_URL");
        $this->apiUrl = $this->url . env('API_URL');
        $this->http = $client;
        $this->headers = [
            'cache-control' => 'no-cache',
            'content-type' => 'application/x-www-form-urlencoded',
        ];
    }

    /**
     * Set the access token
     * 
     * @param string @accessToken
     * 
     * @return void
     */
    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }
    
    public function getAccessToken() : string
    {
        return $this->accessToken;
    }

    /**
     * Login and get the access token
     * @return void
     */
    public function generateNewToken() : void
    {
        //authentication parameters
        $formParams = [
            'client_id' => env('CLIENT_ID'),
            'client_secret' => env('CLIENT_SECRET'),
            'grant_type' => 'client_credentials',
            'scope' => env('SCOPE') 
        ];
        
        $request = $this->http->post($this->accessTokenURL,[
            'headers' => $this->headers,
            'form_params' => $formParams,
            'connect_timeout' => true,
            'verify' => false, //Due to SSL issue
            'timeout' => 3600,
            'http_errors' => true,
        ],);
        
        $response = $request->getBody()->getContents();
        $status = $request ? $request->getStatusCode() : 500;
        if($response && $status === 200 && $response !== null){
            $data = json_decode($response,true);
            $this->accessToken = $data['access_token'];
        }
        $this->setAccessToken($this->accessToken);
    }
    
    /**
     * Get integrations from Edlink API
     * 
     * @param string $applicationAccessToken
     * 
     * @return array
     */
    public function getIntegrations(string $applicationAccessToken): array
    {

        $this->headers = [
            'authorization' => 'Bearer ' . $applicationAccessToken,
            'cache-control' => 'no-cache',
            'content-type' => 'application/x-www-form-urlencoded',
        ];
        $request = $this->http->get($this->accessTokenURL, [
            'headers' => $this->headers,
            'connect_timeout' => true,
            'timeout' => 3600,
            'http_errors' => true,
        ]);

        $response = $request ? $request->getBody()->getContents() : null;
        $status = $request ? $request->getStatusCode() : 500;
        if ($response && $status === 200 && $response !== 'null') {
            $data = json_decode($response, true);
            $this->accessToken = $data['$data'][0]['access_token'];
        }
        return $data;
    }

    /**
     * Get response from Edlink API
     * 
     * @param string $uri
     * 
     * @return array|null
     */
    public function getResponse(string $uri = null): array|null
    {
        $response = [
            'success' =>false,
            'message' => self::DEFAULT_ERROR_MESSAGE, 
            'status' => 500,
            'data' => []
        ];
        
        $fullPath = $this->apiUrl. $uri;
        $this->headers['authorization'] = 'Bearer ' . $this->getAccessToken();
        $this->headers['serviceAccountID'] = env('SERVICE_ACCOUNT');
        
        if($fullPath != "" && filter_var($fullPath, FILTER_VALIDATE_URL)) {
            try {
                $request = $this->http->get($fullPath, [
                    'headers' => $this->headers,
                    'timeout' => 3600,
                    'connect_timeout' => true,
                    'verify' => false,
                    'http_errors' => true,
                ]);
                $data = $request ? $request->getBody()->getContents() : null;
                $response['status'] = $request ? $request->getStatusCode() : 500;
                if ($data && $response['status'] === 200 && $data !== 'null') {
                    $response['success'] = true;
                    $response['data'] = json_decode($data, true);
                    $response['message'] = array_keys($response['data'])[0];
                    return $response;
                }
            } catch (Exception $ex) {
                return $response = [
                    'success' =>false,
                    'message' => $ex->getMessage(), 
                    'status' => $response['status'],
                    'data' => []
                ];
            }
        }else{
           $response = ['success' =>false, 'message' => self::BAD_URL,'status' => 500,'data' => []]; 
        }
        return $response;
    }
    /**
     * Encrypt  token
     * 
     * @param string $data
     * 
     * @return string
     */
    public static function tokenencrypt(string $data): string
    {
        $key = hash('sha256', self::$secretKey);
        $iv = substr(hash('sha256', self::$secretIv), 0, 16);
        $result = openssl_encrypt($data, self::$encryptMethod, $key, 0, $iv);
        return $result = base64_encode($result);
    }

    /**
     * Decrypt token
     * 
     * @param string $data
     * 
     * @return string
     */
    public static function tokendecrypt(string $data): string
    {
        // Log::info("data:" . $data);
        $key = hash('sha256', self::$secretKey);
        $iv = substr(hash('sha256', self::$secretIv), 0, 16);
        $result = openssl_decrypt(base64_decode($data), self::$encryptMethod, $key, 0, $iv);
        return $result;
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
