<?php

/**
 * @package Agere_PhotoGrabber.php
 * @author Vlad Kozak <vk@agere.com.ua>
 */
class Agere_PhotoGrabber_Helper_Backup extends Mage_Core_Helper_Abstract
{
    /**
     * @var array
     */
    protected $specialCharsMap = [
        '&Slash&' => '/',
        '&Backslash&' => '\\',
        '&Asterisk&' => '*',
        '&Pipe&' => '|',
        '&Colon&' => ':',
        '&quot&' => '"',
        '&lt&' => '<',
        '&gt&' => '>',
        '&Questionmark&' => '?',
    ];

    protected $pathToBackup;

    protected $pathToImages;

    public function __construct()
    {
        $this->setPathToBackup(
            Mage::getBaseDir('media') . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'backup'
        );
        $this->setPathToImages(
            Mage::getBaseDir('media') . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'images'
        );
    }

    /**
     * @return mixed
     */
    public function getPathToBackup()
    {
        return $this->pathToBackup;
    }

    /**
     * @param mixed $pathToBackup
     * @return Agere_PhotoGrabber_Helper_Backup
     */
    public function setPathToBackup($pathToBackup)
    {
        $this->pathToBackup = $pathToBackup;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPathToImages()
    {
        return $this->pathToImages;
    }

    /**
     * @param mixed $pathToImages
     * @return Agere_PhotoGrabber_Helper_Backup
     */
    public function setPathToImages($pathToImages)
    {
        $this->pathToImages = $pathToImages;

        return $this;
    }

    public function run()
    {
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('sku')
            ->addAttributeToFilter('type_id', 'configurable')
            ->addFieldToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_DISABLED)
        ;
        foreach ($collection as $product) {
            $sku = $this->replacementSku($product->getSku());
            if ($this->isExistFolder($sku)) {
                $this->copydirect($sku);
            }
        }
    }

    public function replacementSku($sku)
    {
        foreach ($this->specialCharsMap as $char => $symbol) {
            if (mb_substr_count($sku, $symbol)) {
                return str_replace($symbol, $char, $sku);
            } else {
                return $sku;
            }
        }
    }

    public function isExistFolder($sku)
    {
        if (file_exists($folder = $this->getPathToBackup() . DIRECTORY_SEPARATOR . $sku)) {
            return true;
        } else {
            return false;
        }
    }

    public function copydirect($sku)
    {
        $sourceDir = $this->getPathToBackup() . DIRECTORY_SEPARATOR . $sku;
        $destDir = $this->getPathToImages() . DIRECTORY_SEPARATOR . $sku;
        if (!file_exists($destDir)) {
            mkdir($destDir, 0755, true);
        }
        $dirIterator = new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($dirIterator, RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $object) {
            $destPath = $destDir . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            ($object->isDir()) ? mkdir($destPath) : copy($object, $destPath);
        }
        $this->removeDirectory($sourceDir);
    }

    public function removeDirectory($dir)
    {
        $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator(
            $it,
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }
}