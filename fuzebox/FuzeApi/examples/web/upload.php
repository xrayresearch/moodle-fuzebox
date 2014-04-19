<?php
/**
 * Upload proxy for media upload functionality
 */
set_include_path(get_include_path() . PATH_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..');

require_once 'Fuze/Client.php';
require_once 'lib.php';

function render_response($data) {
    echo '<textarea>' . json_encode($data) . '</textarea>';
    exit;
}

function render_error($message = 'oops') {
    return render_response(array(
        'error' => $message,
    ));
}

$config = load_config('./fuze.ini');
init_session();
if (!$_POST) {
    render_error('I get POSTs only');
}

if (!$_SESSION['has_user']) {
    render_error('no user sesssion present');
}

if (!isset($_FILES['media']) || $_FILES['media']['error']) {
    render_error('file was not uploaded: ' . print_r($_FILES, true));
}

$media = $_FILES['media'];

$ret = false;
try {
    $client = getFuzeClient($config['fuze.url']);

    $ret = $client->uploadMedia($media['tmp_name'], $media['name'], $_POST['meetingId']);
} catch (Exception $e) {
    render_error($e->getMessage());
}
if (!$ret) {
    render_error('upload to fuze failed');
}

render_response($ret);
