<?php

return [
    'max_event_size_kb' => env('ERROR_TRACKING_MAX_EVENT_SIZE_KB', 1024),
    'ingest_queue' => env('ERROR_TRACKING_INGEST_QUEUE', 'errors'),
    'alerts_queue' => env('ERROR_TRACKING_ALERTS_QUEUE', 'alerts'),
];
