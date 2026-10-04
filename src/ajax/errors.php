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
        ],
        'db' => [
            'conn_fail' => 'Connection failed.',
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
        ],
        'comment' => [
            'generic' => 'Could not send comment. Please try again later.',
            'unauthed' => 'Please login to comment.',
            'unverified' => 'Please verify your account to comment.',
            'not_found' => 'Comment not found in database.',
            'reply_not_found' => 'The comment that you are trying to reply to does not exist.',
            'too_long' => 'Comment must be less than 500 characters.',
            'too_short' => 'Comment must contain text.',
        ],
    ];

    public static function get(string $type, string $code): string {
        return self::$errors[$type][$code] ?? self::$errors['generic'];
    }
}
?>