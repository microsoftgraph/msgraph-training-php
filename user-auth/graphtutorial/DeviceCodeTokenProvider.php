<?php
// Copyright (c) Microsoft Corporation. All rights reserved.
// Licensed under the MIT license.

use GuzzleHttp\Client;
use Http\Promise\FulfilledPromise;
use Http\Promise\Promise;
use Http\Promise\RejectedPromise;
use Microsoft\Kiota\Abstractions\Authentication\AccessTokenProvider;
use Microsoft\Kiota\Abstractions\Authentication\AllowedHostsValidator;

class DeviceCodeTokenProvider implements AccessTokenProvider {

    private string $clientId;
    private string $tenantId;
    private string $scopes;
    private AllowedHostsValidator $allowedHostsValidator;
    private string $accessToken;
    private Client $tokenClient;

    public function __construct(string $clientId, string $tenantId, string $scopes) {
        $this->clientId = $clientId;
        $this->tenantId = $tenantId;
        $this->scopes = $scopes;
        $this->allowedHostsValidator = new AllowedHostsValidator();
        $this->allowedHostsValidator->setAllowedHosts([
            "graph.microsoft.com",
            "graph.microsoft.us",
            "dod-graph.microsoft.us",
            "graph.microsoft.de",
            "microsoftgraph.chinacloudapi.cn"
        ]);
        $this->tokenClient = new Client();
    }

    public function getAuthorizationTokenAsync(string $url, array $additionalAuthenticationContext = []): Promise {
        $parsedUrl = parse_url($url);
        $scheme = $parsedUrl["scheme"] ?? null;

        if ($scheme !== 'https' || !$this->getAllowedHostsValidator()->isUrlHostValid($url)) {
            return new FulfilledPromise(null);
        }

        // If we already have a user token, just return it
        // Tokens are valid for one hour, after that it needs to be refreshed
        if (isset($this->accessToken)) {
            return new FulfilledPromise($this->accessToken);
        }

        // https://learn.microsoft.com/azure/active-directory/develop/v2-oauth2-device-code
        $deviceCodeRequestUrl = 'https://login.microsoftonline.com/'.$this->tenantId.'/oauth2/v2.0/devicecode';
        $tokenRequestUrl = 'https://login.microsoftonline.com/'.$this->tenantId.'/oauth2/v2.0/token';

        // First POST to /devicecode
        $deviceCodeResponse = json_decode($this->tokenClient->post($deviceCodeRequestUrl, [
            'form_params' => [
                'client_id' => $this->clientId,
                'scope' => $this->scopes
            ]
        ])->getBody()->getContents());

        // Display the user prompt
        print($deviceCodeResponse->message.PHP_EOL);

        // Response also indicates how often to poll for completion
        // And gives a device code to send in the polling requests
        $interval = (int)$deviceCodeResponse->interval;
        $device_code = $deviceCodeResponse->device_code;

        // Do polling - if attempt times out the token endpoint
        // returns an error
        while (true) {
            sleep($interval);

            // POST to the /token endpoint
            $tokenResponse = $this->tokenClient->post($tokenRequestUrl, [
                'form_params' => [
                    'client_id' => $this->clientId,
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:device_code',
                    'device_code' => $device_code
                ],
                // These options are needed to enable getting
                // the response body from a 4xx response
                'http_errors' => false,
                'curl' => [
                    CURLOPT_FAILONERROR => false
                ]
            ]);

            if ($tokenResponse->getStatusCode() == 200) {
                // Return the access_token
                $responseBody = json_decode($tokenResponse->getBody()->getContents());
                $this->accessToken = $responseBody->access_token;
                return new FulfilledPromise($responseBody->access_token);
            } else if ($tokenResponse->getStatusCode() == 400) {
                // Check the error in the response body
                $responseBody = json_decode($tokenResponse->getBody()->getContents());
                if (isset($responseBody->error)) {
                    $error = $responseBody->error;
                    // authorization_pending means we should keep polling
                    if (strcmp($error, 'authorization_pending') != 0) {
                        return new RejectedPromise(
                            new Exception('Token endpoint returned '.$error, 100));
                    }
                }
            }
        }
    }

    public function getAllowedHostsValidator(): AllowedHostsValidator {
        return $this->allowedHostsValidator;
    }
}
?>
