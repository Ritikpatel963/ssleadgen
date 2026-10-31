# Authentication and Authorization Security Review

**Prepared for:** Client review  
**Review date:** 6 October 2026  
**Scope:** Source-code review of the Laravel authentication and password-reset implementation and application authorization against the supplied checklists.

## Executive summary

The application uses Laravel authentication, hashes passwords, regenerates the session after successful login, invalidates the session on logout, and applies a login-attempt throttle. Password reset tokens are configured to expire after 60 minutes and Laravel deletes a token after a successful reset.

Three items need follow-up: password-reset responses disclose whether an email address is registered; the login page says “Remember me for 30 days” while Laravel’s default remember-me cookie duration is 400 days; and no MFA/2FA implementation was found. Production configuration, database contents, and operational logs were not accessible during this code review.

## Checklist results

| Checklist item | Result | Evidence and notes |
|---|---|---|
| Login requires authentication | **Pass** | Application routes are grouped behind Laravel `auth` and `user.active` middleware. Login and password-reset endpoints are public by design. A public registration route is also enabled; see the additional observation below. |
| Passwords use Laravel Hash / bcrypt / Argon2 | **Pass** | Registration and password reset use `Hash::make`; the `User` model also has Laravel’s `hashed` cast. An admin-created account uses `bcrypt` on a random password. |
| No plain-text passwords in DB/logs | **Pass in reviewed code; runtime unverified** | Password writes are hashed, and the `User` model hides the password and remember token from serialization. No authentication code logging passwords was found. Actual database rows, backups, and external logs were not inspected. |
| Password reset tokens expire | **Pass** | `config/auth.php` sets reset-token expiry to 60 minutes. |
| Password reset cannot be reused | **Pass for reset tokens** | Laravel’s password broker deletes the token after a successful reset. **Previous-password reuse is not prevented** by the reset controller; no password-history check is present. |
| Login rate limiting enabled | **Pass** | Failed attempts are limited to five per 60-second window. The counter is keyed by normalized email plus source IP and cleared after successful login. |
| Brute-force protection | **Partial** | The same five-attempt throttle provides basic protection. It is per email/IP pair; distributed attempts across IPs, cache behavior across multiple application instances, and any edge/WAF protections were not verified. |
| Account enumeration prevented where appropriate | **Needs attention** | Login errors for invalid credentials are generic. The forgot-password flow returns different messages for a registered and unregistered email, which can reveal account existence. The inactive-account message is shown only after valid credentials are supplied. |
| Logout invalidates the session | **Pass** | Logout calls Laravel logout, invalidates the session, and regenerates the CSRF token. |
| Session regenerated after login | **Pass** | The session ID is regenerated after successful authentication. |
| Remember-me functionality reviewed | **Needs alignment** | The login page says “Remember me for 30 days.” The installed Laravel `SessionGuard` default is 576,000 minutes (400 days); no project override was found. The actual cookie behavior should be confirmed in production and the duration or label aligned. |
| MFA/2FA for admin accounts where appropriate | **Not implemented** | No MFA/2FA package, routes, configuration, or application flow was found. Consider requiring MFA for administrator accounts. |

## Recommended actions

1. Make forgot-password responses identical whether or not the address exists, while continuing to send a reset email for eligible accounts.
2. Set an explicit remember-me duration that matches the product requirement, or change the UI text to match the actual duration.
3. Add MFA for administrator accounts; consider extending it to all staff accounts.
4. Confirm production uses HTTPS and sets the session cookie `Secure` flag. The config reads `SESSION_SECURE_COOKIE` from the deployment environment; that value was intentionally not inspected.
5. If the application is intended for invited staff only, disable public self-registration or document why it is enabled.
6. Confirm the production rate limiter uses a shared, reliable cache store when the app runs on multiple instances.
7. Decide whether “password reset cannot be reused” also means users must not reuse an old password. If so, add explicit password-history enforcement; it is not currently present.

## Review boundaries

This is a source-code review, not a penetration test or production configuration audit. It does not verify live database contents, deployed `.env` values, HTTPS/proxy setup, mail delivery, cache topology, infrastructure-level rate limiting, or administrator account practices. Findings describe the repository configuration reviewed on the date above.

## Code references

- `routes/web.php` — guest authentication routes and authenticated application route group.
- `app/Http/Controllers/Auth/AuthController.php` — login, registration, session regeneration, and logout.
- `app/Http/Requests/Auth/LoginRequest.php` — credential validation and login rate limiter.
- `app/Http/Controllers/Auth/PasswordResetController.php` — reset-link response and password-reset flow.
- `config/auth.php` — reset-token expiry and request throttle.
- `config/session.php` — session lifetime and cookie settings.
- `app/Models/User.php` — password hash cast and hidden credentials.
- `resources/views/auth/login.blade.php` — remember-me label.
- Laravel framework `SessionGuard` — default remember-me duration; framework dependency source, not application configuration.

## 2. Authorization / Access Control

### Executive summary

The application enforces role/module permissions in route middleware, and sensitive user/role administration actions also require Super Admin authorization. These checks run on the server, so hiding a menu item is not the only protection.

There is a significant record-level authorization gap in client status actions: the caller is checked for write access to the source status module, but the controller does not verify that the supplied client ID currently belongs to that status. A user with write access to one status can potentially change a client from another status by substituting its numeric ID in a direct request. Client profile lookup similarly accepts any client ID for a user who has read access to any client module. No Laravel model policies or Gates were found. Runtime HTTP tests for direct URL access and ID manipulation were not run as part of this source review.

### Checklist results

| Checklist item | Result | Evidence and notes |
|---|---|---|
| User cannot access another user's records | **Not enforced at record level; confirm intended model** | Client and lead records contain an assigned `user_id`, but their list queries are not scoped to the signed-in user. Task, policy, and document queries are also shared. Import status/error downloads are explicitly scoped to the requesting user, and notification reads are scoped to the requesting user. If records are intended to be shared across the team under module permissions, the broad queries may be intentional; if advisers must only access assigned records, this is a gap. |
| Admin-only routes protected | **Pass for administrative changes; read access is role-based** | Settings routes require `access` permission. User creation and update requests and user/role destructive or permission-changing actions require Super Admin checks. The access page itself allows any role granted `access:read` to view the user and role lists. |
| Role permissions enforced server-side | **Pass at module level; record-level gap noted below** | `RequirePermission` checks the requested module and level. Mutating route groups use write permission; status pages and read routes use read permission. Super Admin bypass is centralized in `User::hasPermission`. |
| Policies/Gates implemented | **Not implemented** | No Laravel policy classes or Gate definitions were found. The app relies on custom route middleware and explicit Super Admin checks. There are no model-level view/update policies to consistently enforce record ownership or status boundaries. |
| Permission checks are not only in frontend | **Pass, with an important record-scope limitation** | Protected operations have server-side route middleware; user/role administration also has request/controller authorization checks. The status-specific client routes still need to validate the target record's current status. |
| Hidden buttons cannot bypass authorization | **Pass for module permissions; not sufficient for record boundaries** | The server checks route permissions even when a menu or button is hidden. A caller with permission for a status module can still substitute another client ID where controller actions do not verify that client's current status. |
| Direct URL access tested | **Not dynamically tested** | Route definitions were inspected and show `auth`, active-user, and module middleware on the main application. No authenticated HTTP test was performed to confirm 403/redirect behavior for every endpoint. Public document sharing by token is an intentional exception. |
| ID manipulation tested | **Not dynamically tested; source indicates a likely cross-status bypass** | Laravel route-model binding resolves the requested client, but status transition actions generally update that model without validating its current status. `/clients/{status}/{client}/...` therefore relies on the status module permission alone. `/clients/{client}/profile` also loads any bound client; `clients_profile` grants access when the user can read any client module. User administration ID routes are separately protected by Super Admin checks. |

### Recommended actions

1. Enforce the client's expected current status in every status-specific transition action, or authorize the transition through a Client policy before updating. Return 404/403 for a client outside the permitted status.
2. Decide whether clients and leads are team-wide or adviser-owned. If adviser-owned, scope list, profile, update, transition, export, report, and lookup queries to the current user's assigned records; apply the same rule to tasks/documents if those are private.
3. Add model policies for client, lead, task, document, and user actions where ownership or record state matters. Keep route middleware for module-level permissions.
4. Add feature tests that sign in as limited roles and attempt direct URLs and altered IDs: no module permission, read-only mutation, another adviser's record, and a record in a different client status. Assert 403/404 and verify the record remains unchanged.
5. Keep the user/role administration checks in place; add tests proving that a non-Super Admin cannot change a user's role, deactivate/delete users, or alter role permissions by calling the endpoints directly.

### Authorization evidence reviewed

- `routes/web.php` — authenticated route group, per-module read/write middleware, client transition routes, and access-control routes.
- `app/Http/Middleware/RequirePermission.php` — server-side module permission enforcement.
- `app/Models/User.php` and `app/Models/Role.php` — role permission resolution and Super Admin bypass.
- `app/Http/Controllers/Admin/UserController.php` — Super Admin checks for user and role administration.
- `app/Http/Requests/Admin/StoreUserRequest.php` and `UpdateUserRequest.php` — request-level Super Admin authorization.
- `app/Http/Controllers/Crm/ClientController.php` — unscoped client listing/profile lookup and status transitions.
- `app/Http/Controllers/Crm/LeadController.php` — lead listing and ID-bound lead actions.
- `app/Http/Controllers/TaskController.php` — task listing and ID-bound task actions.
- `app/Http/Controllers/UniversalImportController.php` — import status and error-download ownership filters.
- `app/Http/Controllers/NotificationController.php` — notification lookup constrained to the current user's notifications.
- No `app/Policies` directory or `Gate::define` / `Gate::policy` usage was found.
