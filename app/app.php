<?php
require_once __DIR__ . '/bootstrap.php';

$app = new Silex\Application();

$app->register(new \Silex\Provider\FormServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'translator.messages' => array(),
));
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../web/views',
));

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver' => 'pdo_mysql',
        'user' => 'root',
        'password' => 'fuckoff',
        'dbname' => 'silex',
        'charset' => 'utf8',
    ),
));

$schema = $app['db']->getSchemaManager();
if (!$schema->tablesExist('categories')) {
    $cats = new \Doctrine\DBAL\Schema\Table('categories');
    $cats->addColumn('id', 'integer', array('unsigned' => true, 'autoincrement' => true));
    $cats->setPrimaryKey(array('id'));
    $cats->addColumn('name', 'string', array('length' => 64));
    $cats->addUniqueIndex(array('name'));

    $schema->createTable($cats);

    $app['db']->insert('categories', array(
        'name' => 'sport'
    ));
    $app['db']->insert('categories', array(
        'name' => 'music'
    ));
}
if (!$schema->tablesExist('posts')) {
    $posts = new \Doctrine\DBAL\Schema\Table('posts');
    $posts->addColumn('id', 'integer', array('unsigned' => true, 'autoincrement' => true));
    $posts->setPrimaryKey(array('id'));
    $posts->addColumn('cat_id', 'integer');
    $posts->addColumn('title', 'string', array('length' => 64));
    $posts->addColumn('data', 'text');
    $posts->addColumn('date', 'datetime');

    $schema->createTable($posts);

    $app['db']->insert('posts', array(
        'title' => '1st sport news title',
        'data' => '1st sport news data',
        'cat_id' => 1,
        'date' => date('Y-m-d H:i:s')
    ));
    $app['db']->insert('posts', array(
        'title' => '1st music news title',
        'data' => '1st music news data',
        'cat_id' => 2,
        'date' => date('Y-m-d H:i:s')
    ));
}


$app->before(function () use ($app) {
    $app['twig']->addGlobal('layout', $app['twig']->loadTemplate('layout.twig'));
});

$app->get('/', function() use ($app) {
    $posts = $app['db']->fetchAll("
    SELECT posts.id, posts.title, posts.data, categories.name
    FROM posts JOIN categories
    ON posts.cat_id=categories.id
    ORDER BY date DESC
    ");


    return $app['twig']->render('index.twig', array(
        'title' => 'Main page',
        'posts' => $posts,
    ));
});

$app->match('/add', function(\Symfony\Component\HttpFoundation\Request $request) use ($app) {

    $cat_array = $app['db']->fetchAll("SELECT * FROM categories ORDER BY id ASC");
    foreach ($cat_array as $cat) {
        $cats[$cat['id']] = $cat['name'];
    }

    $data = array(
        'title' => 'News title',
        'data' => 'News data',
        'category' => $cats,
    );

    $form = $app['form.factory']->createBuilder('form', $data)
        ->add('title')
        ->add('data','textarea')
        ->add('category', 'choice', array(
            'choices' => $data['category'],
            'expanded' => false,
        ))
        ->getForm();

    $form->handleRequest($request);

    if ($form->isValid()) {
        $data = $form->getData();

        $app['db']->insert('posts', array(
            'title' => $data['title'],
            'data' => $data['data'],
            'cat_id' => $data['category'],
            'date' => date('Y-m-d H:i:s'),
        ));
        return $app->redirect('/');
    }

    return $app['twig']->render('add.twig', array(
        'title' => 'Add news',
        'form' => $form->createView(),
    ));
});

$app->get('/{name}', function($name) use ($app) {
    $cat_id = $app['db']->fetchColumn("SELECT id FROM categories WHERE name=?", array((string) $name));
    if (!$cat_id) {
        return $app->redirect('/');
    }
    $posts = $app['db']->fetchAll("
    SELECT posts.id, posts.title, posts.data, categories.name
    FROM posts JOIN categories
    ON posts.cat_id=categories.id
    AND cat_id = ?
    ", array((int) $cat_id));

    return $app['twig']->render('index.twig', array(
        'title' => 'Main page',
        'posts' => $posts,
    ));
});

$app->get('/{cat}/{id}', function ($cat, $id) use ($app) {
    $posts = $app['db']->fetchAll("
    SELECT posts.id, posts.title, posts.data, categories.name
    FROM posts JOIN categories
    ON posts.cat_id=categories.id
    AND categories.name = ?
    AND posts.id = ?
    ", array((string) $cat, (int) $id));

    if (!$posts) {
        return new \Symfony\Component\HttpFoundation\Response('Error', 404);
    }

    return $app['twig']->render('index.twig', array(
        'title' => 'Main page',
        'posts' => $posts,
    ));
});


return $app;
?>