<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Folder;
use AppBundle\Entity\FolderPath;
use AppBundle\Entity\APIKey;
use AppBundle\Entity\Share;
use AppBundle\Form\FolderType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser;
use \ZipArchive;

class FolderController extends Controller
{

    /**
     * @param Request $request
     * @return Response
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

        return $this->render('@App/index.html.twig', array(
            'currentFolder' => null,
            'forIndex' => true,
            'listFolders' => $listFolders,
            'listFiles' => $listFiles
        ));
    }


    /**
     * @param Request $request
     * @param $folder_id
     * @return Response
     *
     * return the folder requested with folder id
     *
     */
    public function getAction(Request $request, $folder_id) {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();
        $folder = $this->get('folder_reader')->getOneByIdAndOwner($folder_id, $user);
        if(!$folder) {
            throw $this->createNotFoundException('Folder not found');
        }

        $parentFolders = [];
        array_unshift($parentFolders, $folder);
        $tmp = $folder->getParent();
        while($tmp != null){
            array_unshift($parentFolders, $tmp);
            $tmp = $tmp->getParent();
        }

        $listFolders = $this->get('folder_reader')->getByOwnerAndParent($user, $folder_id);
        $listFiles = $this->get('file_reader')->getByOwnerAndParent($user, $folder_id);

        return $this->render('@App/index.html.twig', array(
            'currentFolder' => $folder,
            'listFolders' => $listFolders,
            'listFiles' => $listFiles,
            'listParentFolders' => $parentFolders
        ));
    }


    /**
     * @param Request $request
     * @param $folder_id
     * @return Response
     *
     * Generate the link to share folder requested with folder id
     *
     */
    public function shareAction(Request $request, $folder_id) {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $folder = $this->get('folder_reader')->getOneByIdAndOwner($folder_id, $this->getUser());
        if(!$folder) {
            throw $this->createNotFoundException('Folder not found');
        }

        $share = new Share();
        $this->get('share_factory')->addOne($share, $folder, $this->getUser());

        return $this->render('@App/Share/link.html.twig', array(
            'share' => $share,
        ));
    }


    /**
     * @param Request $request
     * @param $folder_id
     * @return mixed
     *
     * Zip folder requested with folder id
     *
     */
    public function zipAction(Request $request, $folder_id) {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $folder = $this->get('folder_reader')->getOneByIdAndOwner($folder_id, $this->getUser());
        if(!$folder) {
            throw $this->createNotFoundException('Folder not found');
        }

        return $this->get('folder_factory')->downloadOne($folder, $this->getUser());
    }


    /**
     * @param Request $request
     * @param $folder_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * Rename folder requested with folder id
     *
     */
    public function renameAction(Request $request, $folder_id) {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $folder = $this->get('folder_reader')->getOneByIdAndOwner($folder_id, $this->getUser());
        if(!$folder) {
            throw $this->createNotFoundException('Folder not found');
        }

        $form = $this->createForm(FolderType::class, $folder);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $this->get('folder_factory')->renameOne($folder);

            if($folder->getParent() != null) {
                return $this->redirectToRoute('get_folder', ['folder_id' => $folder->getId()]);
            }

            return $this->redirectToRoute('index');
        }

        return $this->render('@App/Folder/rename.html.twig', ['form' => $form->createView()]);
    }


    /**
     * @param Request $request
     * @param $hash
     * @return Response
     *
     * Get elements with the share link
     *
     */
    public function sharedAction(Request $request, $hash) {
        $share = $this->get('share_reader')->getOneByHash($hash);
        if(!$share) {
            throw $this->createNotFoundException('Share not found');
        }

        if(!$share->getIsEnable()) {
            throw new AccessDeniedHttpException('This link is not enable anymore!');
        }

        $folder = $share->getFolder();
        $listFolders = $this->get('folder_reader')->getByParent($folder);
        $listFiles = $this->get('file_reader')->getByParent($folder);

        return $this->render('@App/index.html.twig', [
            'currentFolder' => $folder,
            'listFolders' => $listFolders,
            'listFiles' => $listFiles,
            'hash' => $hash,
            'fromShareLink' => true,
        ]);
    }


    /**
     * @param Request $request
     * @param $folder_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * Add folder requested with folder id
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

        $folder = new Folder();

        $form = $this->createForm(FolderType::class, $folder);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $this->get('folder_factory')->addOne($folder, $this->getUser(), $parent);

            if($parent != null) {
                return $this->redirectToRoute('get_folder', ['folder_id' => $parent->getId()]);
            }
            return $this->redirectToRoute('index');
        }

        return $this->render('@App/Folder/add.html.twig', ['form' => $form->createView()]);
    }


    /**
     * @param Request $request
     * @param $folder_id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * Delete folder requested with folder id
     *
     */
    public function deleteAction(Request $request, $folder_id) {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $folder = $this->get('folder_reader')->getOneByIdAndOwner($folder_id, $this->getUser());
        if(!$folder) {
            throw $this->createNotFoundException('Folder not found');
        }

        $this->get('folder_factory')->deleteOne($folder, $this->getUser());
        if($folder->getParent() != null) {
            return $this->redirectToRoute('get_folder', ['folder_id' => $folder->getParent()->getId()]);
        }

        return $this->redirectToRoute('index');
    }
}
