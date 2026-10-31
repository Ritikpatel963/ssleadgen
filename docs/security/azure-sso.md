# Microsoft Entra ID SSO

The application supports Microsoft Entra ID single-tenant sign-in alongside local email/password login. SSO only authenticates an existing active application user with a matching email address; it does not create users or change their roles.

## Entra app registration

Register a Web application in the Entra admin center and add the callback URL for each environment:

`https://your-app.example.com/auth/azure/callback`

Create a client secret and configure the application with the tenant-specific authority. The Socialite provider uses the delegated Microsoft Graph `User.Read` permission to retrieve the signed-in user's profile. Do not use `common`, `organizations`, or `consumers` for this single-tenant integration. Add the credentials to the deployment environment, never to source control:

```dotenv
AZURE_CLIENT_ID=your-application-client-id
AZURE_CLIENT_SECRET=your-client-secret
AZURE_TENANT_ID=your-directory-tenant-id
AZURE_REDIRECT_URI=https://your-app.example.com/auth/azure/callback
```

Set `APP_URL` to the same HTTPS origin, then run `php artisan config:cache` during deployment. The configured redirect URI must exactly match the Entra registration.

The login page displays the Microsoft sign-in option only when all four Azure settings are present. During local development, register `http://localhost:8000/auth/azure/callback` as a separate redirect URI if needed.

## Account access

Create and assign each user in the application before enabling SSO for them. The email address returned by Entra must match the application user's email address. The user's existing active status and role permissions remain authoritative. Local login remains available as a recovery path.