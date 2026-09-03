# gr8brik-website
The Gr8brik website's source code (updated every couple of weeks)

<br>The Gr8brik modeler's repo can be found [here](https://github.com/susstevedev/gr8brik). Please do not post issues or PRs regarding the modeler here, but refer to that repo instead.

# Pages
<sub>Some pages may still be missing or incorrectly referenced</sub>
<sub>Big thanks to [@The-An0nym's readme](https://github.com/The-An0nym/gr8brik-website/blob/main/README.md)</sub>

| Description                | URL path            | Repo path                                              |
| -------------------------- | ------------------- | ------------------------------------------------------ |
| Homepage                   | `/`                 | [`/index.php`](/src/index.php)                         |
| Modeler                    | `/modeler`          | [`/mod/new/index.html`](/src/mod/new/index.html)       |
| Creations                  | `/list`             | [`/list.php`](/src/list.php)                           |
| Build                      | `/build/{id}`       | [`/creation.php`](/src/creation.php)                   |
| Community                  | `/com`              | [`/com/index.php`](/src/com/index.php)                 |
| Community thread           | `/topic/{id}`       | [`/com/view.php`](/src/com/view.php)                   |
| User profile page          | `/user/{id}`        | [`/profile.php`](/src/profile.php)                     |
| User profile page          | `/@{username}`      | [`/profile.php`](/src/profile.php)                     |
| User list                  | `/users`            | [`/users.php`](/src/users.php)                         |
| **Account**                |                     |                                                        |
| Account settings           | `/acc`              | [`/acc/index.php`](/src/acc/index.php)                 |
| Account notifications      | `/acc/notifications`| [`/acc/notifications.php`](/src/acc/notifications.php) |
| Account creations          | `/acc/creations`    | [`/acc/creations.php`](/src/acc/creations.php)         |
| Account follower info      | `/acc/following`    | [`/acc/following.php`](/src/acc/following.php)         |
| Account ban appeals        | `/acc/appeals`      | [`/acc/appeals.php`](/src/acc/appeals.php)             |
| Account reported creations | `/acc/reported`     | [`/acc/reported.php`](/src/acc/reported.php)           |
| Account sessions           | `/acc/logins`       | [`/acc/logins.php`](/src/acc/logins.php)               |
| Login                      | `/acc/login.php`    | [`/acc/login.php`](/src/acc/login.php)                 |
| Register                   | `/acc/register.php` | [`/acc/register.php`](/src/acc/register.php)           |
| **Legal**            |                     |                                                        |
| Terms and Conditions       | `/terms.php`        | [`/terms.php`](/src/info/terms.php)                    |
| Privacy Policy             | `/privacy.php`      | [`/privacy.php`](/src/info/privacy.php)                |
| Rules/Community guidelines | `/topic/17`         | N/A                                                    |

# Setup
## Constants file ([`/ajax/constants/constants.php`](/src/ajax/constants/constants.php))

```
<?php
  define('DB_NAME', 'membership');
  define('DB_NAME2', 'creations');
  define('DB_NAME3', 'forum');
  define('DB_MAIL', 'me@mymailserver.com');
  define('MODEL_STORAGE_LIMIT', 50 * 1024 * 1024);
  define('DB_SERVER', 'localhost:3000');
  define('DB_USER', 'my_mysql_user');
  define('DB_PASSWORD', 'my_mysql_pwd');
  
  //all after this are optional but required for oauth and mail
  define('GMAIL_APP_PWD', 'my_gmail_app_pwd');
  define('GOOGLE_CLIENT_ID', 'my_google_oauth_client_id');
  define('GOOGLE_CLIENT_SECRET', 'super-secret-google');
  define('GMAIL_USER', 'mygmailoremailwithgoogle@gmail.com');
  define('GITHUB_CLIENT_ID', 'my_github_oauth_client_id');
  define('GITHUB_CLIENT_PWD', 'super-secret-github');
?>
```

## Disclaimer
For privacy reasons, user data is not included in this repository.
