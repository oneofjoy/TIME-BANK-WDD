# Local authentication setup

Open **http://localhost/TIME-BANK-WDD/** through XAMPP Apache. Start Apache and MySQL in XAMPP after restarting Windows. VS Code Live Server does not execute PHP; it cannot provide login/session functionality.

Local previews at `localhost`/`127.0.0.1` on Live Server ports 5500/5501 automatically move to the configured XAMPP URL, preserving the current page, query, and anchor. Start Apache before opening these previews. Other hosting environments keep their own origin.

The local Apache alias serves `D:/Web Space/TIME-BANK-WDD/` directly. Its configuration is in `C:/xampp/apache/conf/extra/httpd-timebank.conf`, included from `httpd.conf`. This alias is restricted to localhost, disables directory listings, and denies direct access to SQL, Git metadata, and PHP configuration/helpers. Apache configuration backups use the suffix `.timebank-<timestamp>.bak`.

The existing database is `timebank_db`. The SQL in `database/timebank_database.sql` is a reference copy, not an automatic migration. Do not replace or reimport an existing database to run authentication.

Database credentials live in **php/config.local.php**, which is ignored by Git. For another local checkout, copy `php/config.example.php` to that name and enter the local credentials. Do not commit secrets. This localhost configuration is not a public deployment configuration.

Registration creates an active Member, profile, and wallet with zero credits in one transaction. It redirects to Login; successful login redirects to Dashboard. PHP sessions maintain authentication, and logout invalidates the session. Learning and teaching use the same Member account.

Session endpoint: `GET php/get-session.php`. Register/Login/Logout use POST JSON and the session CSRF token in `X-CSRF-Token`. Responses contain `success`, `message`, optional field `errors`, and safe session data where applicable. Logout requires a current Member session. Password hashes and database errors are never returned.

Dashboard, My Sessions, My Schedule, My Profile, My Skills, Time Wallet, and Account Settings are protected **Coming Soon** pages. The existing Explore document remains empty. OAuth, recovery, persistent Remember me, booking, credit transactions, account management, and Admin UI are not implemented.
