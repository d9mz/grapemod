<?php
session_start();

require("../../app/vendor/autoload.php");
require($_SERVER['DOCUMENT_ROOT'] . "/protected/config.inc.php");
require($_SERVER['DOCUMENT_ROOT'] . "/protected/db.php");

$request = (object) [
    "username" => trim(strtolower($_POST['username-grape'])),
    "email"    => $_POST['email-grape'],
    "password" => $_POST['password-grape'],
    "password_hash" => password_hash($_POST['password-grape'], PASSWORD_DEFAULT),

    "progress" => (object) [
        "code"    => 200, // HTTP Response Code 
        "message" => "",
    ],
];

if(empty($request->username))
    $request->progress->message = "Your username cannot be empty.";

if(empty($request->password))
    $request->progress->message = "Your password cannot be empty.";

if(!preg_match('/^[\w]{5,20}+$/', $request->username))
    $request->progress->message = "Your username is too long/short or it contains special characters.";

$stmt = $__db->prepare("SELECT username FROM users WHERE username = lower(:username)");
$stmt->bindParam(":username", $request->username);
$stmt->execute();
if($stmt->rowCount()) 
    { $request->progress->message = "There's already a user with that same username!"; }

if($request->progress->message == "") {
    $stmt = $__db->prepare("INSERT INTO users (username, password, email) VALUES (:username, :password, :email)");
    $stmt->bindParam(":username", $request->username);
    $stmt->bindParam(":password", $request->password_hash);
    $stmt->bindParam(":email",    $request->email);
    $stmt->execute();

    $_SESSION['grapename'] = $request->username;

    $_SESSION['alert'] = (object) [
        "message" => '<div><b>Welcome to GrapeMod!</b></div>
                      <span><a href="/settings" style="">View your settings</a> or join our <a href="#">Discord</a> for daily updates!</span>',
        "type" => 0,
    ];
    
    header("Location: /");
} else {
    $_SESSION['alert'] = (object) [
        "message" => $request->progress->message,
        "type" => 1,
    ];
    
    header("Location: /sign_up");
}

// echo json_encode($request, JSON_PRETTY_PRINT);