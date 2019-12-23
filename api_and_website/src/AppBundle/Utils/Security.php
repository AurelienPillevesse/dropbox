<?php
namespace AppBundle\Utils;

use AppBundle\Entity\User;
use AppBundle\Entity\Folder;
use AppBundle\Entity\File;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class Security
{
	/*
	 * param : File or Folder $item, User $user
	 */
    public function checkUser($item, $user)
    {
        if(!($user instanceof User)) {
            throw new AccessDeniedHttpException('You are not logged in');
        }
        if($item->getOwner() == $user) {
            return true;
        } else {
            throw new NotFoundHttpException('File or folder not found');
        }
    }
}
