<?php
Class Numbers {
    // Source - https://stackoverflow.com/a/47763805
    // Posted by Clinton Canarias
    // Retrieved 2026-08-07, License - CC BY-SA 3.0
    public static function format($n) {
        if ($n < 900) {
            $n_format = number_format($n);
        } else if ($n < 900000) {
            $n_format = number_format($n / 1000) . 'K';
        } else if ($n < 900000000) {
            $n_format = number_format($n / 1000000) . 'M';
        } else if ($n < 900000000000) {
            $n_format = number_format($n / 1000000000) . 'B';
        } else {
            $n_format = number_format($n / 1000000000000) . 'T';
        }
        return $n_format;
    }

    // Source - https://stackoverflow.com/a/5501447
    public static function filesize($bytes) {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
	}
}