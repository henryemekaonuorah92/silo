<?php

namespace Silo\Inventory\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Silo\Inventory\Repository\UserRepository")
 * @ORM\Table(name="user", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="email_idx", columns={"email"})
 * })
 */
class User implements MarshallableInterface
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
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name = '';

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     */
    private $email = '';

    public function __construct($name, $email = null)
    {
        $this->name = $name;
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function getIdString()
    {
        $name = filter_var($this->name, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
        $name = strtolower($name);
        $name = strtr($name, ' -_.','____');
        return sprintf("%s_%s", $this->id, $name);
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    public function __toString()
    {
        return 'User:'.$this->name;
    }

    public function marshall() {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name
        ];
    }
}
