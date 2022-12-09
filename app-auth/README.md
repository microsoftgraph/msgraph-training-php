# How to run the completed project

## Prerequisites

To run the completed project in this folder, you need the following:

- [PHP](https://www.php.net/) and [Composer](https://getcomposer.org/) installed on your development machine. (**Note:** This tutorial was written with PHP version 8.1.5 and Composer version 2.3.5. The steps in this guide may work with other versions, but that has not been tested.)
- A Microsoft work or school account with the **Global administrator** role.

If you don't have a Microsoft account, you can [sign up for the Microsoft 365 Developer Program](https://developer.microsoft.com/microsoft-365/dev-program) to get a free Microsoft 365 subscription.

## Register an application

You can register an application using the Azure Active Directory admin center, or by using the [Microsoft Graph PowerShell SDK](https://learn.microsoft.com/graph/powershell/get-started).

### Azure Active Directory admin center

1. Open a browser and navigate to the [Azure Active Directory admin center](https://aad.portal.azure.com) and login using a Global administrator account.

1. Select **Azure Active Directory** in the left-hand navigation, then select **App registrations** under **Manage**.

1. Select **New registration**. Enter a name for your application, for example, `PHP App-Only Graph Tutorial`.

1. Set **Supported account types** to **Accounts in this organizational directory only**.

1. Leave **Redirect URI** empty.

1. Select **Register**. On the application's **Overview** page, copy the value of the **Application (client) ID** and **Directory (tenant) ID** and save them, you will need these values in the next step.

1. Select **API permissions** under **Manage**.

1. Remove the default **User.Read** permission under **Configured permissions** by selecting the ellipses (**...**) in its row and selecting **Remove permission**.

1. Select **Add a permission**, then **Microsoft Graph**.

1. Select **Application permissions**.

1. Select **User.Read.All**, then select **Add permissions**.

1. Select **Grant admin consent for...**, then select **Yes** to provide admin consent for the selected permission.

1. Select **Certificates and secrets** under **Manage**, then select **New client secret**.

1. Enter a description, choose a duration, and select **Add**.

1. Copy the secret from the **Value** column, you will need it in the next steps.

### PowerShell

To use PowerShell, you'll need the Microsoft Graph PowerShell SDK. If you do not have it, see [Install the Microsoft Graph PowerShell SDK](https://learn.microsoft.com/graph/powershell/installation) for installation instructions.

1. Open PowerShell and run the [RegisterAppForAppOnlyAuth.ps1](RegisterAppForAppOnlyAuth.ps1) file with the following command.

    ```powershell
    .\RegisterAppForAppOnlyAuth.ps1 -AppName "PHP App-Only Graph Tutorial" -GraphScopes "User.Read.All"
    ```

1. Copy the **Client ID**, **Tenant ID**, and **Client secret** values from the script output. You will need these values in the next step.

    ```powershell
    SUCCESS
    Client ID: ae2386e6-799e-4f75-b191-855d7e691c75
    Tenant ID: 5927c10a-91bd-4408-9c70-c50bce922b71
    Client secret: ...
    Secret expires: 10/28/2024 5:01:45 PM
    ```

## Configure the sample

1. Rename [.env.example](./graphapponlytutorial/.env.example) to **.env** and update the values according to the following table.

    | Setting         | Value |
    |-----------------|-------|
    | `CLIENT_ID`     | The client ID of your app registration |
    | `CLIENT_SECRET` | The client secret of your app registration |
    | `TENANT_ID`     | The tenant ID of your organization |

## Run the sample

In your command-line interface (CLI), navigate to the project directory and run the following commands.

```bash
composer install
php main.php
```
