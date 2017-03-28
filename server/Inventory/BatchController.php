<?php

namespace Silo\Inventory;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Silo\Base\JsonRequest;
use Silo\Inventory\Model\Batch;
use Silo\Inventory\Collection\BatchCollection;
use Silo\Inventory\Model\Context;
use Silo\Inventory\Model\Location;
use Silo\Inventory\Model\Operation;
use Silo\Inventory\Model\OperationSet;
use Silo\Inventory\Repository\ModifierRepository;
use Silo\Inventory\Validator\Constraints\LocationExists;
use Silo\Inventory\Validator\Constraints\SkuExists;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints as Constraint;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class BatchController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        /*
         * Create operations massively by uploading a CSV file
         *
         * Each Location will receive a mixed Operation targeting it
         * This way merge increments are seen as positive movements,
         * and decrements as negatives...
         */
        $controllers->post('/import', function (Request $request) use ($app) {
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $file = $request->files->get('file');
            $csv = new \parseCSV();
            $csv->offset = 1;
            $csv->keep_file_data = true;
            $csv->parse($file->getPathname());

            // Check first line of file
            $shouldStartWith = $request->get('type');
            if (substr($csv->file_data, 0, strlen($shouldStartWith)) !== $shouldStartWith) {
                return new JsonResponse(['errors' => ["File should start with \"$shouldStartWith\""]]);
            }

            /** @var BatchCollection[] $batchMap */
            // @todo Batchmap could be implemented in a class that allows optimization of Batch quantities. Ex:
            // MTLST -> VOID +2 SKUA
            // VOID -> MTLST +3 SKUB
            // would become VOID -> MTLST +3SKUB -2SKUA
            $batchMap = [];
            foreach ($csv->data as $line) {
                ++$line;
                /** @var ConstraintViolationList $violations */
                $violations = $app['validator']->validate($line, [
                    new Constraint\Collection([
                        'location' => [new Constraint\NotBlank(), new LocationExists()],
                        'product' => [new Constraint\Required(), new SkuExists()],
                        'quantity' => new Constraint\Range(['min' => -100, 'max' => 100]),
                    ]),
                    new Constraint\Callback(function ($payload, ExecutionContextInterface $context) {
                        if (isset($payload['location']) &&
                            $payload['location'] === "VOID"
                        ) {
                            $context->buildViolation('Location cannot be both VOID')
                                ->addViolation();
                        }
                    })
                ]);

                if ($violations->count() > 0) {
                    return new JsonResponse(['errors' => array_map(function ($violation) {
                        return (string)$violation;
                    }, iterator_to_array($violations->getIterator()))]);
                }

                $product = $app['em']->getRepository('Inventory:Product')->findOneBy(['sku' => $line['product']]);
                $batch = new Batch($product, $line['quantity']);
                $key = $line['location'];
                if (!isset($batchMap[$key])) {
                    $batchMap[$key] = new BatchCollection([$batch]);
                } else {
                    $batchMap[$key]->addBatch($batch);
                }
            }

            // New operation set
            $set = new OperationSet(null, ['description' => $request->request->get('description')]);

            // Find type
            switch ($request->request->get('type')) {
                case 'merge':
                    $typeName = 'batch merge';
                    break;
                case 'replace':
                    $typeName = 'batch replace';
                    break;
                case 'superReplace':
                default:
                    throw new \Exception('Type is unknown');
            }
            $type = $app['em']->getRepository('Inventory:OperationType')->getByName($typeName);

            // Create needed Operations
            foreach ($batchMap as $locationCode => $batches) {
                $location = $app['em']->getRepository('Inventory:Location')->forceFindOneByCode($locationCode);

                switch ($request->request->get('type')) {
                    case 'merge': // Merge the uploaded batch into the location
                        $operation = new Operation($app['current_user'], null, $location, $batches);
                        break;
                    // the Location batches are replaced by the uploaded ones
                    // We achieve this by computing the difference and applying it with an operation
                    // SOURCE + OP = TARGET
                    // hence OP = TARGET - SOURCE
                    case 'replace':
                        $diffBatches = $batches->diff($location->getBatches());
                        $operation = new Operation($app['current_user'], null, $location, $diffBatches);
                        break;
                    case 'superReplace':
                        throw new \Exception('superReplace is unknown');
                        break;
                    default:
                        throw new \Exception('Type is unknown');
                }

                $operation->setType($type);
                $operation->execute($app['current_user']);
                $app['em']->persist($operation);
                $set->add($operation);
            }

            if (!$set->isEmpty()) {
                $app['em']->persist($set);
                $app['em']->flush();
            }

            return new JsonResponse([]);
        });

        return $controllers;
    }
}
