<?php

namespace App\Helpers;

class Messages
{
    protected static $messages =
        [
        'validation_messages' => [
            'required' => 'REQUEST_PARAMETER_MISSING',
            'string' => 'REQUEST_PARAMETER_INVALID_DATA_TYPE',
            'integer' => 'REQUEST_PARAMETER_INVALID_DATA_TYPE',
            'array' => 'REQUEST_PARAMETER_INVALID_DATA_TYPE',
            'size' => 'REQUEST_PARAMETER_INVALID_SIZE',
            'latitude' => 'INVALID_LATITUDE',
            'longitude' => 'INVALID_LONGITUDE',
            'min' => 'NUMBER_MUST_BE_ABOVE_0',
            'max' => 'INVALID_LIMIT_MUST_BE_BELOW_UNDER_1000',
            'different' => 'VALUE_MUST_NOT_BE_SAME',
            'in' => 'INVALID_STATUS',
        ],
        'orders' => [
            'invalid_id' => 'INVALID_ORDER_ID',
            'success' => 'SUCCESS',
            'order_conflict' => 'ORDER_ALREADY_TAKEN',
            'id_not_exist' => 'ORDER_ID_NOT_EXIST',
        ],
        'errors' => [
            'bad' => 'BAD_REQUEST',
            'guzzle' => 'SOMETHING_WENT_WRONG_WITH_GUZZLEHTTP_CLIENT',

        ],
        'google_api' => [
            'key_error' => 'GOOGLE_MAP_API_KEY_IS_NOT_SET',
        ],
        '',
    ];

    /**
     * Provided translated message if key is provided, otherwise provided whole array of
     * key->translated_message pairs
     *
     * @param string|null $key
     *
     * @return array|string|null
     */
    public static function get($key = null)
    {
        $messageList = self::$messages;

        if (null === $key) {
            return $messageList;
        }
        return array_get($messageList, $key);
    }

}
