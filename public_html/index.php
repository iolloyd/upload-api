<?php
session_start();

define("ROOT", dirname(dirname(__FILE__)));
define("LIB", ROOT . '/lib');
define("ROUTES", ROOT . '/routes');
define("CONFIG", ROOT . '/config');

require_once ROOT . '/vendor/autoload.php';
require_once LIB . '/rb.phar';
require_once LIB . '/config.php';

$config = config(CONFIG . '/config.ini');
$host = $config('db')['host'];
$dbname = $config('db')['dbname'];
R::setup(
    "mysql:host=$host;dbname=$dbname",
    $config('db')['user'], 
    $config('db')['password']);

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
    if ($uri !== '/login' 
        && !(isset($_SESSION['user']))
    ) {
        $app->redirect('/login');
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

require_once ROUTES . "/videos.php";
require_once ROUTES . "/auth.php";
require_once ROUTES . "/forms.php";
require_once ROUTES . "/admin.php";
require_once ROUTES . "/tags.php";

$app->run();
