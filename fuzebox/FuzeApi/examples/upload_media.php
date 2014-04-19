<?php
/**
 * This script uploads a media file to Fuze and optonally attaches it to a meeting
 * Demonstrates usage of Fuze_Client::uploadMedia()
 */
// TODO: move this to a config somewhere, probably share the web demo fuze.ini
define('API_ENDPOINT_URL', 'https://partnerdev.fuzemeeting.com/');

set_include_path(get_include_path() . PATH_SEPARATOR . '..');
require_once 'Fuze/Client.php';

$opts = getopt('f:', array(
    'meeting-id:',
    'file-name:',
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
    echo "Usage: {$scriptName} -f media_file --email user_email --password user_password\n";
    echo "                      --pkey partner_key --ekey encryption_key [--file-name name] \n";
    echo "                      [--meeting-id ID] [--url url]\n\n";
    echo "       {$scriptName} --help\n";
    echo "\nThis script will upload media_file to the Fuze backend, optionally\n";
    echo "attaching it to a meeting.\n\n";
    echo "If meeting-id is specified, media will be added to this meeting.\n";
    echo "If fine-name is specified, media will be uploaded under the fine name provided\n";
    echo "instead of the local filesystem name\n";
    echo "url allows for an alternative backend to be used\n\n";
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
foreach (array('f', 'email', 'password', 'pkey', 'ekey') as $k) {
    if (!isset($opts[$k]) || !$opts[$k]) {
        $missing[] = $k;
    }
}
if (count($missing)) {
    bail('Missing options: ' . implode(', ', $missing));
}

$filePathName = @realpath($opts['f']);
if (!is_readable($filePathName)) {
    bail("File {$filePathName} missing or not readable");
}

try {

    if (isset($opts['url']) && $opts['url']) {
        $url = $opts['url'];
    } else {
        $url = API_ENDPOINT_URL;
    }


    echo "Instantiating client ({$url})... \n";
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

    $fname = (isset($opts['file-name']) && $opts['file-name']) ? $opts['file-name'] : null;
    $meeting_id = (isset($opts['meeting-id']) && $opts['meeting-id']) ? $opts['meeting-id'] : null;
    $res = $fuze->uploadMedia($filePathName, $fname, $meeting_id);
    echo "Media uploaded. ID: {$res['mediaId']} (code: {$res['code']})\n";
} catch (Exception $e) {
    echo "An error occurred: {$e->getMessage()}\n";
    exit(1);
}
