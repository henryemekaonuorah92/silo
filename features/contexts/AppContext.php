<?php

namespace Silo\Context;

use Behat\Behat\Context\BehatContext;
use Silo\Inventory\Model\User;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\TableNode;
use Silo\Context\AppAwareContextInterface;

/**
 * Create a new Silo app, and spawn an empty test database.
 * Injects an app reference inside AppAwareContextInterface Contexts.
 * Expose a reference system that can be used to retrieve objects between steps.
 */
class AppContext extends BehatContext
{
    protected $app;

    protected $dsn;

    protected $debug;

    /** @var \Doctrine\ORM\EntityManager */
    private $em;

    private $refs = [];

    private $providers;

    public function getRef($name)
    {
        if (!isset($this->refs[$name])) {
            throw new \Exception("No such ref $name");
        }

        return $this->refs[$name];
    }

    public function setRef($name, $object)
    {
        if (isset($this->refs[$name])) {
            throw new \Exception("Ref $name is already set");
        }
        // $this->printDebug("Set Ref $name as $object");
        $this->refs[$name] = $object;
    }


    /**
     * {@inheritdoc}
     */
    public function __construct(array $parameters)
    {
        $this->dsn = isset($parameters['dsn']) ? $parameters['dsn'] : 'sqlite:///:memory:';
        $this->providers = isset($parameters['providers']) ? $parameters['providers'] : [];
    }

    /** @BeforeScenario */
    public function beforeScenario($event)
    {
        $that = $this;
        $logger = new \Monolog\Logger('test');
        $logger->pushHandler(new \Silo\Base\CallbackHandler(function($record)use($that){
            if (stripos($record['message'], 'Matched route') === 0){return;}
            echo "\033[36m|  ".strtr($record['message'], array("\n" => "\n|  "))."\033[0m\n";
        }, \Monolog\Logger::INFO));

        $this->app = $app = new \Silo\Silo([
            'em.dsn' => $this->dsn,
            'logger' => $logger
        ]);

        foreach($this->providers as $provider) {
            $app->register($provider);
        }

        $app->boot();
        $this->em = $em = $app['em'];

        // Generate the database
        $metadatas = $em->getMetadataFactory()->getAllMetadata();

        $tool = new \Doctrine\ORM\Tools\SchemaTool($this->app['em']);
        $tool->createSchema($metadatas);

        $mainContext = $this->getMainContext();
        if ($mainContext instanceof AppAwareContextInterface) {
            $mainContext->setApp($app);
        }

        if ($mainContext instanceof ClientContextInterface) {
            $mainContext->setClient(new Client($app));
        }

        foreach ($mainContext->getSubcontexts() as $context) {
            if ($context instanceof AppAwareContextInterface) {
                $context->setApp($app);
            }

            if ($context instanceof ClientContextInterface) {
                $context->setClient(new Client($app));
            }
        }

        $user = new User('test');
        $em->persist($user);
        $em->flush();
        $this->setRef('User', $user);

        // Register a logger if needed
        if (isset($this->parameters['debugDoctrine']) && $this->parameters['debugDoctrine']) {
            $em->getConnection()
                ->getConfiguration()
                ->setSQLLogger(new \PrintDebugLogger($this))
            ;
        }
    }

    /** @BeforeStep */
    public function beforeStep($event)
    {
        $em = $this->app['em'];
        $em->clear();
        $this->app['current_user'] = $em->getRepository('Inventory:User')->find($this->getRef('User'));
    }
}
