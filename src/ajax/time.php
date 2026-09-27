<?php
function time_ago($timestamp) {
    require_once 'numbers.php';

    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $time_ago - $current_time;

    $seconds = abs($time_difference);
    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    $weeks = round($seconds / 604800);
    $months = round($seconds / 2629800);
    $years = round($seconds / 31556926);

    if ($time_difference < 0) {
        if ($seconds < 60) {
            return Numbers::format($seconds) . " seconds ago";
        } else if ($minutes < 60) {
            return Numbers::format($minutes) . " minutes ago";
        } else if ($hours < 24) {
            return Numbers::format($hours) . " hours ago";
        } else if ($days < 7) {
            return Numbers::format($days) . " days ago";
        } else if ($weeks < 4) {
            return Numbers::format($weeks) . " weeks ago";
        } else if ($months < 12) {
            return Numbers::format($months) . " months ago";
        } else {
            return Numbers::format($years) . " years ago";
        }
    } else {
        if ($seconds < 60) {
            return "in " . Numbers::format($seconds) . " seconds";
        } else if ($minutes < 60) {
            return "in " . Numbers::format($minutes) . " minutes";
        } else if ($hours < 24) {
            return "in " . Numbers::format($hours) . " hours";
        } else if ($days < 7) {
            return "in " . Numbers::format($days) . " days";
        } else if ($weeks < 4) {
            return "in " . Numbers::format($weeks) . " weeks";
        } else if ($months < 12) {
            return "in " . Numbers::format($months) . " months";
        } else {
            return "in " . Numbers::format($years) . " years";
        }
    }
}
?>