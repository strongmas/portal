<?php

return [

    // Where backup:run sends failure alerts and the weekly report.
    'alert_email' => env('BACKUP_ALERT_EMAIL', env('MAIL_FROM_ADDRESS')),

    // Daily database dumps are kept this long; the first dump of each month
    // is kept for twelve months on top of that.
    'keep_days' => (int) env('BACKUP_KEEP_DAYS', 30),

];
