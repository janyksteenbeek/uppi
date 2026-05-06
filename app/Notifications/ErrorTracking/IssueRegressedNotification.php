<?php

namespace App\Notifications\ErrorTracking;

use App\Models\ErrorTracking\IssueAnomaly;

class IssueRegressedNotification extends IssueDetectedNotification
{
    public function __construct(IssueAnomaly $anomaly)
    {
        parent::__construct($anomaly, '↩️', 'Issue regressed');
    }
}
