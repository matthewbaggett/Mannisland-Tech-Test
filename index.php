<?php
require('vendor/autoload.php');

use \DevExercize\Models\Quote;

error_reporting(E_ALL);
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);

// Create Slim app
$app = new \Slim\App(
    [
        'settings' => [
            'debug'         => true,
        ]
    ]
);

// Lets connect to a database
$databaseConfiguration = array(
    'db_type' => 'Mysql',
    'db_hostname' => 'localhost',
    'db_port' => '3306',
    'db_username' => 'mann_island_test',
    'db_password' => 'mann_island_test',
    'db_database' => 'mann_island_test',
);
$database = new \Thru\ActiveRecord\DatabaseLayer($databaseConfiguration);

// This would normally be stored elsewhere, but since time is of the essence..
$username = 'demo@mannisland.co.uk';
$password = 'm4nn1sland';

function getSoapClient()
{
    $soapClient = new Zend\Soap\Client(
        "http://mannisland.co.uk/exercise/Service.php?wsdl",
        [
            'soap_version' => SOAP_1_1,
            'compression' => SOAP_COMPRESSION_ACCEPT
        ]
    );
    //\Kint::dump($soapClient->getFunctions());
    return $soapClient;
}

// Add whoops to slim because its helps debuggin' and is pretty.
$app->add(new \Zeuxisoo\Whoops\Provider\Slim\WhoopsMiddleware);

// Fetch DI Container
$container = $app->getContainer();

// Instantiate and add Slim specific extension
$view = new \Slim\Views\Twig(
    __DIR__ . '/views',
    [
    'cache' => $container->get('settings')['debug'] ? false : __DIR__ . '/cache'
    ]
);

$view->addExtension(new Slim\Views\TwigExtension(
    $container->get('router'),
    $container->get('request')->getUri()
));

// Register Twig View helper
$container->register($view);

$app->get('/', function (\Slim\Http\Request $request, \Slim\Http\Response $response, $args) {
    header("Location: /companies");
    exit;
});

$app->get('/companies', function (\Slim\Http\Request $request, \Slim\Http\Response $response, $args) {
    global $username, $password;
    $auth = ['username' => $username, 'password' => $password];
    $companies = getSoapClient()->getCompanies($auth);
    return $this->view->render($response, 'companies.html.twig', [
        'companies' => $companies
    ]);
})->setName('select-company');

$app->post('/companies/lookup', function (\Slim\Http\Request $request, \Slim\Http\Response $response, $args) {
    // we're just going to redirect the user so we had a nice POST submit function and a nice
    // friendly URL for the resulting page (and for bots)
    $tickerCode = $request->getParsedBodyParam('company');
    header("Location: /companies/{$tickerCode}");
    exit;
});

$app->get('/companies/{tickerCode}', function (\Slim\Http\Request $request, \Slim\Http\Response $response, $args) {
    global $username, $password;
    $auth = ['username' => $username, 'password' => $password];
    $tickerCode = $args['tickerCode'];
    $soapClient = getSoapClient();
    $errors = [];

    try {
        $companies = [];
        foreach (getSoapClient()->getCompanies($auth) as $company) {
            $companies[$company->symbol] = $company;
        }
        $company = $companies[$tickerCode];
    } catch (SoapFault $sf) {
        $errors[] = "Could not get company details: " . $sf->faultcode . " - " . $sf->faultactor;
    }

    try {
        $directors = $soapClient->getDirectorsBySymbol($auth, $tickerCode);
    } catch (SoapFault $sf) {
       $errors[] = "Could not get directors listing: " . $sf->faultcode . " - " . $sf->faultactor;
    }

    try {
        $quoteResponse = $soapClient->getQuote($auth, $tickerCode);
        $quote = new Quote();
        $quote->ticker_code = $tickerCode;
        $quote->value = $quoteResponse;
        $quote->save();
    } catch (SoapFault $sf) {
        $errors[] = "Could not get quote: " . $sf->faultcode . " - " . $sf->faultactor;
    }

    $quotes = Quote::search()
        ->where('ticker_code', $tickerCode)
        ->limit(5)
        ->order('created', 'DESC')
        ->exec();


    return $this->view->render($response, 'company_profile.html.twig', [
        'errors' => $errors,
        'company' => isset($company) ? $company : false,
        'directors' => isset($directors) ? $directors : false,
        'quotes' => $quotes
    ]);
});

// Run app
$app->run();
