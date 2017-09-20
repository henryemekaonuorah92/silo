<?php

namespace Silo\Inventory;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Silo\Base\CsvResponse;
use Silo\Base\JsonRequest;
use Silo\Inventory\Finder\OperationFinder;
use Silo\Inventory\Model\Batch;
use Silo\Inventory\Collection\BatchCollection;
use Silo\Inventory\Model\Location;
use Silo\Inventory\Model\Modifier;
use Silo\Inventory\Model\Operation;
use Silo\Inventory\Model\OperationSet;
use Silo\Inventory\Repository\LocationRepository;
use Silo\Inventory\Repository\ModifierRepository;
use Silo\Inventory\Validator\Constraints\SkuExists;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints as Constraint;
use Symfony\Component\Validator\ConstraintViolationList;

class ExportController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/batches', function () use ($app) {
            /** @var EntityManager $em */
            $em = $app['em'];

            $sql = <<<EOQ
            select location.code as code, parent.code as parent, product.sku as s, SUM(batch.quantity) as q
            from silo_location location
            left join silo_location parent on location.parent = parent.location_id
            inner join silo_batch batch on location.location_id = batch.location_id
            inner join silo_product product on batch.product_id = product.product_id
            where batch.quantity != 0
            and location.isDeleted = 0
            group by location.code, product.sku
EOQ;
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            /** @var array $productMap [code => [[sku, quantity], ]] */
            $rows = [];
            while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
                array_push($rows, implode(',', $row));
            }

            array_unshift($rows, "location,parent,product,quantity");
            $content = implode(PHP_EOL, $rows);

            // Generate response
            $response = new CsvResponse($content, "batchesExport.csv");
            $response->sendHeaders();

            return $response;
        });

        $controllers->get('/locations', function () use ($app) {
            /** @var EntityManager $em */
            $em = $app['em'];

            $sql = <<<EOQ
            select location.code as code, parent.code as parent, modifier_type.name
            from silo_location location
            left join silo_location parent on location.parent = parent.location_id
            left join silo_modifier modifier on modifier.location = location.location_id
            left join silo_modifier_type modifier_type on modifier.modifier_type_id = modifier_type.modifier_type_id
            where location.isDeleted = 0
            order by location.code
EOQ;
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            /** @var array $productMap [code => [[sku, quantity], ]] */
            $rows = [];
            while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
                array_push($rows, implode(',', $row));
            }

            array_unshift($rows, "location,parent,modifier");
            $content = implode(PHP_EOL, $rows);

            // Generate response
            $response = new CsvResponse($content, "locationsExport.csv");
            $response->sendHeaders();

            return $response;
        });

        return $controllers;
    }
}
