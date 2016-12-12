<?php

namespace Silo\Inventory\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Silo\Inventory\Repository\User")
 * @ORM\Table(name="user")
 */
class User
{
    const NAME_BOT = 'bot';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="user_id", type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name = '';

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255)
     */
    private $email = '';

    public function __construct($name, $email = null)
    {
        $this->name = $name;
        $this->email = $email;
    }
}
