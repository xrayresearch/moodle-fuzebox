<?php
/**
 * AJAX handler for Fuze Partner API in-browser demo
 *
 * This aims to be a simple AJAX backend for using the Fuze Partner API. The code
 * does not do any serious error checking or validation -- it's basically a
 * proxy to the Partner API, keeping the key pair safe and implementing
 * en/de -cryption.
 *
 * Note: some calls may take a long time. max_execution_time should be increased
 *       if needed.
 *
 */

// Include path sorcery. If Fuze is on the include path,
// this should be commented out or removed
set_include_path(get_include_path() . PATH_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..');

require_once 'Fuze/Client.php';
require_once 'lib.php';

$config = load_config('./fuze.ini');
if (!$config) {
    echo 'Unable to load config!';
    exit;
}

init_session();


function ajax_status() {
    global $config;
    $pkStatus = false;
    $userStatus = false;
    $userEmail = '';
    $pk = '';
    $fuze = null;
        if ($_SESSION['has_partner']) {
        try {
            $fuze = getFuzeClient($config['fuze.url']);
            if ($fuze->checkKeys()) {
                $pkStatus = true;
                $pk = $_SESSION['partner_key'];
            }
        } catch (Exception $e) {
            // pass
        }
    }
        if ($pkStatus && $_SESSION['has_user']) {
        $res = $fuze->getAccountInfo();
        if ($res->code == 200) {
            $userStatus = true;
            $userEmail = $_SESSION['user_email'];
        }
    }
    if ($pkStatus && $userStatus) {
        $headers['partnerkey'] = $pk;
        $headers['token'] = $fuze->getUserSession();
        $headers['session'] = $fuze->getRawSessionToken();
    }
    return array(
        'status' => array(
            'partner' => $pkStatus,
            'user' => $userStatus,
        ),
        'user_email' => $userEmail,
        'pkey' => $pk,
        'fuzeurl' => $config['fuze.url'],
    );
}

function ajax_clearcredentials() {
    $_SESSION['has_partner'] = false;
    $_SESSION['has_user'] = false;
    return array('success' => true);
}

function ajax_signinpartner() {
    global $config;
    $client = new Fuze_Client(
        $config['fuze.url'],
        $_POST['partner_key'],
        $_POST['encryption_key']
    );
        if (!$client->checkKeys()) {
        return array(
            'error' => 'Keys are invalid',
        );
    }
        // Store the keys in the session
    $_SESSION['has_partner'] = true;
    $_SESSION['partner_key'] = $_POST['partner_key'];
    $_SESSION['encryption_key'] = $_POST['encryption_key'];
        return array(
        'success' => True,
        'message' => 'Key pair appears valid',
        'pk' => $_POST['partner_key'],
    );
}

function ajax_signin() {
    global $config;
    $client = getFuzeClient($config['fuze.url']);
    $params = array(
        'email' => $_POST['email'],
        'password' => $_POST['password']
    );
    $res = $client->signin($params);
    if (!$res || $res->code != 200) {
        return array(
            'error' => $res ? $res->message : 'Error communicating with the Fuze API'
        );
    }

    $_SESSION['user_token'] = $client->getUserSession();
    $_SESSION['user_email'] = $_POST['email'];
    $_SESSION['has_user'] = true;
    return array(
        'success' => True,
        'message' => 'Signed in successfully',
        'email' => $_POST['email'],
    );
}

function ajax_signup() {
    global $config;
    $client = getFuzeClient($config['fuze.url']);
    $params = array(
        'email' => $_POST['email'],
        'password' => $_POST['password'],
        'firstname' => $_POST['firstname'],
        'lastname' => $_POST['lastname'],
        'phone' => $_POST['phone'],
        'welcome' => _checkboxValue('welcome'),
        'package' => _getPostParam('package', 'PROMO'),
    );

    $res = $client->signup($params);
    if (!$res || $res->code != 200) {
        return array(
            'error' => $res ? $res->message : 'Error communicating with the Fuze API'
        );
    }

    // yeey!
    return array(
        'success' => True,
        'message' => 'User signed up successfully'
    );
}

function ajax_subscribe() {
    global $config;
    $client = getFuzeClient($config['fuze.url']);

    $packages = _getPostParam('packages', '');

    $res = $client->subscribe(explode(',', $packages));

    if (!$res || $res->code != 200) {
        return array(
            'error' => $res ? $res->message : 'Error communicating with the Fuze API'
        );
    }
    return array(
        'success' => True,
        'message' => 'User subscribed successfully',
        'packages' => $res->packages,
    );
}

function ajax_schedule() {
    global $config;
    $client = getFuzeClient($config['fuze.url']);

    $invitees = array();
    $postInvitees = _getPostParam('invitees');
    if ($postInvitees) {
        $postInviteesArray = explode(',', $postInvitees);
        foreach ($postInvitees as $invitee) {
            $invitees[] = trim($invitee);
        }
    }

    $params = array(
        'sendemail' => _checkboxValue('sendemail'),
        'includetollfree' => _checkboxValue('includetollfree'),
        'includeinternationaldial' => _checkboxValue('includeinternationaldial'),
        'starttime' => $_POST['starttime'],
        'endtime' => $_POST['endtime'],
        'subject' => empty($_POST['subject']) ? '' : $_POST['subject'],
        'invitationtext' => _getPostParam('invitationtext'),
        'invitees' => $invitees,
        'timezone' => _getPostParam('timezone'),
        'autorecording' => _checkboxValue('autorecording'),
        'webinar' => _checkboxValue('webinar'),
    );

    if ($_POST['startnow']) {
        $res = $client->startMeeting($params);
    } else {
        $res = $client->scheduleMeeting($params);
    }

    if (!$res || $res->code != 200) {
        return array(
            'error' => $res ? $res->message : 'Error communicating with the Fuze API'
        );
    }

    $ret = array(
        'success' => True,
        'message' => 'Meeting was scheduled',
        'meetingid' => $res->meetingid,
    );
    if ($_POST['startnow']) {
        $ret['signedlaunchurl'] = $res->signedlaunchurl;
    }
    return $ret;
}

function ajax_launch() {
    global $config;
    $client = getFuzeClient($config['fuze.url']);

    $url = $client->getSignedLaunchURL($_POST['meetingid']);

    return array(
        'success' => True,
        'message' => 'Meeting was scheduled',
        'url' => $url
    );
}

function ajax_list() {
    global $config;
    $client = getFuzeClient($config['fuze.url']);

    $params = array();
    if ( !empty($_POST['page']) ) {
        $params['page'] = intval($_POST['page']);
    }
    if ( !empty($_POST['count']) ) {
        $params['count'] = intval($_POST['count']);
    }
    if ( !empty($_POST['startdate']) ) {
        $params['startdate'] = $_POST['startdate'];
    }
    if ( !empty($_POST['enddate']) ) {
        $params['enddate'] = $_POST['enddate'];
    }

    $result = $client->listMeetings($params);

    return array(
        'success' => True,
        'message' => $result->message,
        'total' => $result->total,
        'count' => $result->count,
        'meetings' => $result->meetings,
    );
}

function ajax_listpackages() {
    global $config;
    $client = getFuzeClient($config['fuze.url']);
    $result = $client->getPackages();
    return array(
        'success' => true,
        'packages' => $result->packages,
    );
}

function ajax_generic() {
    global $config;
    $client = getFuzeClient($config['fuze.url']);

    $params = json_decode($_POST['params'], true);
    $result = $client->call($_POST['method'], $params);

    return array(
        'response' => $result
    );
}


//
// main() /sort of/ is below
//
if (!$_POST || !isset($_POST['action']) || empty($_POST['action'])) {
    echo json_encode(array(
        'error'=> 'Unknown action'
    ));
    exit;
}

$action = $_POST['action'];
$fn = "ajax_{$action}";
if (!function_exists($fn)) {
    echo json_encode(array(
        'error' => 'Unknown action ' . $action
    ));
    exit;
}

try {

    $result = $fn();
    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode(array(
        'error' => 'PHP Server Error: ' . $e->getMessage(),
    ));
}

