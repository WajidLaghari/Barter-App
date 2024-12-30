<?php

namespace App\Http\Utils;

enum Message: string
{
    public function set(string $placeholder): string
    {
        return sprintf($this->value, $placeholder);
    }

    case VALIDATION_FAILURE = 'there was the validation failure';
    case ALL_RECORDS = 'all %s records';
    case INVALID_ID = 'the provided %s ID is invalid';
    case FAILED_CREATE = 'failed to create new %s';
    case FAILED_UPDATE = 'failed to update %s';
    case FAILED_DELETED = 'failed to delete %s';
    case CREATED = 'a new %s was created';
    case UPDATED = 'the %s was updated';
    case DELETED = 'the %s was deleted';
    case RQUESTED_RECORD = 'the requested $s record';
}
