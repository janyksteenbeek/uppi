<?php

namespace App\Enums\Alerts;

enum AlertTriggerType: string
{
    case DOWN = 'down';
    case RECOVERY = 'recovery';
    case ISSUE_DETECTED = 'issue_detected';
    case ISSUE_REGRESSED = 'issue_regressed';
}
