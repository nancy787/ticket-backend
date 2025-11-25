<?php

use Carbon\Carbon;
use App\Models\Email;

if (!function_exists('format_price')) {
    function format_price($price)
    {
        return number_format($price, 2);
    }
}

if (!function_exists('formatDate')) {
    function formatDate($date)
    {
        if (isset($date)) {
            return Carbon::parse($date)->format('l, d M, Y');
        } else {
            return 'NA';
        }
    }
}

if (!function_exists('formatDateForInput')) {
    function formatDateForInput($date)
    {
        if (isset($date)) {
            return Carbon::parse($date)->format('Y-m-d');
        } else {
            return null;
        }
    }
}

if (!function_exists('formatgetDate')) {
    function formatgetDate($date)
    {
        if (isset($date)) {
            return Carbon::parse($date)->format('d-m-Y');
        } else {
            return null;
        }
    }
}


if (!function_exists('formatDay')) {
    function formatDay($date)
    {
        if (isset($date)) {
            return Carbon::parse($date)->format('D');
        } else {
            return 'NA';
        }
    }
}


if (!function_exists('formatDayInput')) {
    function formatDayInput($date)
    {
        if (isset($date)) {
            return Carbon::parse($date)->format('l d M Y');
        } else {
            return 'NA';
        }
    }
}

if (!function_exists('formatFullDay')) {
    function formatFullDay($date)
    {
        if (isset($date)) {
            return Carbon::parse($date)->format('l');
        } else {
            return 'NA';
        }
    }
}



if (!function_exists('formatSoldDate')) {
    function formatSoldDate($date)
    {
        if (isset($date)) {
            return Carbon::parse($date)->format('d M, Y');
        } else {
            return 'NA';
        }
    }
}


if (!function_exists('formatgetDay')) {
    function formatgetDay($date)
    {
        if (isset($date)) {
            $formattedDate = Carbon::parse($date)->format('d-m-Y D');
            return preg_replace_callback('/\b[a-z]{3}\b/i', function ($matches) {
                return strtoupper($matches[0]);
            }, $formattedDate);
        } else {
            return null;
        }
    }
}

function storeEmail($userId, $from, $to, $subject= null, $content = null) {

    $emailStore = [
        'user_id'  => $userId, 
        'from'     => $from,
        'to'       => $to, 
        'subject'  => $subject,
        'content'  => $content,
    ];

    Email::create($emailStore);

    return; 
}