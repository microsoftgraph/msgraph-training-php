<?php
// Copyright (c) Microsoft Corporation. All rights reserved.
// Licensed under the MIT license.

// <UseSnippet>
use Microsoft\Graph\Graph;
use Microsoft\Graph\Http;
use Microsoft\Graph\Model;
use GuzzleHttp\Client;
// </UseSnippet>

class GraphHelper {
    // <AppOnlyAuthConfigSnippet>
    private static Client $tokenClient;
    private static string $appToken;
    private static string $clientId = '';
    private static string $clientSecret = '';
    private static string $tenantId = '';
    private static Graph $appClient;

    public static function initializeGraphForAppOnlyAuth(): void {
        GraphHelper::$tokenClient = new Client();
        GraphHelper::$clientId = $_ENV['CLIENT_ID'];
        GraphHelper::$clientSecret = $_ENV['CLIENT_SECRET'];
        GraphHelper::$tenantId = $_ENV['TENANT_ID'];
        GraphHelper::$appClient = new Graph();
    }
    // </AppOnlyAuthConfigSnippet>

    // <GetAppOnlyTokenSnippet>
    public static function getAppOnlyToken(): string {
        // If we already have a token, just return it
        // Tokens are valid for one hour, after that a new token needs to be
        // requested
        if (isset(GraphHelper::$appToken)) {
            return GraphHelper::$appToken;
        }

        // https://docs.microsoft.com/azure/active-directory/develop/v2-oauth2-client-creds-grant-flow
        $tokenRequestUrl = 'https://login.microsoftonline.com/'.GraphHelper::$tenantId.'/oauth2/v2.0/token';

        // POST to the /token endpoint
        $tokenResponse = GraphHelper::$tokenClient->post($tokenRequestUrl, [
            'form_params' => [
                'client_id' => GraphHelper::$clientId,
                'client_secret' => GraphHelper::$clientSecret,
                'grant_type' => 'client_credentials',
                'scope' => 'https://graph.microsoft.com/.default'
            ],
            // These options are needed to enable getting
            // the response body from a 4xx response
            'http_errors' => false,
            'curl' => [
                CURLOPT_FAILONERROR => false
            ]
        ]);

        $responseBody = json_decode($tokenResponse->getBody()->getContents());
        if ($tokenResponse->getStatusCode() == 200) {
            // Return the access token
            GraphHelper::$appToken = $responseBody->access_token;
            return $responseBody->access_token;
        } else {
            $error = isset($responseBody->error) ? $responseBody->error : $tokenResponse->getStatusCode();
            throw new Exception('Token endpoint returned '.$error, 100);
        }
    }
    // </GetAppOnlyTokenSnippet>

    // <GetUsersSnippet>
    public static function getUsers(): Http\GraphCollectionRequest {
        $token = GraphHelper::getAppOnlyToken();
        GraphHelper::$appClient->setAccessToken($token);

        // Only request specific properties
        $select = '$select=displayName,id,mail';
        // Sort by display name
        $orderBy = '$orderBy=displayName';

        $requestUrl = '/users?'.$select.'&'.$orderBy;
        return GraphHelper::$appClient->createCollectionRequest('GET', $requestUrl)
                                      ->setReturnType(Model\User::class)
                                      ->setPageSize(25);
    }
    // </GetUsersSnippet>

    // <MakeGraphCallSnippet>
    public static function makeGraphCall(): void {
        $token = GraphHelper::getAppOnlyToken();
        GraphHelper::$appClient->setAccessToken($token);
        // INSERT YOUR CODE HERE
    }
    // </MakeGraphCallSnippet>
}
?>
