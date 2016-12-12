<?php

namespace Silo\Inventory\Repository;

use Doctrine\ORM\EntityRepository;
use Silo\Inventory\Model\User as Model;

class User extends EntityRepository
{
    /**
     * System user by name. If does not exist, create it.
     *
     * @return Model
     */
    public function getSystemUser($name)
    {
        if (!in_array($name, [Model::NAME_BOT])) {
            throw new \InvalidArgumentException("$name is not a known system user.");
        }

        $user = $this->findOneBy(['name' => $name]);
        if (!$user) {
            $user = new Model($name, "it+$name@frankandoak.com");
            $this->_em->persist($user);
        }

        return $user;
    }
}
