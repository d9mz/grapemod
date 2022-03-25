<?php
session_start();

require("../../app/vendor/autoload.php");
require($_SERVER['DOCUMENT_ROOT'] . "/protected/config.inc.php");
require($_SERVER['DOCUMENT_ROOT'] . "/protected/db.php");
require($_SERVER['DOCUMENT_ROOT'] . "/protected/select.php");

$select = new \Database\Select($__db);

$request = (object) [
    "username" => $_SESSION['grapename'],
    "comment" => $_POST['comment-grape'],
    "to_user" => $_GET['profile'],

    "progress" => (object) [
        "code"    => 200, // HTTP Response Code 
        "message" => "",
    ],
];

if(empty(trim($request->comment)))
    $request->progress->message = "Your comment cannot be empty.";

if(strlen($request->comment) > 149) 
    $request->progress->message = "Your comment is too long.";

if(!isset($_SESSION['grapename']))
    $request->progress->message = "Your have to be logged in to upload content!";
    
if(!$select->user_exists($request->to_user)) 
    $request->progress->message = "This user does not exist.";

if($request->progress->message == "") {
    $stmt = $__db->prepare("INSERT INTO profile_comments (comment_author, comment_text, comment_to) VALUES (:comment_author, :comment_text, :comment_to)");
    $stmt->bindParam(":comment_author", $request->username);
    $stmt->bindParam(":comment_text", $request->comment);
    $stmt->bindParam(":comment_to", $request->to_user);
    $stmt->execute();
    
    header("Location: /user/" . $_GET['profile']);
} else {
    $_SESSION['alert'] = (object) [
        "message" => $request->progress->message,
        "type" => 1,
    ];
    
    header("Location: /user/" . $_GET['profile']);
}