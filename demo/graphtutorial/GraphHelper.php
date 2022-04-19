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
    // <UserAuthConfigSnippet>
    private static $tokenClient;
    private static $clientId = '';
    private static $authTenant = '';
    private static $graphUserScopes = '';
    private static $userClient;
    private static $userToken;

    public static function initializeGraphForUserAuth() {
        GraphHelper::$tokenClient = new Client();
        GraphHelper::$clientId = $_ENV['CLIENT_ID'];
        GraphHelper::$authTenant = $_ENV['AUTH_TENANT'];
        GraphHelper::$graphUserScopes = $_ENV['GRAPH_USER_SCOPES'];
        GraphHelper::$userClient = new Graph();
    }
    // </UserAuthConfigSnippet>

    // <GetUserTokenSnippet>
    public static function getUserToken(): string {
        // If we already have a user token, just return it
        // Tokens are valid for one hour, after that it needs to be refreshed
        if (isset(GraphHelper::$userToken)) {
            return GraphHelper::$userToken;
        }

        // https://docs.microsoft.com/azure/active-directory/develop/v2-oauth2-device-code
        $deviceCodeRequestUrl = 'https://login.microsoftonline.com/'.GraphHelper::$authTenant.'/oauth2/v2.0/devicecode';
        $tokenRequestUrl = 'https://login.microsoftonline.com/'.GraphHelper::$authTenant.'/oauth2/v2.0/token';

        // First POST to /devicecode
        $deviceCodeResponse = json_decode(GraphHelper::$tokenClient->post($deviceCodeRequestUrl, [
            'form_params' => [
                'client_id' => GraphHelper::$clientId,
                'scope' => GraphHelper::$graphUserScopes
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
            $tokenResponse = GraphHelper::$tokenClient->post($tokenRequestUrl, [
                'form_params' => [
                    'client_id' => GraphHelper::$clientId,
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
                GraphHelper::$userToken = $responseBody->access_token;
                return $responseBody->access_token;
            } else if ($tokenResponse->getStatusCode() == 400) {
                // Check the error in the response body
                $responseBody = json_decode($tokenResponse->getBody()->getContents());
                if (isset($responseBody->error)) {
                    $error = $responseBody->error;
                    // authorization_pending means we should keep polling
                    if (strcmp($error, 'authorization_pending') != 0) {
                        throw new Exception('Token endpoint returned '.$error, 100);
                    }
                }
            }
        }
    }
    // </GetUserTokenSnippet>

    // <GetUserSnippet>
    public static function getUser(): Model\User {
        $token = GraphHelper::getUserToken();
        GraphHelper::$userClient->setAccessToken($token);

        return GraphHelper::$userClient->createRequest('GET', '/me?$select=displayName,mail,userPrincipalName')
                                       ->setReturnType(Model\User::class)
                                       ->execute();
    }
    // </GetUserSnippet>

    // <GetInboxSnippet>
    public static function getInbox(): Http\GraphCollectionRequest {
        $token = GraphHelper::getUserToken();
        GraphHelper::$userClient->setAccessToken($token);

        // Only request specific properties
        $select = '$select=from,isRead,receivedDateTime,subject';
        // Sort by received time, newest first
        $orderBy = '$orderBy=receivedDateTime DESC';

        $requestUrl = '/me/mailFolders/inbox/messages?'.$select.'&'.$orderBy;
        return GraphHelper::$userClient->createCollectionRequest('GET', $requestUrl)
                                       ->setReturnType(Model\Message::class)
                                       ->setPageSize(25);
    }
    // </GetInboxSnippet>

    // <SendMailSnippet>
    public static function sendMail(string $subject, string $body, string $recipient) {
        $token = GraphHelper::getUserToken();
        GraphHelper::$userClient->setAccessToken($token);

        $sendMailBody = array(
            'message' => array (
                'subject' => $subject,
                'body' => array (
                    'content' => $body,
                    'contentType' => 'text'
                ),
                'toRecipients' => array (
                    array (
                        'emailAddress' => array (
                            'address' => $recipient
                        )
                    )
                )
            )
        );

        GraphHelper::$userClient->createRequest('POST', '/me/sendMail')
                                ->attachBody($sendMailBody)
                                ->execute();
    }
    // </SendMailSnippet>

    // <AppOnyAuthConfigSnippet>
    private static $clientSecret = '';
    private static $tenantId = '';
    private static $appClient;

    private static function ensureGraphForAppOnlyAuth() {
        if (isset(GraphHelper::$appClient)) {
            return;
        }

        GraphHelper::$clientSecret = $_ENV['CLIENT_SECRET'];
        GraphHelper::$tenantId = $_ENV['TENANT_ID'];

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
            // Create the app-only client and set the token
            GraphHelper::$appClient = new Graph();
            GraphHelper::$appClient->setAccessToken($responseBody->access_token);
        } else {
            $error = isset($responseBody->error) ? $responseBody->error : $tokenResponse->getStatusCode();
            throw new Exception('Token endpoint returned '.$error, 100);
        }
    }
    // </AppOnyAuthConfigSnippet>

    // <GetUsersSnippet>
    public static function getUsers(): Http\GraphCollectionRequest {
        GraphHelper::ensureGraphForAppOnlyAuth();

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
    public static function makeGraphCall() {
        // INSERT YOUR CODE HERE
        // Note: if using $appClient, be sure to call ensureGraphForAppOnlyAuth
        // before using it.
        // GraphHelper::ensureGraphForAppOnlyAuth();

        // Note: if using $userClient, be sure to get the user
        // token and set it in the client
        // $token = GraphHelper::getUserToken();
        // GraphHelper::$userClient->setAccessToken($token);
    }
    // </MakeGraphCallSnippet>
}
?>
