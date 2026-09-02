<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Per-file import cap
    |--------------------------------------------------------------------------
    |
    | A batch has never delivered more than 168 files: past that, every
    | remaining file fails with "getaddrinfo() thread failed to start" and the
    | batch still reports success. 14 batches across 9 customers have been
    | truncated this way. This is a safety cap, not a tuning knob, so it applies
    | even when the A/B test below is running.
    |
    */

    'per_file_max_files' => (int) env('TRANSFER_PER_FILE_MAX', 150),

    /*
    |--------------------------------------------------------------------------
    | Archive threshold
    |--------------------------------------------------------------------------
    |
    | Per-file import pays a fixed cost per file, measured at ~11.5s on live
    | batches, because a small file never lives long enough for the connection
    | to reach full speed. Measured batches of ~5MB files ran at 0.55 MB/s
    | against ~13 MB/s for the same content as one archive. Below this average
    | file size the archive wins, so take the archive.
    |
    */

    'archive_below_avg_bytes' => (int) env('TRANSFER_ARCHIVE_AVG_MB', 100) * 1024 * 1024,

    /*
    |--------------------------------------------------------------------------
    | Strategy A/B test
    |--------------------------------------------------------------------------
    |
    | When on, transfers that the cap above has not already decided are assigned
    | an arm at random and the choice is logged, so the threshold can be set from
    | measured throughput instead of guessed. Turn off once the data is in.
    |
    */

    'ab_test' => (bool) env('TRANSFER_AB_TEST', false),

];
