<?php
require_once 'config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/const/utils/what_browser.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/const/utils/svgs.php';

/**
 * Class that owns array of errors and method to return such errors
 */
class ErrorRegistry {
    private static array $errors = [
        'generic' => 'An unknown error has occured. Please try again later.',
        'auth' => [
            'unverified' => 'Please verify your account to continue this action.',
            'unauthed' => 'Please login to continue this action.',
            'bad_auth' => 'You do not have permission to do this',
            'bad_csrf' => 'Your cross-site-request-forgery token seems to be invalid.',
			'ip_ban' => 'Banned at %s, until %s',
        ],
		'auth_login' => [
			'field_missing' => 'Field %s cannot be empty.',
			'authed' => 'Already logged in.',
			'bad_password' => 'Invalid combination of email or username and password.',
			'bad_format' => 'Invalid format for %s',
			'password_short' => 'Password cannot be less than 8 characters.',
			'password_long' => 'Password cannot be more than 128 characters.',
			'field_not_avaliable' => 'This %s is not avaliable',
		],
        'account_settings' => [
            'banner_upload_fail' => "Sorry, there was an error uploading your file.",
            'email_change_failure' => 'An error occured while changing the email address',
            'email_change_bad_format' => 'Email address is not valid',
            'email_change_taken' => 'Email address is not avaliable',
            'email_change_not_matching' => 'Old email address does not equal %s',
        ],
        'db' => [
            'generic' => 'An unknown error has occured. Please try again later.',
            'conn_fail' => 'Connection to %s failed',
            'query_fail' => 'Query execution failed.',
        ],
        'creation' => [
            'bad_id' => 'Id must be an integer',
            'not_found' => 'Creation not found in database',
            'user_blocked' => 'The creator of this creation has blocked you.',
            'user_blocking' => 'You have blocked the creator of this creation.',
            'format_invalid' => "Invalid creation format.",
            'visibility_invalid' => "Visibility must be one of: public, unlisted, private.",
            'visibility_unverified' => 'Please verify your account to create public creations.',
            'storage_invalid' => "Failed to check storage usage.",
            'storage_max' => "Storage limit of " . MODEL_STORAGE_LIMIT . " was reached.",
            'save_fail' => "Failed to save creation.",
            'update_fail' => "Failed to update creation.",
            'thumbnail_encoding_bad' => "Thumbnail is not a valid image encoded in Webp or PNG.",
            'thumbnail_save_fail' => "Failed to save thumbnail.",
        ],
        'creation_file' => [
            'cannot_edit' => 'You are not allowed to edit this Creation. Please contact the creator via Direct Messages.',
            'not_found' => 'There is no JSON file for this Creation.',
            'format_invalid' => "The JSON file for this Creation is invalid.",
        ],
        'user' => [
            'not_found' => 'User not found in database',
            'deactive_profile' => 'This user has deleted their account',
            'private_profile' => 'User has privated their profile',
            'suspended_profile' => 'This account has been suspended %s.<br />When an account is suspended, the owner can\'t sign in or interact with content. However, they can appeal the suspension.',
            'private_profile_detailed' => "When an account is private, other users can't view the profile unless they are following said user, or said user is following them.",
        ],
        'profile_follow' => [
            'generic' => "An error occured while following this user",
            'generic_unfollow' => "An error occured while unfollowing this user",
            'self_follow' => "You cannot follow yourself",
        ],
        'profile_block' => [
            'generic' => "An error occured while blocking this user",
            'generic_unblock' => 'An error occurred while unblocking this user',
            'self_block' => "You cannot block yourself",
        ],
        'comment' => [
            'generic' => 'Could not send comment. Please try again later.',
			'bad_id' => 'Id must be an integer',
            'unauthed' => 'Please login to comment.',
            'unverified' => 'Please verify your account to comment.',
            'not_found' => 'Comment does not exist in the database.',
            'reply_not_found' => 'The comment that you are trying to reply to does not exist.',
            'too_long' => 'Comment must be less than 500 characters.',
            'too_short' => 'Comment must contain text.',
            'cooldown_active' => 'Please wait %s seconds before posting another comment.',
			'owner_banned' => 'The account that had posted this reply has been suspended for violating the Guidelines.',
			'owner_not_found' => 'The account that had posted this reply has been deleted.',
            'user_blocked' => 'The creator of this Comment has blocked you.',
            'user_blocking' => 'You have blocked the creator of this Comment.',
            'user_blocked_blocking' => 'You have blocked the creator of this Comment, and the creator of this Comment has blocked you.',
			'comment_not_faved' => 'You have not favorited this comment',
			'comment_fav_not_allowed' => 'You cannot favorite this comment',
			'comment_faved' => 'You have already favorited this comment',
			'comment_unfav_fail' => 'Failed to unfavorite',
			'comment_fav_fail' => 'Failed to favorite this comment',
        ],
        'topic_reply' => [
            'user_blocked' => 'The creator of this Reply has blocked you.',
            'user_blocking' => 'You have blocked the creator of this Reply.',
        ],
        'direct_message' => [
            'not_found' => 'Group not found in database.',
            'message_too_short' => 'Message shall contain text.',
            'compose_users_empty' => 'No usernames provided.',
            'compose_cannot_message_self' => 'You cannot message yourself.',
            'compose_users_not_found' => 'One or more users were not found',
            'delete_perms_missing' => 'No permission to delete this group.',
        ],
    ];

    public static function get(string $type, string $code, ?array $placeholders = []): string {
        if(isset(self::$errors[$type][$code])) {
            if(!empty($placeholders)) {
                $error = vsprintf(self::$errors[$type][$code], $placeholders);
            } else {
                $error = self::$errors[$type][$code];
            }
        } else {
            $error = self::$errors['generic'];
        }

        return $error;
    }

    /**
     * Function to generate dynamic Gr8brik error page
     * @param string $TitleError Error to display in page title
     * @param string $Error Full error message
     */
    public static function ErrorPage(string $TitleError, string $Error) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    ?>
        <!DOCTYPE html>
        <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
                <title><?php echo $TitleError ?></title>
                <link rel="stylesheet" href="/lib/w3_new.css">
                <link rel="stylesheet" href="/lib/theme.css">
                <link rel="apple-touch-icon" href="/img/logo.jpg" />
                <meta name="apple-mobile-web-app-status-bar" content="#f1f1f1" />
                <meta name="theme-color" content="#f1f1f1" />
            </head>
            <body class="w3-container w3-center w3-light-grey">
                <div id="ErrorDetails">
                    <h4><?php echo $TitleError ?></h4>
                    <p><?php echo $Error ?>
                    <p><a href="" class="w3-button w3-round w3-border w3-border-grey">Retry</a></p>
                </div>
                <div id="ClientInfo">
                    <h4>Client details</h4>
                    <ul>
                        <li>IP: <?php echo $_SERVER['REMOTE_ADDR'] ?></li>
                        <li>Device: <?php echo UA ?></li>
                        <li>UserID: <?php echo $_SESSION['userid'] ?? 'not set' ?></li>
                    </ul>
                </div>
                <hr />
                <footer id="Footer" class="w3-round w3-padding w3-border w3-border-grey">
                    <span>©2026 The Gr8Brik Team. All rights reserved.</span>
                    <a href="/terms.php">Terms and Conditions</a>.
                    <a href="/privacy.php">Privacy Policy</a>.

                    <?php if(isset(appSpace->__urls->__socials)) { ?>
                        <a href="mailto:<?php echo DB_MAIL ?>" target="_blank" class="w3-hover-text-red" alt="Email">
                            <?php GenerateSVGs::get('bi-envelope-at'); ?>
                        </a>

                        <a href="<?php echo appSpace->__urls->__socials['twitter'] ?>" target="_blank" class="w3-hover-text-blue" alt="Twitter">
                            <?php GenerateSVGs::get('bi-twitter-x'); ?>
                        </a>

                        <a href="<?php echo appSpace->__urls->__socials['youtube'] ?>" target="_blank" class="w3-hover-text-red" alt="YouTube">
                            <?php GenerateSVGs::get('bi-youtube'); ?>
                        </a>

                        <a href="<?php echo appSpace->__urls->__socials['github'] ?>" target="_blank" class="w3-hover-text-white" alt="Github">
                            <?php GenerateSVGs::get('bi-github'); ?>
                        </a>

                        <a href="<?php echo appSpace->__urls->__socials['reddit'] ?>" target="_blank" class="w3-hover-text-orange" alt="Reddit">
                            <?php GenerateSVGs::get('bi-reddit'); ?>
                        </a>

                        <a href="<?php echo appSpace->__urls->__socials['discord'] ?>" target="_blank" alt="Discord">
                            <?php GenerateSVGs::get('bi-discord'); ?>
                        </a>
                    <?php } ?>
                    <p class="w3-small">Lego&#174;, the Lego&#174; logo, the Minifigure&#8482;, and the Brick&#8482; and Knob&#8482; configurations are trademarks of the Lego&#174; company, which does not sponsor, authorize, or endorse this site.</p>
                </footer><br />
            </body>
        </html>
    <?php
    }
}
?>