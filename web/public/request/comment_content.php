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
    "to_mod" => $_GET['id'],

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
    
if(!$select->mod_exists($request->to_mod)) 
    $request->progress->message = "This mod does not exist.";

if($request->progress->message == "") {
    $stmt = $__db->prepare("INSERT INTO content_comments (comment_author, comment_text, comment_to) VALUES (:comment_author, :comment_text, :comment_to)");
    $stmt->bindParam(":comment_author", $request->username);
    $stmt->bindParam(":comment_text", $request->comment);
    $stmt->bindParam(":comment_to", $request->to_mod);
    $stmt->execute();
    
    header("Location: /content/" . $_GET['id']);
} else {
    $_SESSION['alert'] = (object) [
        "message" => $request->progress->message,
        "type" => 1,
    ];
    
    header("Location: /user/" . $_GET['profile']);
}