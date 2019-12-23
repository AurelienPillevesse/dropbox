<?php

namespace AppBundle\Factory;

use Doctrine\ORM\EntityManager;
use AppBundle\Entity\File;
use AppBundle\Entity\User;
use AppBundle\Entity\Folder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

class FileFactory
{
    private $em;
    private $container;


    /**
     * FileFactory constructor.
     * @param ContainerInterface $container
     * @param EntityManager $em
     */
    public function __construct(ContainerInterface $container, EntityManager $em)
    {
        $this->em = $em;
        $this->container = $container;
    }


    /**
     * @param File $file
     * @param User $user
     * @param Folder|null $parent
     * @throws \Exception
     *
     * Add file
     *
     */
    public function addOne(File $file, User $user, Folder $parent = null)
    {
        $file->setOwner($user);
        $file->setParent($parent);

        $formFile = $file->getFile();

        if($formFile->guessExtension() != null) {
            $file->setExtension($formFile->guessExtension());
        } else {
            $file->setExtension($formFile->getClientOriginalExtension());
        }

        if ($formFile->getClientSize() > (User::MAX_STORAGE_ALLOWED - $user->getUsedStorage())){
            throw new \Exception('No enough space available for your account!');
        }

        $choosenServer = null;
        $maxSpaceServer = 0;

        $storage_dirs = glob($this->container->getParameter('files_directory'). DIRECTORY_SEPARATOR ."iscsi*", GLOB_ONLYDIR);
        for($i = 0; $i < count($storage_dirs); $i++) {
            $availableSpaceServer = disk_free_space($storage_dirs[$i]);

            if($availableSpaceServer > $maxSpaceServer) {
                $tmp = explode(DIRECTORY_SEPARATOR, $storage_dirs[$i]);
                $choosenServer = end($tmp);
                $maxSpaceServer = $availableSpaceServer;
            }
        }

        if(($choosenServer == null) || ($choosenServer != null && $maxSpaceServer < $formFile->getClientSize())) {
            throw new \Exception('Our servers currently have an issue. Try again later!');
        }

        $file->setServerName($choosenServer);

        $fileName = md5(uniqid()). '.' .$formFile->guessExtension();
        $file->setType($formFile->getMimeType());
        $file->setFile($fileName);

        $exploded_name = explode('.', $formFile->getClientOriginalName());
        for ($i=0; $i < count($exploded_name); $i++) {
            if($exploded_name[$i] == $formFile->getClientOriginalExtension()) {
                array_splice($exploded_name, $i, 1);
            }
        }
        $final_name = implode('.', $exploded_name);
        $file->setName($final_name);

        $file->setSize($formFile->getClientSize());

        $formFile->move(
            $this->container->getParameter('files_directory'). DIRECTORY_SEPARATOR .$choosenServer,
            $fileName
        );

        $this->em->persist($file);

        $user->addStorage($file->getSize());
        $this->em->persist($user);
        $this->em->flush();
    }


    /**
     * @param File $file
     * @param User $user
     *
     * Delete file
     *
     */
    public function deleteOne(File $file, User $user)
    {
        $this->em->remove($file);

        $path = $this->container->getParameter('files_directory').DIRECTORY_SEPARATOR.$file->getServerName().DIRECTORY_SEPARATOR.$file->getFile();
        if(file_exists($path)) {
            unlink($path);
        }

        $user->setUsedStorage($user->getUsedStorage() - $file->getSize());
        $this->em->persist($user);
        $this->em->flush();
    }


    /**
     * @param File $file
     *
     * Rename file
     *
     */
    public function renameOne(File $file)
    {
        $this->em->persist($file);
        $this->em->flush();
    }


    /**
     * @param File $file
     * @param $name
     *
     * API - rename file
     *
     */
    public function renameApiOne(File $file, $name)
    {
        $file->setName($name);
        $this->em->persist($file);
        $this->em->flush();
    }
}
