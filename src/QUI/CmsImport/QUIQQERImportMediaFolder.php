<?php

namespace QUI\CmsImport;

use QUI;
use QUI\Projects\Media\Utils as MediaUtils;
use QUI\Utils\System\File as FileUtils;
use QUI\Utils\StringHelper as StringUtils;

class QUIQQERImportMediaFolder extends QUI\Projects\Media\Folder
{
    /**
     * Adds / create a subfolder
     *
     * @internal This is almost an exact copy of \QUI\Projects\Media\Folder::createFolder
     * with the only difference that you can set a custom ID
     *
     * @param string $foldername - Name of the new folder
     * @param int $customId (optional) - Custom QUIQQER Media ID
     *
     * @return QUI\Projects\Media\Folder
     * @throws QUI\Exception
     */
    public function createFolder($foldername, $customId = null)
    {
        // Namensprüfung wegen unerlaubten Zeichen
        MediaUtils::checkFolderName($foldername);

        // Whitespaces am Anfang und am Ende rausnehmen
        $new_name = trim($foldername);


        $User = QUI::getUserBySession();
        $dir  = $this->Media->getFullPath().$this->getPath();

        if (is_dir($dir.$new_name)) {
            // prüfen ob dieser ordner schon als kind existiert
            // wenn nein, muss dieser ordner in der DB angelegt werden

            try {
                $children = $this->getChildByName($new_name);
            } catch (QUI\Exception $Exception) {
                $children = false;
            }

            if ($children) {
                throw new QUI\Exception(
                    'Der Ordner existiert schon '.$dir.$new_name,
                    701
                );
            }
        }

        FileUtils::mkdir($dir.$new_name);

        $table     = $this->Media->getTable();
        $table_rel = $this->Media->getTable('relations');
        $data      = [
            'name'      => $new_name,
            'title'     => $new_name,
            'short'     => $new_name,
            'type'      => 'folder',
            'file'      => $this->getAttribute('file').$new_name.'/',
            'alt'       => $new_name,
            'c_date'    => date('Y-m-d h:i:s'),
            'e_date'    => date('Y-m-d h:i:s'),
            'c_user'    => $User->getId(),
            'e_user'    => $User->getId(),
            'mime_type' => 'folder'
        ];

        $PDO = QUI::getDataBase()->getPDO();

        if (!empty($customId)) {
            $data['id'] = (int)$customId;

            // disable AUTO_INCREMENT
//            $Statement = $PDO->prepare("ALTER TABLE {$table} MODIFY `id` BIGINT(20) NOT NULL");
//            $Statement->execute();

            QUI::getDataBase()->insert($table, $data);

            // re-enable AUTO_INCREMENT
//            $Statement = $PDO->prepare("ALTER TABLE {$table} MODIFY `id` BIGINT(20) NOT NULL AUTO_INCREMENT");
//            $Statement->execute();

            $id = $data['id'];
        } else {
            QUI::getDataBase()->insert($table, $data);
            $id = $PDO->lastInsertId();
        }

        QUI::getDataBase()->insert($table_rel, [
            'parent' => $this->getId(),
            'child'  => $id
        ]);

        if (is_dir($dir.$new_name)) {
            $Folder = $this->Media->get($id);

            $Folder->setEffects($this->getEffects());
            $Folder->save();

            return $Folder;
        }

        throw new QUI\Exception(
            ['quiqqer/quiqqer', 'exception.media.folder.could.not.be.created'],
            507
        );
    }

    /**
     * Uploads a file to the Folder
     *
     * @internal This is almost an exact copy of \QUI\Projects\Media\Folder::uploadFile
     * with the only difference that you can set a custom ID
     *
     * @param string $file - Path to the File
     * @param integer $options - Overwrite flags,
     *                           self::FILE_OVERWRITE_NONE
     *                           self::FILE_OVERWRITE_FILE
     *                           self::FILE_OVERWRITE_DESTROY
     * @param int $customId (optional) - Custom QUIQQER Media ID
     *
     * @return QUI\Projects\Media\Item
     * @throws QUI\Exception
     */
    public function uploadFile($file, $options = self::FILE_OVERWRITE_NONE, $customId = null)
    {
        if (!file_exists($file)) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/system', 'exception.file.not.found', [
                    'file' => $file
                ]),
                404
            );
        }

        if (is_dir($file)) {
            return $this->uploadFolder($file);
        }

        $fileinfo = FileUtils::getInfo($file);
        $filename = MediaUtils::stripMediaName($fileinfo['basename']);


        // test if the image is readable
        if (MediaUtils::getMediaTypeByMimeType($fileinfo['mime_type']) === 'image'
            && strpos($fileinfo['mime_type'], 'svg') === false
        ) {
            try {
                $this->getMedia()->getImageManager()->make($file);
            } catch (\Exception $Exception) {
                QUI\System\Log::addError($Exception->getMessage());
                throw new QUI\Exception([
                    'quiqqer/quiqqer',
                    'exception.image.upload.image.corrupted'
                ]);
            }
        }


        // mb_strtolower hat folgenden Grund: file_exists beachtet Gross und Kleinschreibung im Unix Systemen
        // Daher sind die Namen im Mediabereich alle klein geschrieben damit es keine Doppelten Dateien geben kann
        // Test.jpg und test.jpg wären unterschiedliche Dateien bei Windows aber nicht
        $filename = mb_strtolower($filename);

        // svg fix
        if ($fileinfo['mime_type'] == 'text/html'
            || $fileinfo['mime_type'] == 'text/plain'
        ) {
            $content = file_get_contents($file);

            if (strpos($content, '<svg') !== false && strpos($content, '</svg>')) {
                file_put_contents(
                    $file,
                    '<?xml version="1.0" encoding="UTF-8"?>'.
                    $content
                );

                $fileinfo = FileUtils::getInfo($file);
            }
        }

        // if no ending, we search for one
        if (!isset($fileinfo['extension']) || empty($fileinfo['extension'])) {
            $filename .= FileUtils::getEndingByMimeType($fileinfo['mime_type']);
        }

        $new_file = $this->getFullPath().'/'.$filename;
        $new_file = str_replace("//", "/", $new_file);

        // overwrite the file
        if (file_exists($new_file)) {
            if ($options != self::FILE_OVERWRITE_DESTROY
                && $options != self::FILE_OVERWRITE_TRUE
            ) {
                throw new QUI\Exception(
                    QUI::getLocale()->get('quiqqer/system', 'exception.media.file.already.exists', [
                        'filename' => $filename
                    ]),
                    705
                );
            }

            // overwrite file
            try {
                $Item = MediaUtils::getElement($new_file);

                if (MediaUtils::isImage($Item)) {
                    /* @var $Item QUI\Projects\Media\Image */
                    $Item->deleteCache();
                }

                $Item->deactivate();
                $Item->delete();

                if ($options == self::FILE_OVERWRITE_DESTROY) {
                    $Item->destroy();
                }
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addDebug(
                    $Exception->getMessage(),
                    ['file' => $new_file]
                );

                unlink($new_file);
            }
        }

        // copy the file to the media
        FileUtils::copy($file, $new_file);


        // create the database entry
        $User      = QUI::getUserBySession();
        $table     = $this->Media->getTable();
        $table_rel = $this->Media->getTable('relations');

        $new_file_info = FileUtils::getInfo($new_file);
        $title         = str_replace('_', ' ', $new_file_info['filename']);

        if (empty($new_file_info['filename'])) {
            $new_file_info['filename'] = time();
        }

        $filePath = $this->getAttribute('file').'/'.$new_file_info['basename'];

        if ($this->getId() == 1) {
            $filePath = $new_file_info['basename'];
        }

        $filePath    = StringUtils::replaceDblSlashes($filePath);
        $imageWidth  = null;
        $imageHeight = null;

        if (isset($new_file_info['width']) && $new_file_info['width']) {
            $imageWidth = (int)$new_file_info['width'];
        }

        if (isset($new_file_info['height']) && $new_file_info['height']) {
            $imageHeight = (int)$new_file_info['height'];
        }

        $data = [
            'name'         => $new_file_info['filename'],
            'title'        => $title,
            'short'        => '',
            'file'         => $filePath,
            'pathHash'     => \md5($filePath),
            'alt'          => $title,
            'c_date'       => date('Y-m-d h:i:s'),
            'e_date'       => date('Y-m-d h:i:s'),
            'c_user'       => $User->getId(),
            'e_user'       => $User->getId(),
            'mime_type'    => $new_file_info['mime_type'],
            'image_width'  => $imageWidth,
            'image_height' => $imageHeight,
            'type'         => MediaUtils::getMediaTypeByMimeType($new_file_info['mime_type'])
        ];

        $PDO = QUI::getPDO();

        if (!empty($customId)) {
            $data['id'] = (int)$customId;

            // disable AUTO_INCREMENT
//            $Statement = $PDO->prepare("ALTER TABLE {$table} MODIFY `id` BIGINT(20) NOT NULL");
//            $Statement->execute();

            QUI::getDataBase()->insert($table, $data);

            // re-enable AUTO_INCREMENT
//            $Statement = $PDO->prepare("ALTER TABLE {$table} MODIFY `id` BIGINT(20) NOT NULL AUTO_INCREMENT");
//            $Statement->execute();

            $id = $data['id'];
        } else {
            QUI::getDataBase()->insert($table, $data);
            $id = $PDO->lastInsertId();
        }

        QUI::getDataBase()->insert($table_rel, [
            'parent' => $this->getId(),
            'child'  => $id
        ]);

        /* @var $File QUI\Projects\Media\File */
        $File = $this->Media->get($id);
        $File->generateMD5();
        $File->generateSHA1();

        $maxSize = $this->getProject()->getConfig('media_maxUploadSize');

        // if it is an image, than resize -> if needed
        if (MediaUtils::isImage($File) && $maxSize) {
            /* @var $File QUI\Projects\Media\Image */
            $resizeData = $File->getResizeSize($maxSize, $maxSize);

            if ($new_file_info['width'] > $maxSize || $new_file_info['height'] > $maxSize) {
                $File->resize($resizeData['width'], $resizeData['height']);

                QUI::getDataBase()->update(
                    $table,
                    [
                        'image_width'  => $resizeData['width'],
                        'image_height' => $resizeData['height'],
                    ],
                    [
                        'id' => $id
                    ]
                );
            }

            $File->setEffects($this->getEffects());
        }

        $File->save();

        return $File;
    }
}
