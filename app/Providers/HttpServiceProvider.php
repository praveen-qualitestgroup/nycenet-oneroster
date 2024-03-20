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
    protected $accessToken = "";

    /**
     * Create a new service provider instance.
     * 
     * @param Client $client
     * 
     */
    public function __construct(Client $client)
    {
        $this->url = env("BASE_URL", null);
        $this->accessTokenURL = env("EDLINK_API_URL", null) . "v1/integrations";
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

    /**
     * Login and get the access token
     * @return void
     */
    public function generateNewToken() : void
    {
        
        $accessToken = '';
        $this->setAccessToken($accessToken);
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
        ini_set('max_execution_time', 0);
        $full_path = $this->url;
        $full_path .= $uri;
        $this->headers['authorization'] = 'Bearer ' . $this->accessToken;
        if ($full_path != "") {
            $request = $this->http->get($full_path, [
                'headers' => $this->headers,
                'timeout' => 3600,
                'connect_timeout' => true,
                'http_errors' => true,
            ]);
            $response = $request ? $request->getBody()->getContents() : null;
            $status = $request ? $request->getStatusCode() : 500;
            if ($response && $status === 200 && $response !== 'null') {
                $response = json_decode($response, true);
                if (array_key_exists('$next', $response)) {
                    return $response = array_merge($response['$data'], $this->getResponse(basename($response['$next'])));
                }
                return array_merge($response['$data']);
            }
        }
        return null;
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
