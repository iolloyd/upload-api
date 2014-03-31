<?php
session_start();
error_reporting(E_ALL); ini_set('display_errors', true);
function config($configFile) {
    $info = parse_ini_file($configFile, true);

    return function($key) use ($info) {
        if (isset($info[$key])) {
            return $info[$key];
        }
        return "";
    };
}
$configFile = dirname(dirname(__FILE__)).'/config/config.ini';
$config = config($configFile);


require_once '../vendor/autoload.php';
require_once 'rb.phar';

R::setup();

class jsonSlim extends \Slim\Slim {
    function json($data) {
        $this->response->headers->set('Content-Type', 'application/json');
        echo json_encode($data);        
    } 
}
$app = new jsonSlim();
$app->config(array(
    'debug' => true,
    'mode' => 'development',
    'view' => new \Slim\Views\Twig(),
));
$app->hook('slim.before.router', function() use ($app) {
    $uri = $_SERVER['REQUEST_URI']; 
    if ($uri !== '/login') {
        if (!(isset($_SESSION['user']))) {
            $app->redirect('/login');
            die;
        }
    }
});

$loader = new Twig_Loader_String();
$twig = new Twig_Environment($loader);
$view = $app->view();
$view->parserOptions = array( 
    'debug' => true,
    'cache' => dirname(dirname(__FILE__)).'/cache',
);

$view->parserExtensions = array(
    new \Slim\Views\TwigExtension(),
);

$app->get('/', function() use ($app) {
    echo 'ok';
});

require_once "./routes/videos.php";
require_once "./routes/auth.php";

$app->run();
