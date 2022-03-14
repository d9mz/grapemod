<?php
session_start();

require("../app/vendor/autoload.php");
require($_SERVER['DOCUMENT_ROOT'] . "/protected/config.inc.php");
require($_SERVER['DOCUMENT_ROOT'] . "/protected/db.php");

$router = new \Bramus\Router\Router();
$loader = new \Twig\Loader\FilesystemLoader('twig/templates');
$twig = new \Twig\Environment($loader);

function find_rand_header_file($banner_dir) {
    $files = glob($banner_dir . '/*.*');
    $file = array_rand($files);
    return "/" . $files[$file];
}

$config->header_config->pane_file = find_rand_header_file($config->header_config->pane_dir);

$router->get('/', function() use ($twig) { 
    echo $twig->render('index.twig', array());
});

$router->get('/sign_up', function() use ($twig) { 
    echo $twig->render('sign_up.twig', array());
});

$router->get('/sign_in', function() use ($twig) { 
    echo $twig->render('sign_in.twig', array());
});

$router->set404(function() use ($twig) {
    echo "404";
});

$twig->addGlobal('config',  $config);
$twig->addGlobal('session', $_SESSION);
$router->run();

unset($_SESSION['alert']);