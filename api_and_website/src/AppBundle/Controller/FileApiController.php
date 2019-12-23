<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\APIKey;
use AppBundle\Entity\File;
use AppBundle\Entity\User;
use AppBundle\Form\CustomFileType;
use AppBundle\Form\RenameFileType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;


class FileApiController extends Controller
{
    private $serializer;

    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->serializer = $this->get('app.serializer.default');
    }

    public function addApiAction(Request $request, $folder_id)
    {
        $key = $request->request->get('token');

        $em = $this->getDoctrine()->getManager();
        $APIKey = $em->getRepository('AppBundle:APIKey')->findOneByHash($key);

        if (!$APIKey) {
            return new JsonResponse(['errorMessage' => 'Invalid token'], 401);
        }

        if (!$APIKey->isValid()) {
            return new JsonResponse(['errorMessage' => 'Token expired'], 422);
        }

        $user = $APIKey->getUser();
        $parent = $this->get('folder_reader')->getOneByIdAndOwner($folder_id, $user);
        if($folder_id != null && $parent == null) {
            return new JsonResponse(['errorMessage' => 'Folder not found'], 404);
        }

        if(!$request->files->get('file')) {
            return new JsonResponse(['errorMessage' => 'No file sent'], 400);
        }

        $file = new File();
        $file->setFile($request->files->get('file'));
        try {
            $this->get('file_factory')->addOne($file, $user, $parent);
        } catch(\Exception $e) {
            return new JsonResponse(['errorMessage' => $e->getMessage()], 500);
        }

        return new JsonResponse($this->serializer->serialize($file, 'json'), 200);
    }

    public function renameApiAction(Request $request, $file_id)
    {
        $key = $this->serializer->deserialize($request->getContent(), APIKey::class, 'json');
        if(get_class($key) != 'AppBundle\Entity\APIKey') {
            return $key;
        }

        $inputFile = $this->serializer->deserialize($request->getContent(), File::class, 'json');
        if(get_class($inputFile) != 'AppBundle\Entity\File') {
            return $inputFile;
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
        $file = $this->get('file_reader')->getOneByIdAndOwner($file_id, $user);
        if(!$file) {
            return new JsonResponse(['errorMessage' => 'File not found'], 404);
        }

        $this->get('file_factory')->renameApiOne($file, $inputFile->getName());

        return new JsonResponse($this->serializer->serialize($file, 'json'), 200);
    }

    public function deleteApiAction(Request $request, $file_id) {
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
        $file = $this->get('file_reader')->getOneByIdAndOwner($file_id, $user);
        if(!$file) {
            return new JsonResponse(['errorMessage' => 'File not found'], 404);
        }

        $this->get('file_factory')->deleteOne($file, $user);
        return new JsonResponse(['message' => 'Done'], 200);
    }

    public function getApiAction(Request $request, $file_id) {
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

        $file = $this->get('file_reader')->getOneByIdAndOwner($file_id, $user);
        if(!$file) {
            return new JsonResponse(['errorMessage' => 'File not found'], 404);
        }

        return new JsonResponse(['file' => $this->serializer->serialize($file, 'json')], 200);
    }

    public function downloadApiAction(Request $request, $file_id) {
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
        $file = $this->get('file_reader')->getOneByIdAndOwner($file_id, $user);
        if(!$file) {
            return new JsonResponse(['errorMessage' => 'File not found'], 404);
        }

        $path = $this->container->getParameter('files_directory').DIRECTORY_SEPARATOR.$file->getServerName().DIRECTORY_SEPARATOR.$file->getFile();

        $response = new BinaryFileResponse($path);
        $mimeTypeGuesser = new FileinfoMimeTypeGuesser();

        if($mimeTypeGuesser->isSupported()){
            $response->headers->set('Content-Type', $mimeTypeGuesser->guess($path));
        } else {
            $response->headers->set('Content-Type', 'text/plain');
        }

        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $file->getName().'.'.$file->getExtension()
        );

        return $response;
    }
}
