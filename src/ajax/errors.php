<?php
require_once 'config.php';
/**
 * Class that owns array of errors and method to return such errors
 */
class ErrorRegistry {
    private static array $errors = [
        'generic' => 'An unknown error has occured. Please try again later.',
        'auth' => [
            'unverified' => 'Please verify your account to continue this action.',
            'unauthed' => 'Please login to continue this action.',
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
        'user' => [
            'not_found' => 'User not found in database',
            'private_profile' => 'User has privated their profile.',
            'private_profile_detailed' => "When an account is private, other users can\'t view the profile unless they are following said user, or said user is following them.",
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
    ];

    public static function get(string $type, string $code, ?array $placeholders = []): string {
		if(!empty($placeholders)) {
			$error = vsprintf(self::$errors[$type][$code], $placeholders);
		} else {
			$error = self::$errors[$type][$code];
		}

        return $error ?? self::$errors['generic'];
    }
}
?>