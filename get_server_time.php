<?php
header('Content-Type: application/json');
date_default_timezone_set('Africa/Cairo');

echo json_encode([
    'time' => date('H:i:s'),
    'date' => date('Y-m-d'),
    'datetime' => date('Y-m-d H:i:s')
]);