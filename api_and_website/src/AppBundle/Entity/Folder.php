<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Folder
 *
 * @ORM\Table(name="folder")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\FolderRepository")
 */
class Folder
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var \AppBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $owner;

    /**
     * @var \AppBundle\Entity\Folder
     *
     * @ORM\ManyToOne(targetEntity="Folder")
     * @ORM\JoinColumn(name="folder_id", referencedColumnName="id")
     */
    private $parent;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     */
    private $created;


    /*
     * Constructor
     */
    public function __construct()
    {
        $this->created = new \Datetime();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Folder
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set owner.
     *
     * @param \AppBundle\Entity\User $owner
     *
     * @return Folder
     */
    public function setOwner(\AppBundle\Entity\User $owner = null)
    {
        $this->owner = $owner;
        return $this;
    }

    /**
     * Get owner.
     *
     * @return \AppBundle\Entity\User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set parent.
     *
     * @param \AppBundle\Entity\Folder $parent
     *
     * @return Folder
     */
    public function setParent(\AppBundle\Entity\Folder $parent = null)
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * Get parent.
     *
     * @return \AppBundle\Entity\Folder
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     *
     * @return Folder
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }
}
