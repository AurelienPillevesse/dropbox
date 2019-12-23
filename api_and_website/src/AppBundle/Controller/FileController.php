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
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser;
use Symfony\Component\HttpFoundation\StreamedResponse;


class FileController extends Controller
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     *
     * If user is not connected, redirect to not_connected view, else redirect to index view with folders list and files list
     *
     */
    public function indexAction(Request $request) {
        $user = $this->getUser();

        if(!$user) {
            return $this->render('@App/not_connected.html.twig');
        }

        $listFolders = $this->get('folder_reader')->getByOwnerAndParent($user, null);
        $listFiles = $this->get('file_reader')->getByOwnerAndParent($user, null);

        return $this->render('@App/index.html.twig', [
            'currentFolder' => null,
            'forIndex' => true,
            'listFolders' => $listFolders,
            'listFiles' => $listFiles
        ]);
    }

    /**
     * @param Request $request
     * @param $file_id
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * return the file requested with file id
     *
     */
    public function getAction(Request $request, $file_id) {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $file = $this->get('file_reader')->getOneByIdAndOwner($file_id, $this->getUser());
        if(!$file) {
            throw $this->createNotFoundException('File not found');
        }

        $type = explode('/', $file->getType())[0];

        return $this->render('@App/File/get_file.html.twig', [
            'file_id' => $file->getId(),
            'file_type' => $file->getType(),
            'file_name' => $file->getName(),
            'type' => $type
        ]);
    }

    /**
     * @param Request $request
     * @param $file_id
     * @return StreamedResponse
     *
     * return the view of the file requested with file id
     *
     */
    public function showAction(Request $request, $file_id) {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $file = $this->get('file_reader')->getOneByIdAndOwner($file_id, $this->getUser());
        if(!$file) {
            throw $this->createNotFoundException('File not found');
        }

        $content = file_get_contents($this->getParameter('files_directory').DIRECTORY_SEPARATOR.$file->getServerName().DIRECTORY_SEPARATOR.$file->getFile());

        $response = new StreamedResponse();
        $response->setCallback(function () use ($content) {
            echo $content;
        });
        $response->headers->set('Content-Type', $file->getType());
        return $response;
    }

    /**
     * @param Request $request
     * @param $folder_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * Add file in the folder requested with folder id
     *
     */
    public function addAction(Request $request, $folder_id) {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $parent = $this->get('folder_reader')->getOneByIdAndOwner($folder_id, $this->getUser());
        if($folder_id != null && $parent == null) {
            throw $this->createNotFoundException('Folder not found');
        }

        $file = new File();
        $form = $this->createForm(CustomFileType::class, $file);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            try {
                $this->get('file_factory')->addOne($file, $this->getUser(), $parent);
            } catch(\Exception $e) {
                return $this->render('@App/File/add.html.twig', [
                    'form' => $form->createView(),
                    'errorMessage' => $e->getMessage()
                ]);
            }

            if($folder_id != null) {
                return $this->redirectToRoute('get_folder', ['folder_id' => $folder_id]);
            }

            return $this->redirectToRoute('index');
        }

        return $this->render('@App/File/add.html.twig', [
            'form' => $form->createView(),
            'errorMessage' => ''
        ]);
    }

    /**
     * @param Request $request
     * @param $file_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * Rename the file requested with file id
     *
     */
    public function renameAction(Request $request, $file_id) {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $file = $this->get('file_reader')->getOneByIdAndOwner($file_id, $this->getUser());
        if(!$file) {
            throw $this->createNotFoundException('File not found');
        }

        $form = $this->createForm(RenameFileType::class, $file);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $this->get('file_factory')->renameOne($file);

            if($file->getParent() != null) {
                return $this->redirectToRoute('get_folder', ['folder_id' => $file->getParent()->getId()]);
            }

            return $this->redirectToRoute('index');
        }

        return $this->render('@App/File/rename.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @param Request $request
     * @param $file_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * Delete the file requested with file id
     *
     */
    public function deleteAction(Request $request, $file_id) {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $file = $this->get('file_reader')->getOneByIdAndOwner($file_id, $this->getUser());
        if(!$file) {
            throw $this->createNotFoundException('File not found');
        }

        $this->get('file_factory')->deleteOne($file, $this->getUser());
        if($file->getParent() != null) {
            return $this->redirectToRoute('get_folder', ['folder_id' => $file->getParent()->getId()]);
        }

        return $this->redirectToRoute('index');
    }

    /**
     * @param Request $request
     * @param $file_id
     * @return BinaryFileResponse
     *
     * Download the file requested with file id
     *
     */
    public function downloadAction(Request $request, $file_id) {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $file = $this->get('file_reader')->getOneByIdAndOwner($file_id, $this->getUser());
        if(!$file) {
            throw $this->createNotFoundException('File not found');
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
