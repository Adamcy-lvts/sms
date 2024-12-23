<?php

function formatDate($date)
{
    // Create a new DateTime object from the provided date
    $dateObject = new DateTime($date);

    // Format the date and time into the desired format
    return $dateObject->format('D M d Y g:i A');
}

function getOrdinal($number): string
{
    if (($number % 100) >= 11 && ($number % 100) <= 13) {
        return $number . 'th';
    }

    return $number . match ($number % 10) {
        1 => 'st',
        2 => 'nd',
        3 => 'rd',
        default => 'th'
    };
}
