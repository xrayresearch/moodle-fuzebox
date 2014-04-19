<?php
/**
 * This file tries to provide a minimalistic example of starting a Fuze Meeting
 * via Partner API. All that is required is a valid partner key pair. The script
 * will schedule a meeting using the user credentials provided and print a URL
 * to be opened in a browser.
 */
define('API_ENDPOINT_URL', 'https://partnerdev.fuzemeeting.com/');

set_include_path(get_include_path() . PATH_SEPARATOR . '..');
require_once 'Fuze/Client.php';

$opts = getopt('', array(
    'url:',
    'email:',
    'password:',
    'pkey:',
    'ekey:',
    'help'
));

$scriptName = $_SERVER['argv'][0];

function usage() {
    global $scriptName;
    echo "\n";
    echo "Usage: {$scriptName} --email user_email --password user_password\n";
    echo "                      --pkey partner_key --ekey encryption_key\n";
    echo "                      [--meeting-id ID] [--url url]\n\n";
    echo "       {$scriptName} --help\n";
    echo " This script will create (schedule) a meeting, obtain and print\n";
    echo " a signed launch URL for the creted meeting. The URL can be opened\n";
    echo " directly in a browser to log in the first host.\n";
}

function bail($message=null) {
    global $scriptName;
    if ($message) {
        echo "{$message}\n";
    }
    echo "Try {$scriptName} --help\n";
    exit;
}

if (array_key_exists('help', $opts)) {
    usage();
    exit;
}

$missing = array();
foreach (array('email', 'password', 'pkey', 'ekey') as $k) {
    if (!isset($opts[$k]) || !$opts[$k]) {
        $missing[] = $k;
    }
}
if (count($missing)) {
    bail('Missing options: ' . implode(', ', $missing));
}


try {

    if (isset($opts['url']) && $opts['url']) {
        $url = $opts['url'];
    } else {
        $url = API_ENDPOINT_URL;
    }
    echo "Using {$url}\n";

    echo "Instantiating client ... \n";
    $fuze = new Fuze_Client($url, $opts['pkey'], $opts['ekey']);

    echo "Checking keys ... ";
    $keysOk = $fuze->checkKeys();
    echo ($keysOk ? 'passed.' : 'failed!') . "\n";
    if (!$keysOk) {
        exit(1);
    }
        echo "Signing in with user account ... \n";
    $result = $fuze->signin(array(
        'email' => $opts['email'],
        'password' => $opts['password']
    ));

    // The client should be signed in here, $fuze->isSignedIn() must be true
    if (!$fuze->isSignedIn()) {
        echo "Signing in failed! See signin result below:\n";
        var_dump($result);
        exit(1);
    }

    echo "Scheduling a meeting ... \n";
    $result = $fuze->scheduleMeeting(array(
        'sendemail' => 'All',
        'includetollfree' => true,
        'includeinternationaldial' => true,
        'starttime' => strftime("%a, %d %b %Y %H:%M:%S %z", time() + 3600), // After 1 hour
        'endtime' => strftime("%a, %d %b %Y %H:%M:%S %z", time() + 3600*2), // After 2 hours
        'subject' => "This is a test scheduled meeting",
        'invitationtext' => "Wellcome to my test meeting",
        'invitees' => array(),
    ));

    // Get a fresh token, generate a working url
    echo "Requesting a launch url ...\n";
    $url = $fuze->getSignedLaunchURL($result->meetingid);

    // And here we should have the url for logging in to the meeting as host
    echo "Load the following url in a browser to join the first host to the meeting:\n";
    echo "{$url}\n";

    exit(0);
} catch (Exception $e) {
    echo "An error occurred: {$e->getMessage()}\n";
    exit(1);
}