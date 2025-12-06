<?php

// Helper to format datetime for datetime-local input
function format_datetime_local($dt)
{
    if (!$dt) {
        return '';
    }
    $t = strtotime($dt);
    if (!$t) {
        return '';
    }
    return date('Y-m-d\TH:i', $t);
}
