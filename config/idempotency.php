<?php

declare(strict_types=1);

return [
    /**
     * The name of the main idempotency HTTP header.
     */
    'main_header_name' => env('IDEMPOTENCY_MAIN_HEADER', 'Idempotency-Key'),

    /**
     * The name of the repeated idempotency HTTP header.
     */
    'repeated_header_name' => env('IDEMPOTENCY_REPEATED_HEADER', 'Idempotent-Replayed'),

    /**
     * The idempotency key expiration time and should be defined in minutes.
     *
     * Defaults to 6 hours.
     */
    'expiration_time' => env('IDEMPOTENCY_EXPIRATION_TIME', 360),

    /**
     * The HTTP methods that should be considered idempotent.
     */
    'http_methods' => ['POST', 'PUT', 'PATCH', 'DELETE'],
];
