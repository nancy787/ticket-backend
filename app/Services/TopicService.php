<?php

namespace App\Services;
use App\Models\TicketCategory;

class TopicService
{

    public static $topics;

    public static function initializeTopics()
    {
        self::$topics = TicketCategory::pluck('name')->toArray();
    }
}

TopicService::initializeTopics();

?>