<?php
session_start();

require("../../app/vendor/autoload.php");
require($_SERVER['DOCUMENT_ROOT'] . "/protected/config.inc.php");
require($_SERVER['DOCUMENT_ROOT'] . "/protected/db.php");

$request = (object) [
    "username" => trim(strtolower($_POST['username-grape'])),
    "password" => $_POST['password-grape'],
    "password_hash" => password_hash($_POST['password-grape'], PASSWORD_DEFAULT),

    "progress" => (object) [
        "code"    => 200, // HTTP Response Code 
        "message" => "",
    ],
];

if(empty($request->username))
    $request->progress->message = "Your username cannot be empty.";
    
$stmt = $__db->prepare("SELECT password FROM users WHERE username = :username");
$stmt->bindParam(":username", $request->username);
$stmt->execute();

if(!$stmt->rowCount()){ 
    { $request->progress->message = "Incorrect username or password!"; } }

$row = $stmt->fetch(PDO::FETCH_ASSOC);
if(!isset($row['password'])) 
    { $request->progress->message = "Incorrect username or password!"; } 
else 
    { $request->returned_password = $row['password']; }

if(!@password_verify($request->password, $request->returned_password)) 
    { $request->progress->message = "Incorrect username or password!"; }
    
if($request->progress->message == "") {
    $_SESSION['grapename'] = $request->username;
    header("Location: /");
} else {
    $_SESSION['alert'] = (object) [
        "message" => $request->progress->message,
        "type" => 1,
    ];
    
    header("Location: /sign_in");
}

// echo json_encode($request, JSON_PRETTY_PRINT);