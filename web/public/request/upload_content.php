<?php
session_start();

require("../../app/vendor/autoload.php");
require($_SERVER['DOCUMENT_ROOT'] . "/protected/config.inc.php");
require($_SERVER['DOCUMENT_ROOT'] . "/protected/db.php");

$request = (object) [
    "mod_title" => $_POST['mod-title-grape'],
    "mod_game" => $_POST['game'],
    "mod_description" => $_POST['mod-description-grape'],
    "mod_tags" => $_POST['tags-grape'],

    "progress" => (object) [
        "code"    => 200, // HTTP Response Code 
        "message" => "",
    ],
];

// print_r($request);

if(!isset($_SESSION['grapename']))
    $request->progress->message = "Your have to be logged in to upload content!";

if(empty($request->mod_title))
    $request->progress->message = "Your mod's title cannot be empty.";

if(empty($request->mod_game))
    $request->progress->message = "Your mod's file input cannot be empty.";

if(empty($request->mod_description))
    $request->progress->message = "Your mod's description cannot be empty.";

if(empty($request->mod_tags))
    $request->progress->message = "Your mod's tags cannot be empty.";
    
$target_dir = "../content/mod/";
$imageFileType = strtolower(pathinfo($_FILES["mod-grape"]["name"],PATHINFO_EXTENSION));
$target_name = preg_replace("/[^a-zA-Z0-9.]/", "", $_FILES["mod-grape"]["name"]);

$target_file = $target_dir . $target_name;

if (file_exists($target_file)) {
    $request->progress->message = "This file already exists!";
}

if ($_FILES["mod-grape"]["size"] > 10000000) {
    $request->progress->message = "This file is too big.";
}

if($imageFileType != "zip") {
    $request->progress->message = "Only ZIP files are allowed.";
}

if (!move_uploaded_file($_FILES["mod-grape"]["tmp_name"], $target_file)) {
    $request->progress->message = "Sorry, there was an error uploading your file.";
}

if($request->progress->message == "") {
    $stmt = $__db->prepare(
        "INSERT INTO content 
             (content_type, content_description, content_title, content_category, content_tags, content_author, content_file) 
         VALUES 
             ('game', :content_description, :content_title, :content_category, :content_tags, :content_author, :content_file)
        "
     );
 
    $stmt->bindParam(":content_description", $request->mod_description);
    $stmt->bindParam(":content_title", $request->mod_title);
    $stmt->bindParam(":content_category", $request->mod_game);
    $stmt->bindParam(":content_tags", $request->mod_tags);
    $stmt->bindParam(":content_file", $target_name);
    $stmt->bindParam(":content_author", $_SESSION['grapename']);
    $stmt->execute();

    $_SESSION['alert'] = (object) [
        "message" => '<div><b>Your mod has successfully been uploaded!</b></div>
                      <span>Wait for it to be approved by our moderators! This may take a few hours.</span>',
        "type" => 0,
    ];
    
    //print_r($_FILES);
    header("Location: /");
} else {
    $_SESSION['alert'] = (object) [
        "message" => $request->progress->message,
        "type" => 1,
    ];
    
    header("Location: /");
}

// echo json_encode($request, JSON_PRETTY_PRINT);