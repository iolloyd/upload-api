<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../vendor/autoload.php';

////////////////
// Doctrine setup
////////////////
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
$entities = array('../lib/entities');
$dbParams = array(
    'driver'   => 'pdo_mysql',
    'user'     => 'root',
    'password' => 'root',
    'dbname'   => 'foo',
);

$isDevMode = true;
$dbConfig = Setup::createAnnotationMetadataConfiguration($entities, $isDevMode);
$entityManager = EntityManager::create($dbParams, $dbConfig);

////////////////
// App setup
////////////////
$config = array(
    'debug' => true,
    'mode' => 'development',
    'view' => new \Slim\Views\Twig(),
);

$app = new \Slim\Slim();
$app->config($config);
$loader = new Twig_Loader_String();
$twig = new Twig_Environment($loader);
$view = $app->view();
$view->parserOptions = array( 
    'debug' => true,
    'cache' => dirname(dirname(__FILE__)).'/cache',
);

$env = array('app' => $app);
$view->parserExtensions = array(
    new \Slim\Views\TwigExtension(),
);

function getVideos() {
    return array(
        'one', 'two', 'three'
        );
}

////////////////
// Routing setup
////////////////
$app->get('/videos', function() use ($app) {
    $videos = getVideos();
    $app->render('list.html', array('videos' => $videos));
});

$app->get('/videos/new', function() use ($app) {
    $app->render('new.html'); 
});

$app->get('/videos/:id', function($id) use ($app) {
    $data = array('id' => $id);
    $app->render('video.html', $data); 
});

$app->post('/videos/upload', function() use ($app) {
    $video = $_FILES['video']['tmp_name'];
    file_put_contents('./saved.jpg', file_get_contents($video));
    echo 'You uploaded ...</br>';
    echo "<img src='http://vm.cloud.xxx/saved.jpg'/>";
});
$app->run();
