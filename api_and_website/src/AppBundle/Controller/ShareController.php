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

class ShareController extends Controller
{
    /**
     * @param Request $request
     * @param $hash
     * @param $folder_id
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * Get the shared folder with the hash generated
     *
     */
    public function sharedSubFolderAction(Request $request, $hash, $folder_id){
        $share = $this->get('share_reader')->getOneByHash($hash);
        if(!$share) {
            throw new AccessDeniedHttpException('Share not found!');
        }

        if(!$share->getIsEnable()) {
            throw new AccessDeniedHttpException('This link is not enable anymore!');
        }

        $sharedFolder = $share->getFolder();
        $wantedFolder = $this->get('folder_reader')->getOneById($folder_id);
        if(!$wantedFolder) {
            throw $this->createNotFoundException('Folder not found');
        }

        if($sharedFolder != $wantedFolder) {
            $tmp = $wantedFolder->getParent();

            while($tmp != null && $tmp != $sharedFolder){
                $tmp = $tmp->getParent();
            }

            if($tmp == null && $tmp != $sharedFolder) {
                throw new AccessDeniedHttpException('Folder not accessible in this public link!');
            }
        }

        $listFolders = $this->get('folder_reader')->getByParent($folder_id);
        $listFiles = $this->get('file_reader')->getByParent($folder_id);

        $parentFolders = [];
        array_unshift($parentFolders, $wantedFolder);
        $tmp = $wantedFolder->getParent();
        while($tmp != null && $tmp != $sharedFolder){
            array_unshift($parentFolders, $tmp);
            $tmp = $tmp->getParent();
        }

        return $this->render('@App/index.html.twig', array(
            'currentFolder' => $wantedFolder,
            'folder_id' => $folder_id,
            'hash' => $hash,
            'listFolders' => $listFolders,
            'listFiles' => $listFiles,
            'listParentFolders' => $parentFolders
        ));
    }


    /**
     * @param Request $request
     * @param $hash
     * @param $file_id
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * Get the shared file with the hash generated
     *
     */
    public function sharedSubFileAction(Request $request, $hash, $file_id){
        $share = $this->get('share_reader')->getOneByHash($hash);
        if(!$share) {
            throw new AccessDeniedHttpException('Share not found!');
        }

        if(!$share->getIsEnable()) {
            throw new AccessDeniedHttpException('This link is not enable anymore!');
        }

        $sharedFolder = $share->getFolder();
        $wantedFile = $this->get('file_reader')->getOneById($file_id);
        if(!$wantedFile) {
            throw $this->createNotFoundException('File not found');
        }

        if($sharedFolder != $wantedFile->getParent()) {
            $tmp = $wantedFile->getParent();

            while($tmp != null && $tmp != $sharedFolder){
                $tmp = $tmp->getParent();
            }

            if($tmp == null && $tmp != $sharedFolder) {
                throw new AccessDeniedHttpException('File not accessible in this public link!');
            }
        }

        $type = explode('/', $wantedFile->getType())[0];

        return $this->render('@App/File/get_file.html.twig', [
            'file_id' => $wantedFile->getId(),
            'file_type' => $wantedFile->getType(),
            'file_name' => $wantedFile->getName(),
            'hash' => $hash,
            'type' => $type
        ]);
    }


    /**
     * @param Request $request
     * @param $hash
     * @param $file_id
     * @return StreamedResponse
     *
     * Show file content of shared folder
     *
     */
    public function showSharedSubFileAction(Request $request, $hash, $file_id) {
        $share = $this->get('share_reader')->getOneByHash($hash);
        if(!$share) {
            throw new AccessDeniedHttpException('Share not found!');
        }

        if(!$share->getIsEnable()) {
            throw new AccessDeniedHttpException('This link is not enable anymore!');
        }

        $sharedFolder = $share->getFolder();
        $wantedFile = $this->get('file_reader')->getOneById($file_id);
        if(!$wantedFile) {
            throw $this->createNotFoundException('File not found');
        }

        if($sharedFolder != $wantedFile->getParent()) {
            $tmp = $wantedFile->getParent();

            while($tmp != null && $tmp != $sharedFolder){
                $tmp = $tmp->getParent();
            }

            if($tmp == null && $tmp != $sharedFolder) {
                throw new AccessDeniedHttpException('File not accessible in this public link!');
            }
        }

        $content = file_get_contents($this->getParameter('files_directory').DIRECTORY_SEPARATOR.$wantedFile->getServerName().DIRECTORY_SEPARATOR.$wantedFile->getFile());

        $response = new StreamedResponse();
        $response->setCallback(function () use ($content) {
            echo $content;
        });
        $response->headers->set('Content-Type', $wantedFile->getType());
        return $response;
    }
}
