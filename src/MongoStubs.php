<?php

declare(strict_types=1);

namespace MongoDB\BSON;

if (!class_exists(UTCDateTime::class, false)) {
    class UTCDateTime
    {
        private int $milliseconds;

        public function __construct(?int $milliseconds = null)
        {
            $this->milliseconds = $milliseconds ?? (int)floor(microtime(true) * 1000);
        }

        public function toDateTime(): \DateTimeImmutable
        {
            $seconds = intdiv($this->milliseconds, 1000);
            $remainderMilliseconds = $this->milliseconds % 1000;

            return (new \DateTimeImmutable('@' . $seconds))
                ->setTimezone(new \DateTimeZone(date_default_timezone_get()))
                ->modify(sprintf('+%d milliseconds', $remainderMilliseconds));
        }
    }
}

if (!class_exists(ObjectId::class, false)) {
    class ObjectId
    {
        private string $id;

        public function __construct(?string $id = null)
        {
            $this->id = $id ?? bin2hex(random_bytes(12));
        }

        public function __toString(): string
        {
            return $this->id;
        }
    }
}

namespace MongoDB\Driver\Exception;

if (!class_exists(BulkWriteError::class, false)) {
    class BulkWriteError
    {
        public function getMessage(): string
        {
            return 'bulk write error';
        }
    }
}

if (!class_exists(BulkWriteResult::class, false)) {
    class BulkWriteResult
    {
        /**
         * @return array<int, BulkWriteError>
         */
        public function getWriteErrors(): array
        {
            return [];
        }
    }
}

if (!class_exists(BulkWriteException::class, false)) {
    class BulkWriteException extends \RuntimeException
    {
        public function getWriteResult(): BulkWriteResult
        {
            return new BulkWriteResult();
        }
    }
}
