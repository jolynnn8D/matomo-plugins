<?php


namespace Piwik\Plugins\UserFlow;


class Commons
{
    const URL_KEY = 'url';
    const ACTION_KEY = 'actionDetails';
    const AVERAGE_TIME_KEY = 'average_time';
    const VISITOR_KEY = 'nb_visits';
    const TIME_KEY = 'timeSpent';
    const DOMAIN_KEY = 'domain';
    const ID_PATTERN = '/\/[0-9]{1,}/';
    const LAST_SEGMENT_REGEX = '/[^\/]+(?=\/$|$)/';
    const DOMAIN_REGEX = '/^(?:http?:\/\/)?(?:[^@\/\n]+@)?(?:www\.)?([^:\/?\n]+)?(\:[0-9]{1,}){0,1}/';
}