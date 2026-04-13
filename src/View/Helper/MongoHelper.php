<?php

declare(strict_types=1);

namespace App\View\Helper;

use Cake\View\Helper;

class MongoHelper extends Helper
{
    public function toString(mixed $value): string
    {
        if ($value instanceof \MongoDB\BSON\UTCDateTime) {
            return $value->toDateTime()->format('Y-m-d H:i:s');
        }

        if ($value instanceof \MongoDB\BSON\ObjectId) {
            return (string)$value;
        }

        if ($value instanceof \MongoDB\Model\BSONDocument) {
            return (string)json_encode($value->getArrayCopy(), JSON_UNESCAPED_SLASHES);
        }

        if ($value instanceof \MongoDB\Model\BSONArray) {
            return (string)json_encode($value->getArrayCopy(), JSON_UNESCAPED_SLASHES);
        }

        if (is_array($value)) {
            return (string)json_encode($value, JSON_UNESCAPED_SLASHES);
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return '';
        }

        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return (string)$value;
            }

            return (string)json_encode((array)$value, JSON_UNESCAPED_SLASHES);
        }

        return (string)$value;
    }
}
