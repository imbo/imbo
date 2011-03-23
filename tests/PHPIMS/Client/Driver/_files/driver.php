<?php
if (isset($_REQUEST['sleep'])) {
    sleep($_REQUEST['sleep']);
}

$data = array(
    'method' => $_SERVER['REQUEST_METHOD'],
);

switch ($data['method']) {
    case 'POST':
        $data['data'] = $_POST;
        break;
    case 'GET':
        $data['data'] = $_GET;
        break;
}

if (isset($_FILES)) {
    $data['files'] = $_FILES;
}

print(serialize($data));