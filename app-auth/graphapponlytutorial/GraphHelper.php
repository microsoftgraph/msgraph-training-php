<?php
// Copyright (c) Microsoft Corporation. All rights reserved.
// Licensed under the MIT license.

// <UseSnippet>
use Microsoft\Graph\Core\Authentication\GraphPhpLeagueAccessTokenProvider;
use Microsoft\Graph\Generated\Models;
use Microsoft\Graph\Generated\Users\UsersRequestBuilderGetQueryParameters;
use Microsoft\Graph\Generated\Users\UsersRequestBuilderGetRequestConfiguration;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;
// </UseSnippet>

class GraphHelper {
    // <AppOnlyAuthConfigSnippet>
    private static string $clientId = '';
    private static string $clientSecret = '';
    private static string $tenantId = '';
    private static ClientCredentialContext $tokenContext;
    private static GraphServiceClient $appClient;

    public static function initializeGraphForAppOnlyAuth(): void {
        GraphHelper::$clientId = $_ENV['CLIENT_ID'];
        GraphHelper::$clientSecret = $_ENV['CLIENT_SECRET'];
        GraphHelper::$tenantId = $_ENV['TENANT_ID'];

        GraphHelper::$tokenContext = new ClientCredentialContext(
            GraphHelper::$tenantId,
            GraphHelper::$clientId,
            GraphHelper::$clientSecret);

        GraphHelper::$appClient = new GraphServiceClient(
            GraphHelper::$tokenContext, ['https://graph.microsoft.com/.default']);
    }
    // </AppOnlyAuthConfigSnippet>

    // <GetAppOnlyTokenSnippet>
    public static function getAppOnlyToken(): string {
        // Create an access token provider to get the token
        $tokenProvider = new GraphPhpLeagueAccessTokenProvider(GraphHelper::$tokenContext);
        return $tokenProvider
            ->getAuthorizationTokenAsync('https://graph.microsoft.com')
            ->wait();
    }
    // </GetAppOnlyTokenSnippet>

    // <GetUsersSnippet>
    public static function getUsers(): Models\UserCollectionResponse {
        $configuration = new UsersRequestBuilderGetRequestConfiguration();
        $configuration->queryParameters = new UsersRequestBuilderGetQueryParameters();
        // Only request specific properties
        $configuration->queryParameters->select = ['displayName','id','mail'];
        // Sort by display name
        $configuration->queryParameters->orderby = ['displayName'];
        // Get at most 25 results
        $configuration->queryParameters->top = 25;

        return GraphHelper::$appClient->users()->get($configuration)->wait();
    }
    // </GetUsersSnippet>

    // <MakeGraphCallSnippet>
    public static function makeGraphCall(): void {
        // INSERT YOUR CODE HERE
    }
    // </MakeGraphCallSnippet>
}
?>
