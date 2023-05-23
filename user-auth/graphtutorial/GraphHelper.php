<?php
// Copyright (c) Microsoft Corporation. All rights reserved.
// Licensed under the MIT license.

// <UseSnippet>
use Microsoft\Graph\Generated\Models;
use Microsoft\Graph\Generated\Users\Item\MailFolders\Item\Messages\MessagesRequestBuilderGetQueryParameters;
use Microsoft\Graph\Generated\Users\Item\MailFolders\Item\Messages\MessagesRequestBuilderGetRequestConfiguration;
use Microsoft\Graph\Generated\Users\Item\SendMail\SendMailPostRequestBody;
use Microsoft\Graph\Generated\Users\Item\UserItemRequestBuilderGetQueryParameters;
use Microsoft\Graph\Generated\Users\Item\UserItemRequestBuilderGetRequestConfiguration;
use Microsoft\Graph\GraphRequestAdapter;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Abstractions\Authentication\BaseBearerTokenAuthenticationProvider;

require_once 'DeviceCodeTokenProvider.php';
// </UseSnippet>

class GraphHelper {
    // <UserAuthConfigSnippet>
    private static string $clientId = '';
    private static string $tenantId = '';
    private static string $graphUserScopes = '';
    private static DeviceCodeTokenProvider $tokenProvider;
    private static GraphServiceClient $userClient;

    public static function initializeGraphForUserAuth(): void {
        GraphHelper::$clientId = $_ENV['CLIENT_ID'];
        GraphHelper::$tenantId = $_ENV['TENANT_ID'];
        GraphHelper::$graphUserScopes = $_ENV['GRAPH_USER_SCOPES'];

        GraphHelper::$tokenProvider = new DeviceCodeTokenProvider(
            GraphHelper::$clientId,
            GraphHelper::$tenantId,
            GraphHelper::$graphUserScopes);
        $authProvider = new BaseBearerTokenAuthenticationProvider(GraphHelper::$tokenProvider);
        $adapter = new GraphRequestAdapter($authProvider);
        GraphHelper::$userClient = new GraphServiceClient($adapter);
    }
    // </UserAuthConfigSnippet>

    // <GetUserTokenSnippet>
    public static function getUserToken(): string {
        return GraphHelper::$tokenProvider
            ->getAuthorizationTokenAsync('https://graph.microsoft.com')->wait();
    }
    // </GetUserTokenSnippet>

    // <GetUserSnippet>
    public static function getUser(): Models\User {
        $configuration = new UserItemRequestBuilderGetRequestConfiguration();
        $configuration->queryParameters = new UserItemRequestBuilderGetQueryParameters();
        $configuration->queryParameters->select = ['displayName','mail','userPrincipalName'];
        return GraphHelper::$userClient->me()->get($configuration)->wait();
    }
    // </GetUserSnippet>

    // <GetInboxSnippet>
    public static function getInbox(): Models\MessageCollectionResponse {
        $configuration = new MessagesRequestBuilderGetRequestConfiguration();
        $configuration->queryParameters = new MessagesRequestBuilderGetQueryParameters();
        // Only request specific properties
        $configuration->queryParameters->select = ['from','isRead','receivedDateTime','subject'];
        // Sort by received time, newest first
        $configuration->queryParameters->orderby = ['receivedDateTime DESC'];
        $configuration->queryParameters->top = 25;
        return GraphHelper::$userClient->me()
            ->mailFolders()
            ->byMailFolderId('inbox')
            ->messages()
            ->get($configuration)->wait();
    }
    // </GetInboxSnippet>

    // <SendMailSnippet>
    public static function sendMail(string $subject, string $body, string $recipient): void {
        $message = new Models\Message();
        $message->setSubject($subject);

        $itemBody = new Models\ItemBody();
        $itemBody->setContent($body);
        $itemBody->setContentType(new Models\BodyType(Models\BodyType::TEXT));
        $message->setBody($itemBody);

        $email = new Models\EmailAddress();
        $email->setAddress($recipient);
        $to = new Models\Recipient();
        $to->setEmailAddress($email);
        $message->setToRecipients([$to]);

        $sendMailBody = new SendMailPostRequestBody();
        $sendMailBody->setMessage($message);

        GraphHelper::$userClient->me()->sendMail()->post($sendMailBody)->wait();
    }
    // </SendMailSnippet>

    // <MakeGraphCallSnippet>
    public static function makeGraphCall(): void {
        // INSERT YOUR CODE HERE
    }
    // </MakeGraphCallSnippet>
}
?>
