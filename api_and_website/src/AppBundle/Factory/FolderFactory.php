<?php

namespace AppBundle\Factory;

use Doctrine\ORM\EntityManager;
use AppBundle\Entity\File;
use AppBundle\Entity\User;
use AppBundle\Entity\Folder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser;
use \ZipArchive;

class FolderFactory
{
    private $em;
    private $container;


    /**
     * FolderFactory constructor.
     * @param ContainerInterface $container
     * @param EntityManager $em
     */
    public function __construct(ContainerInterface $container, EntityManager $em)
    {
        $this->em = $em;
        $this->container = $container;
    }


    /**
     * @param Folder $folder
     * @param User $user
     * @param Folder|null $parent
     *
     * Add folder
     *
     */
    public function addOne(Folder $folder, User $user, Folder $parent = null)
    {
        $folder->setOwner($user);
        $folder->setParent($parent);

        $this->em->persist($folder);
        $this->em->flush();
    }


    /**
     * @param Folder $folder
     *
     * Rename folder
     *
     */
    public function renameOne(Folder $folder)
    {
        $this->em->persist($folder);
        $this->em->flush();
    }


    /**
     * @param Folder $folder
     * @param $name
     *
     * API - rename folder
     *
     */
    public function renameApiOne(Folder $folder, $name)
    {
        $folder->setName($name);
        $this->em->persist($folder);
        $this->em->flush();
    }


    /**
     * @param Folder $folder
     * @param User $user
     *
     * Delete folder
     *
     */
    public function deleteOne(Folder $folder, User $user)
    {
        $childrensFiles = $this->container->get('file_reader')->getByOwnerAndParent($user, $folder);
        $childrensFolders = $this->container->get('folder_reader')->getByOwnerAndParent($user, $folder);

        $this->deleteFilesFromFolder($childrensFiles);

        foreach($childrensFolders as $childrenFolder) {
            $this->deleteOne($childrenFolder, $user);
        }

        $sharesFolders = $this->container->get('share_reader')->getByOwnerAndFolder($user, $folder);
        foreach($sharesFolders as $shareFolder) {
            $this->em->remove($shareFolder);
        }

        $this->em->remove($folder);
        $this->em->flush();

        $this->container->get('user_factory')->updateStorage($user);
    }


    /**
     * @param $childrensFiles
     *
     * Delete files from selected folder
     *
     */
    public function deleteFilesFromFolder($childrensFiles) {
        foreach($childrensFiles as $childrenFile) {
            $path = $this->container->getParameter('files_directory').DIRECTORY_SEPARATOR.$childrenFile->getServerName().DIRECTORY_SEPARATOR.$childrenFile->getFile();
            if(file_exists($path)) {
                unlink($path);
            }

            $this->em->remove($childrenFile);
        }
    }


    /**
     * @param Folder $folder
     * @param User $user
     * @return bool|BinaryFileResponse
     *
     * Download folder
     *
     */
    public function downloadOne(Folder $folder, User $user) {
        if(!file_exists($this->container->getParameter('zip_directory'))) {
            mkdir($this->container->getParameter('zip_directory'));
        }

        $destination = $this->container->getParameter('zip_directory'). DIRECTORY_SEPARATOR . $folder->getName().'.zip';
        if (!extension_loaded('zip')) {
            return false;
        }

        $zip = new ZipArchive();
        if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
            return false;
        }

        $path = $folder->getName();
        $zip->addEmptyDir($path);

        $this->zipFilesFromFolder($path . DIRECTORY_SEPARATOR, $folder, $user, $zip);

        $zip->close();

        $response = new BinaryFileResponse($destination);
        $mimeTypeGuesser = new FileinfoMimeTypeGuesser();

        if($mimeTypeGuesser->isSupported()){
            $response->headers->set('Content-Type', $mimeTypeGuesser->guess($destination));
        }else{
            $response->headers->set('Content-Type', 'text/plain');
        }

        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $folder->getName().'.zip'
        );

        register_shutdown_function('unlink', $destination);

        return $response;
    }


    /**
     * @param $path
     * @param Folder $folder
     * @param User $user
     * @param ZipArchive $zip
     *
     * Zip files from folder selected
     *
     */
    public function zipFilesFromFolder($path, Folder $folder, User $user, ZipArchive $zip){
        $listFiles = $this->container->get('file_reader')->getByOwnerAndParent($user, $folder);
        $listFolders = $this->container->get('folder_reader')->getByOwnerAndParent($user, $folder);

        foreach ($listFiles as $file) {
            $pathFile = $this->container->getParameter('files_directory').DIRECTORY_SEPARATOR.$file->getServerName().DIRECTORY_SEPARATOR.$file->getFile();
            $zip->addFile($pathFile, $path.$file->getName() . '.' . $file->getExtension());
        }

        foreach ($listFolders as $folder) {
            $newPath = $path  .$folder->getName();
            $zip->addEmptyDir($newPath);
            $newPath = $newPath . DIRECTORY_SEPARATOR;
            $this->zipFilesFromFolder($newPath, $folder, $user, $zip);
        }
    }
}
