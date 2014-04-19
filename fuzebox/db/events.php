<?php

/* * ************************************************************ 
 * 
 * Date: Mar 6, 2013
 * version: 1.0
 * programmer: Shani Mahadeva <satyashani@gmail.com>
 * Description:   
 * PHP file events
 * 
 * 
 * *************************************************************** */
$handlers = array (
    'user_logout' => array (
        'handlerfile'      => '/mod/fuzebox/lib.php',
        'handlerfunction'  => 'fuze_user_logout',
        'schedule'         => 'instant',
        'internal'         => 1,
    ),
    "user_deleted" => array (
        'handlerfile'      => '/mod/fuzebox/lib.php',
        'handlerfunction'  => 'fuze_user_delete',
        'schedule'         => 'instant',
        'internal'         => 1,
    ),
);