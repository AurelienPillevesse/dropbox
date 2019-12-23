<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\APIKey;
use AppBundle\Entity\Credentials;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\DependencyInjection\ContainerInterface;


class APIKeyController extends Controller
{
    private $serializer;

    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->serializer = $this->get('app.serializer.default');
    }

    public function loginApiAction(Request $request)
    {
        $found = false;

        $credentials = $this->serializer->deserialize($request->getContent(), Credentials::class, 'json');
        if(get_class($credentials) != 'AppBundle\Entity\Credentials') {
            return $credentials;
        }

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('AppBundle:User')->findOneByUsernameCanonical($credentials->getUsername());

        if ($user) {
            $found = true;
        } else {
            $user = $em->getRepository('AppBundle:User')->findOneByEmailCanonical($credentials->getUsername());
            if ($user) {
                $found = true;
            }
        }

        if(!$found) {
            return new JsonResponse(['errorMessage' => 'Username or email does not exists!'], 400);
        }

        $encoder = $this->get('security.password_encoder');
        $isPasswordValid = $encoder->isPasswordValid($user, $credentials->getPassword());

        if (!$isPasswordValid) {
            return new JsonResponse(['errorMessage' => 'Password is not correct!'], 400);
        }

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

    public function logoutApiAction(Request $request)
    {
        $key = $this->serializer->deserialize($request->getContent(), APIKey::class, 'json');
        if(get_class($key) != 'AppBundle\Entity\APIKey') {
            return $key;
        }

        $em = $this->getDoctrine()->getManager();
        $APIKey = $em->getRepository('AppBundle:APIKey')->findOneByHash($key->getHash());

        if (!$APIKey) {
            return new JsonResponse(['errorMessage' => 'Invalid token'], 401);
        }

        if (!$APIKey->isValid()) {
            return new JsonResponse(['errorMessage' => 'Token expired'], 422);
        }

        $allAPIKeys = $em->getRepository('AppBundle:APIKey')->findByUser($APIKey->getUser());
        foreach ($allAPIKeys as $key) {
            $key->setLifetime(0);
            $em->persist($key);
        }

        $em->flush();
        return new JsonResponse($this->serializer->serialize(['message' => 'Done'], 'json'), 200);
    }
}
