<?php

function formatDate($date)
{
    // Create a new DateTime object from the provided date
    $dateObject = new DateTime($date);

    // Format the date and time into the desired format
    return $dateObject->format('D M d Y g:i A');
}