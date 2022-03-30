<?php
session_start();

require("../app/vendor/autoload.php");
require($_SERVER['DOCUMENT_ROOT'] . "/protected/config.inc.php");
require($_SERVER['DOCUMENT_ROOT'] . "/protected/db.php");
require($_SERVER['DOCUMENT_ROOT'] . "/protected/select.php");

$router = new \Bramus\Router\Router();
$loader = new \Twig\Loader\FilesystemLoader('twig/templates');
$twig = new \Twig\Environment($loader);
$select = new \Database\Select($__db);

// Define a filter using an anonymous function
$filter = new \Twig\TwigFilter('remove_extension', function ($string) {
    // Return file name without extension
    return pathinfo($string, PATHINFO_FILENAME);
});

// Add it to twig instance, assuming it is stored in $twig
$twig->addFilter($filter);

function find_rand_header_file($banner_dir) {
    $files = glob($banner_dir . '/*.*');
    $file = array_rand($files);
    return "/" . $files[$file];
}

$config->header_config->pane_file = find_rand_header_file($config->header_config->pane_dir);

$router->get('/', function() use ($twig, $__db) { 
    $content_search = $__db->prepare("SELECT * FROM content ORDER BY id DESC LIMIT 10");
    $content_search->execute();
    
    while($content = $content_search->fetch(PDO::FETCH_ASSOC)) { 
        $contents[] = $content;
    }

    $contents['rows'] = $content_search->rowCount();

    $user_search = $__db->prepare("SELECT * FROM users ORDER BY id DESC LIMIT 5");
    $user_search->execute();
    
    while($user = $user_search->fetch(PDO::FETCH_ASSOC)) { 
        $users[] = $user;
    }

    $users['rows'] = $user_search->rowCount();

    echo $twig->render('index.twig', array("mods" => $contents, "users" => $users));
});

$router->get('/sign_up', function() use ($twig) { 
    echo $twig->render('sign_up.twig', array());
});

$router->get('/games', function() use ($twig, $__db) { 
    $content_search = $__db->prepare("SELECT * FROM content ORDER BY content_category DESC LIMIT 100");
    $content_search->execute();
    
    while($content = $content_search->fetch(PDO::FETCH_ASSOC)) { 
        $contents[] = $content;
    }

    $contents['rows'] = $content_search->rowCount();

    $games = array_diff(scandir($_SERVER['DOCUMENT_ROOT'] . "/img/gametiles/"), array('..', '.'));
    $game_count = [];

    foreach($games as $game) {
        $game = pathinfo($game, PATHINFO_FILENAME);
        $mod_search = $__db->prepare("SELECT * FROM content WHERE content_category = :game LIMIT 100");
        $mod_search->bindParam(":game", $game);
        $mod_search->execute();    

        array_push($game_count, [$game, $mod_search->rowCount()]);
    }

    echo $twig->render('games.twig', array(
        "emblems"    => $games,
        "game_count" => $game_count, 
        "mods"       => $contents,
    ));
});

$router->get('/sign_in', function() use ($twig) { 
    echo $twig->render('sign_in.twig', array());
});

$router->get('/create_content', function() use ($twig) { 
    echo $twig->render('create_content.twig', array());
});

$router->get('/search', function() use ($twig, $__db) { 
    if(isset($_GET['category']) && !empty(trim($_GET['category']))) {
        $category = "%" . $_GET['category'] . "%";

        $content_search = $__db->prepare("SELECT * FROM content WHERE content_category LIKE :category ORDER BY id DESC LIMIT 10");
        $content_search->bindParam(":category", $category);
        $content_search->execute();
        
        while($content = $content_search->fetch(PDO::FETCH_ASSOC)) { 
            $contents[] = $content;
        }

        $contents['rows'] = $content_search->rowCount();
    } else if(isset($_GET['name']) && !empty(trim($_GET['name']))) {
        $name = "%" . $_GET['name'] . "%";

        $content_search = $__db->prepare("SELECT * FROM content WHERE content_title LIKE :content_title ORDER BY id DESC LIMIT 10");
        $content_search->bindParam(":content_title", $name);
        $content_search->execute();
        
        while($content = $content_search->fetch(PDO::FETCH_ASSOC)) { 
            $contents[] = $content;
        }

        $contents['rows'] = $content_search->rowCount();
    }
    
    echo $twig->render('search.twig', array("mods" => $contents, "emblem" => @$_GET['category']));
});

$router->get('/user/(\w+)', function($username) use ($twig, $select, $__db) { 
    if($select->user_exists($username)) {
        $user_info = $select->fetch_table_singlerow($username, "users", "username"); 

        $comments_search = $__db->prepare("SELECT * FROM profile_comments WHERE comment_to = :username ORDER BY id DESC");
        $comments_search->bindParam(":username", $user_info['username']);
        $comments_search->execute();
        
        while($comment = $comments_search->fetch(PDO::FETCH_ASSOC)) { 
            $comments[] = $comment;
        }
    
        $comments['rows'] = $comments_search->rowCount();

        $content_search = $__db->prepare("SELECT * FROM content WHERE content_author = :username ORDER BY id DESC");
        $content_search->bindParam(":username", $user_info['username']);
        $content_search->execute();
        
        while($content = $content_search->fetch(PDO::FETCH_ASSOC)) { 
            $contents[] = $content;
        }
    
        $contents['rows'] = $content_search->rowCount();

        echo $twig->render('user.twig', array("user" => $user_info, "comments" => $comments, "mods" => $contents));
    } else {
        $_SESSION['alert'] = (object) [
            "message" => '<div><b>This user does not exist!</b></div>
                          <span>Make sure you didn\'t mispell.</span>',
            "type" => 1,
        ];
        
        header("Location: /");
    }
});

$router->get('/content/(\w+)', function($mod_id) use ($twig, $select, $__db) { 
    if($select->mod_exists($mod_id)) {
        $mod_info = $select->fetch_table_singlerow($mod_id, "content", "id"); 

        $comments_search = $__db->prepare("SELECT * FROM content_comments WHERE comment_to = :id ORDER BY id DESC");
        $comments_search->bindParam(":id", $mod_info['id']);
        $comments_search->execute();
        
        while($comment = $comments_search->fetch(PDO::FETCH_ASSOC)) { 
            $comments[] = $comment;
        }
    
        $comments['rows'] = $comments_search->rowCount();

        echo $twig->render('view_content.twig', array("mod" => $mod_info, "comments" => $comments));
    } else {
        $_SESSION['alert'] = (object) [
            "message" => '<div><b>This user does not exist!</b></div>
                          <span>Make sure you didn\'t mispell.</span>',
            "type" => 1,
        ];
        
        header("Location: /");
    }
});

$router->set404(function() use ($twig) {
    echo "404";
});

$twig->addGlobal('config',   $config);
$twig->addGlobal('session',  $_SESSION);
$twig->addGlobal('args',     $_GET);
$twig->addGlobal('referrer', @$_SERVER['HTTP_REFERER']);
$router->run();

unset($_SESSION['alert']);