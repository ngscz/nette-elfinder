<?php

namespace Ngscz\Elfinder\Uploader;

use App\Model\Entity\Asset;
use Kdyby\Doctrine\EntityManager;
use elFinder as studio42ElFinder;
use elFinderVolumeLocalFileSystem;

class Uploader implements IUploader
{
    /** @var EntityManager */
    private $entityManager;


    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param  string $cmd command name
     * @param  array $result command result
     * @param  array $args command arguments from client
     * @param  studio42ElFinder $elfinder elFinder instance
     * @return void|true
     */
    public function onUpload($cmd, $result, $args, $elfinder)
    {
        foreach ($result['added'] as $value) {

            $path = $this->findRealPath($elfinder, $value['hash']);

            $originalName = $this->findOriginalFileName($value['size']);

            $originalName = ($originalName) ?: $value['name'];

            $asset = new Asset();
            $asset->setHash($value['hash']);
            $asset->setName($originalName);
            $asset->setPath($path);
            $asset->setType($value['mime']);
            $asset->setSize(intval($value['size']));

            $this->entityManager->persist($asset);

        }
        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            echo json_encode([
                'error' => [
                    'err' => 'Nepodařilo se uložit informace o souboru do databáze.',
                ],
            ]);
            exit;
        }
    }

    /**
     * @param  string $cmd command name
     * @param  array $result command result
     * @param  array $args command arguments from client
     * @param  studio42ElFinder $elfinder elFinder instance
     * @return void|true
     */
    public function onRename($cmd, $result, $args, $elfinder)
    {
        //@todo add posibility to rename file. Now it is disabled in elfinder configuration
    }

    /**
     * @param  string $cmd command name
     * @param  array $result command result
     * @param  array $args command arguments from client
     * @param  studio42ElFinder $elfinder elFinder instance
     * @return void|true
     */
    public function onRemove($cmd, $result, $args, $elfinder)
    {
        foreach ($result['removed'] as $value) {

            $asset = $this->findByHash($value['hash']);
            if ($asset) {
                $this->entityManager->remove($asset);
            }

            if ($value['mime'] == 'directory') {
                $qb = $this->entityManager->createQueryBuilder();
                $qb->delete('App\Model\Entity\Asset', 'a');
                $qb->where('a.path LIKE :path');
                $qb->setParameter(':path', '/' . $value['name'] . '/%');
                $qb->getQuery()->execute();
            }
        }

        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            echo json_encode([
                'error' => [
                    'err' => 'Nepodařilo se smazat soubor z databáze.',
                ],
            ]);
            exit;
        }
    }

    /**
     * @param  string $cmd command name
     * @param  array $result command result
     * @param  array $args command arguments from client
     * @param  studio42ElFinder $elfinder elFinder instance
     * @return void|true
     * @throws \elFinderAbortException
     */
    public function onFileDimension($cmd, $result, $args, $elfinder)
    {

        $asset = $this->findByHash($args['target']);
        if (!$asset) {

            $asset = $this->createAsset($elfinder, $args['target']);

            $this->entityManager->persist($asset);

            try {
                $this->entityManager->flush();
            } catch (\Exception $e) {
                echo json_encode([
                    'error' => [
                        'err' => 'Nepodařilo se uložit informace o souboru do databáze.',
                    ],
                ]);
                exit;
            }

        }
    }

    /**
     * @param  string $cmd command name
     * @param  array $result command result
     * @param  array $args command arguments from client
     * @param  studio42ElFinder $elfinder elFinder instance
     * @return void|true
     * @throws \elFinderAbortException
     */
    public function onFilePaste($cmd, $result, $args, $elfinder)
    {
        foreach ($result['removed'] as $key => $value) {
            if ($value['mime'] == 'directory') {
                //@todo update current hashes of subdirectories
            } else {
                $asset = $this->findByHash($value['hash']);
                if ($asset) {
                    $newValue = $result['added'][$key];
                    $asset->setHash($newValue['hash']);
                    $asset->setPath($this->findRealPath($elfinder, $newValue['hash']));
                    unset($result['added'][$key]);
                }
            }
        }

        foreach ($result['added'] as $key => $value) {
            if ($value['mime'] != 'directory') {
                $asset = $this->createAsset($elfinder, $value['hash']);
                if (!$this->isHashPersisted($value['hash'])) {
                    $this->entityManager->persist($asset);
                }
            }
        }

        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            echo json_encode([
                'error' => [
                    'err' => 'Nepodařilo se uložit informace o souboru do databáze.',
                ],
            ]);
            exit;
        }

    }

    /**
     * @param  string $cmd command name
     * @param  array $result command result
     * @param  array $args command arguments from client
     * @param  studio42ElFinder $elfinder elFinder instance
     * @return void|true
     * @throws \elFinderAbortException
     */
    public function onFolderOpen($cmd, $result, $args, $elfinder)
    {
        $hashes = array();
        foreach ($result['files'] as $value) {
            $hashes[] = $value['hash'];
        }

        $assets = $this->findByHashes($hashes);

        foreach ($result['files'] as $value) {
            if ($value['mime'] != 'directory') {
                if (!isset($assets[$value['hash']])) {
                    $asset = $this->createAsset($elfinder, $value['hash'], false);
                    if (!$this->isHashPersisted($value['hash'])) {
                        $this->entityManager->persist($asset);
                    }

                }
            }
        }

        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            echo json_encode([
                'error' => [
                    'err' => 'Nepodařilo se uložit informace o souboru do databáze.',
                ],
            ]);
            exit;
        }

    }


    /**
     * @param string $hash
     * @return Asset
     */
    private function findByHash($hash)
    {
        return $this->entityManager->getRepository(Asset::class)->findOneBy(['hash' => $hash]);
    }

    /**
     * @param array $hashes
     * @return array
     */
    private function findByHashes($hashes)
    {
        return $this->entityManager->getRepository(Asset::class)->createQueryBuilder()
            ->from(Asset::class, 'a', 'a.hash')
            ->select('a')
            ->where('a.hash IN (:hashes)')
            ->setParameter(':hashes', $hashes)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int $size
     * @return string|null
     */
    private function findOriginalFileName($size)
    {
        $uploadedFiles = $_FILES['upload'];
        if ($uploadedFiles) {
            foreach ($uploadedFiles['size'] as $key => $value) {
                if ($value == $size) {
                    return $uploadedFiles['name'][$key];
                }
            }
        }
        return null;
    }

    /**
     * @param studio42ElFinder $elfinder
     * @param string $hash
     * @param bool $checkExisting
     * @return Asset
     * @throws \elFinderAbortException
     */
    private function createAsset($elfinder, $hash, $checkExisting = true)
    {
        $info = $elfinder->exec('info', array('targets' => array($hash)));

        $path = $this->findRealPath($elfinder, $hash);

        $hash = $info['files'][0]['hash'];

        $asset = null;

        if ($checkExisting) {
            $asset = $this->findByHash($hash);
        }

        if (!$asset) {
            $asset = new Asset();
        }

        $asset->setHash($hash);
        $asset->setName($info['files'][0]['name']);
        $asset->setPath($path);
        $asset->setType($info['files'][0]['mime']);
        $asset->setSize($info['files'][0]['size']);

        return $asset;
    }

    /**
     * @param studio42ElFinder $elfinder
     * @param string $hash
     * @return string
     */
    private function findRealPath($elfinder, $hash)
    {
        /** @var string $filePath */
        $filePath = $elfinder->realpath($hash);

        /** @var elFinderVolumeLocalFileSystem $volume */
        $volume = $elfinder->getVolume($hash);

        $path = str_replace($volume->getRootPath(), '', $filePath);

        return $path;
    }

    /**
     * @param string $hash
     * @return bool
     */
    private function isHashPersisted($hash)
    {
        $assets = $this->entityManager->getUnitOfWork()->getScheduledEntityInsertions();
        foreach ($assets as $asset) {
            if ($asset->getHash() == $hash) {
                return true;
            }
        }
        return false;
    }
}
