<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;

/**
* @ORM\Entity
* @ORM\Table(name="fos_user")
*/
class User extends BaseUser
{

    const MAX_STORAGE_ALLOWED = 32212254720;

    /**
    * @ORM\Id
    * @ORM\Column(type="integer")
    * @ORM\GeneratedValue(strategy="AUTO")
    */
    protected $id;

    public function __construct()
    {
        parent::__construct();
        $this->usedStorage = 0;
    }

    /**
    * @var string
    *
    * @ORM\Column(name="facebook_id", type="string", nullable=true)
    */
    protected $facebook_id;

    /**
    * @var string
    *
    * @ORM\Column(name="google_id", type="string", nullable=true)
    */
    protected $google_id;


    /**
    * @var integer
    *
    * @ORM\Column(name="usedStorage", type="integer", nullable=false)
    */
    protected $usedStorage;



    /**
     * Set facebookId
     *
     * @param string $facebookId
     *
     * @return User
     */
    public function setFacebookId($facebookId)
    {
        $this->facebook_id = $facebookId;

        return $this;
    }

    /**
     * Get facebookId
     *
     * @return string
     */
    public function getFacebookId()
    {
        return $this->facebook_id;
    }

    /**
     * Set googleId
     *
     * @param string $googleId
     *
     * @return User
     */
    public function setGoogleId($googleId)
    {
        $this->google_id = $googleId;

        return $this;
    }

    /**
     * Get googleId
     *
     * @return string
     */
    public function getGoogleId()
    {
        return $this->google_id;
    }


    /**
     * Set usedStorage
     *
     * @param string $usedStorage
     *
     * @return boolval
     */
    public function setUsedStorage($usedStorage)
    {
        $this->usedStorage = $usedStorage;

        return True;
    }

    /**
     * Add Storage
     *
     * @param string $storage
     *
     * @return boolval
     */
    public function addStorage($storage)
    {
        $this->usedStorage += $storage;

        return True;
    }

    /**
     * Get usedStorage
     *
     * @return int
     */
    public function getUsedStorage()
    {
        return $this->usedStorage;
    }
}
