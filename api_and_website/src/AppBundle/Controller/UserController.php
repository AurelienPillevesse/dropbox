<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\User;
use AppBundle\Entity\APIKey;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\DependencyInjection\ContainerInterface;


class UserController extends Controller
{
    private $serializer;


    /**
     * @param ContainerInterface|null $container
     *
     * set the parent container
     *
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->serializer = $this->get('app.serializer.default');
    }


    /**
     * @param Request $request
     * @return JsonResponse
     *
     * API - user register
     *
     */
    public function registerApiAction(Request $request)
    {
        $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');
        if(get_class($user) != 'AppBundle\Entity\User') {
            return $user;
        }

        if (!filter_var($user->getEmail(), FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['errorMessage' => 'Email is not valide!'], 400);
        }

        $em = $this->getDoctrine()->getManager();
        $databaseUsernameUser = $em->getRepository('AppBundle:User')->findBy(['usernameCanonical' => strtolower($user->getUsername())]);
        if($databaseUsernameUser) {
            return new JsonResponse(['errorMessage' => 'Username already taken!'], 400);
        }

        $databaseEmailUser = $em->getRepository('AppBundle:User')->findBy(['emailCanonical' => strtolower($user->getEmail())]);
        if($databaseEmailUser) {
            return new JsonResponse(['errorMessage' => 'Email already taken!'], 400);
        }

        $userManager = $this->get('fos_user.user_manager');
        $userManager->updateUser($user);

        $APIKeyUser = new APIKey();
        $APIKeyUser->setUser($user);
        $em->persist($APIKeyUser);
        $allAPIKeys = $em->getRepository('AppBundle:APIKey')->findByUser($user);

        foreach ($allAPIKeys as $key) {
            $key->setLifetime(0);
            $em->persist($key);
        }

        $em->flush();

        return new JsonResponse($this->serializer->serialize($APIKeyUser, 'json'), 200);
    }
}
