<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Folder;
use AppBundle\Entity\Share;
use AppBundle\Entity\FolderPath;
use AppBundle\Entity\APIKey;
use AppBundle\Form\FolderType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FolderApiController extends Controller
{
    private $serializer;

    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->serializer = $this->get('app.serializer.default');
    }

    public function addApiAction(Request $request, $folder_id)
    {
        $key = $this->serializer->deserialize($request->getContent(), APIKey::class, 'json');
        if(get_class($key) != 'AppBundle\Entity\APIKey') {
            return $key;
        }

        $folder = $this->serializer->deserialize($request->getContent(), Folder::class, 'json');
        if(get_class($folder) != 'AppBundle\Entity\Folder') {
            return $folder;
        }

        $em = $this->getDoctrine()->getManager();
        $APIKey = $em->getRepository('AppBundle:APIKey')->findOneByHash($key->getHash());

        if (!$APIKey) {
            return new JsonResponse(['errorMessage' => 'Invalid token'], 401);
        }

        if (!$APIKey->isValid()) {
            return new JsonResponse(['errorMessage' => 'Token expired'], 422);
        }

        $user = $APIKey->getUser();
        $parent = $this->get('folder_reader')->getOneByIdAndOwner($folder_id, $user);
        if($folder_id != null && !$parent) {
            return new JsonResponse(['errorMessage' => 'Folder not found'], 404);
        }

        $this->get('folder_factory')->addOne($folder, $user, $parent);

        return new JsonResponse($this->serializer->serialize($folder, 'json'), 200);
    }

    public function getApiAction(Request $request, $folder_id)
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

        $user = $APIKey->getUser();

        if($folder_id != null) {
            $folder = $this->get('folder_reader')->getOneByIdAndOwner($folder_id, $user);
            if(!$folder) {
                return new JsonResponse(['errorMessage' => 'Folder not found'], 404);
            }
        }

        $folders = $this->get('folder_reader')->getByOwnerAndParent($user, $folder_id);
        $files = $this->get('file_reader')->getByOwnerAndParent($user, $folder_id);

        $content = [];
        $content['folders'] = $folders;
        $content['files'] = $files;

        return new JsonResponse($this->serializer->serialize($content, 'json'), 200);
    }

    public function renameApiAction(Request $request, $folder_id)
    {
        $key = $this->serializer->deserialize($request->getContent(), APIKey::class, 'json');
        if(get_class($key) != 'AppBundle\Entity\APIKey') {
            return $key;
        }

        $inputFolder = $this->serializer->deserialize($request->getContent(), Folder::class, 'json');
        if(get_class($inputFolder) != 'AppBundle\Entity\Folder') {
            return $inputFolder;
        }

        $em = $this->getDoctrine()->getManager();
        $APIKey = $em->getRepository('AppBundle:APIKey')->findOneByHash($key->getHash());

        if (!$APIKey) {
            return new JsonResponse(['errorMessage' => 'Invalid token'], 401);
        }

        if (!$APIKey->isValid()) {
            return new JsonResponse(['errorMessage' => 'Token expired'], 422);
        }

        $user = $APIKey->getUser();

        $folder = $this->get('folder_reader')->getOneByIdAndOwner($folder_id, $user);
        if(!$folder) {
            return new JsonResponse(['errorMessage' => 'Folder not found'], 404);
        }

        $this->get('folder_factory')->renameApiOne($folder, $inputFolder->getName());

        return new JsonResponse($this->serializer->serialize($folder, 'json'), 200);
    }

    public function deleteApiAction(Request $request, $folder_id)
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

        $user = $APIKey->getUser();

        $folder = $this->get('folder_reader')->getOneByIdAndOwner($folder_id, $user);
        if(!$folder) {
            return new JsonResponse(['errorMessage' => 'Folder not found'], 404);
        }

        $this->get('folder_factory')->deleteOne($folder, $user);

        return new JsonResponse(['message' => 'Done'], 200);
    }

    public function zipApiAction(Request $request, $folder_id) {
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

        $folder = $this->get('folder_reader')->getOneByIdAndOwner($folder_id, $APIKey->getUser());
        if(!$folder) {
            return new JsonResponse(['errorMessage' => 'Folder not found'], 404);
        }

        return $this->get('folder_factory')->downloadOne($folder, $APIKey->getUser());
    }

    public function shareApiAction(Request $request, $folder_id) {
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

        $folder = $this->get('folder_reader')->getOneByIdAndOwner($folder_id, $APIKey->getUser());
        if(!$folder) {
            return new JsonResponse(['errorMessage' => 'Folder not found'], 404);
        }

        $share = new Share();
        $this->get('share_factory')->addOne($share, $folder, $APIKey->getUser());

        return new JsonResponse(['link' => $this->generateUrl('shared_folder', array('hash' => $share->getHash()), UrlGeneratorInterface::ABSOLUTE_URL)], 200);
    }
}
