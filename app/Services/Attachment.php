<?php
/*
 *  This file is part of AtroDAM.
 *
 *  AtroDAM - Open Source DAM application.
 *  Copyright (C) 2020 AtroCore UG (haftungsbeschränkt).
 *  Website: https://atrodam.com
 *
 *  AtroDAM is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  AtroDAM is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with AtroDAM. If not, see http://www.gnu.org/licenses/.
 *
 *  The interactive user interfaces in modified source and object code versions
 *  of this program must display Appropriate Legal Notices, as required under
 *  Section 5 of the GNU General Public License version 3.
 *
 *  In accordance with Section 7(b) of the GNU General Public License version 3,
 *  these Appropriate Legal Notices must retain the display of the "AtroDAM" word.
 */

declare(strict_types=1);

namespace Dam\Services;

use Dam\Core\ConfigManager;
use Dam\Core\Validation\Validator;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\ORM\Entity;
use Imagick;
use Treo\Core\FileStorage\Manager;

/**
 * Class Attachment
 *
 * @package Dam\Services
 */
class Attachment extends \Espo\Services\Attachment
{
    /**
     * Attachment constructor.
     */
    public function __construct()
    {
        $this->addDependency("Validator");
        $this->addDependency("DAMFileManager");
        $this->addDependency("fileStorageManager");
        $this->addDependency("ConfigManager");
        $this->addDependency("filePathBuilder");

        parent::__construct();
    }

    /**
     * @param      $attachment
     * @param null $path
     * @return array
     * @throws \ImagickException
     * @throws \ReflectionException
     */
    public function getImageInfo($attachment, $path = null): array
    {
        if (stripos($attachment->get("type"), "image/") === false) {
            return [];
        }

        $path = $path ?? $this->getPath($attachment);

        $image = new Imagick($path);

        if ($imageInfo = getimagesize($path)) {
            $result = [
                "width"       => $image->getImageWidth(),
                "height"      => $image->getImageHeight(),
                "color_space" => self::getColorSpace($image),
                "color_depth" => $image->getImageDepth(),
                'orientation' => $this->getPosition($image->getImageWidth(), $image->getImageHeight()),
                'mime'        => $image->getImageMimeType(),
            ];
        }

        return $result ?? [];
    }

    /**
     * @param      $attachment
     * @param null $path
     * @return array
     */
    public function getFileInfo($attachment, $path = null): array
    {
        $path = $path ?? $this->getPath($attachment);

        if ($pathInfo = pathinfo($path)) {
            $result['extension'] = $pathInfo['extension'];
            $result['base_name'] = $pathInfo['basename'];
        }

        $result['size'] = filesize($path);

        return $result;
    }

    /**
     * @param $attachment
     *
     * @return mixed
     * @throws \Espo\Core\Exceptions\BadRequest
     * @throws \Espo\Core\Exceptions\Error
     * @throws \Espo\Core\Exceptions\Forbidden
     */
    public function createEntity($attachment)
    {
        $entity = parent::createEntity($attachment);

        try {
            $this->validateAttachment($attachment, $entity);
        } catch (\Exception $exception) {
            $this->getFileManager()->removeFile([$entity->get('tmpPath')]);
            /**@var $repo \Dam\Repositories\Attachment* */
            $repo = $this->getRepository();
            $repo->deleteFromDb($entity->id);

            throw $exception;
        }

        return $entity;
    }

    /**
     * @param string $attachmentId
     * @return bool
     */
    public function toDelete(string $attachmentId)
    {
        $entity = $this->getEntity($attachmentId);

        if (!$entity) {
            return false;
        }

        $entity->set("deleted", true);

        return $this->getRepository()->save($entity);
    }

    public function deleteAttachment($attachmentId, $entityType = null)
    {
        $attachmentRepository = $this->getRepository();

        $where = [
            'id' => $attachmentId
        ];

        if ($entityType) {
            $where["relatedType"] = $entityType;
        }

        $attachment = $attachmentRepository->where($where)->findOne();

        if (!$attachment) {
            return false;
        }

        return $attachmentRepository->remove($attachment);
    }

    /**
     * @param \Dam\Entities\Attachment $attachment
     * @param string                   $newName
     * @return mixed
     */
    public function changeName(\Dam\Entities\Attachment $attachment, string $newName)
    {
        return $this->getRepository()->renameFile($attachment, $newName);
    }

    /**
     * @param \Dam\Entities\Attachment $attachment
     * @return array|mixed
     * @throws Error
     * @throws \ImagickException
     */
    public function getFileMetaData(\Dam\Entities\Attachment $attachment)
    {
        $mime = $attachment->get('type');
        $meta = [];

        switch (true) {
            case (stripos($mime, "image") !== false):
                $meta = $this->getImageMeta($attachment);
                break;
        }

        return $meta;
    }

    /**
     * @param \Dam\Entities\Attachment $attachment
     * @return array
     * @throws Error
     * @throws \ImagickException
     */
    public function getImageMeta(\Dam\Entities\Attachment $attachment)
    {
        $path = $this->getFileStorageManager()->getLocalFilePath($attachment);

        $imagick = new \Imagick();
        $imagick->readImage($path);

        return $imagick->getImageProperties();
    }

    /**
     * @param $attachment
     * @throws BadRequest
     * @throws Error
     * @throws Forbidden
     */
    protected function validateAttachment($attachment, $entity)
    {
        if (!empty($attachment->file)) {
            $arr      = explode(',', $attachment->file);
            $contents = '';
            if (count($arr) > 1) {
                $contents = $arr[1];
            }

            $contents             = base64_decode($contents);
            $attachment->contents = $contents;

            $relatedEntityType = null;
            $field             = null;
            $role              = 'Attachment';
            if (isset($attachment->parentType)) {
                $relatedEntityType = $attachment->parentType;
            } elseif (isset($attachment->relatedType)) {
                $relatedEntityType = $attachment->relatedType;
            }
            if (isset($attachment->field)) {
                $field = $attachment->field;
            }

            if (isset($attachment->role)) {
                $role = $attachment->role;
            }
            if (!$relatedEntityType || !$field) {
                throw new BadRequest("Params 'field' and 'parentType' not passed along with 'file'.");
            }

            $fieldType = $this->getMetadata()->get(['entityDefs', $relatedEntityType, 'fields', $field, 'type']);
            if (!$fieldType) {
                throw new Error("Field '{$field}' does not exist.");
            }

            if ($this->hasAcl($relatedEntityType)) {
                throw new Forbidden("No access to " . $relatedEntityType . ".");
            }

            if (in_array($field, $this->getAcl()->getScopeForbiddenFieldList($relatedEntityType, 'edit'))) {
                throw new Forbidden("No access to field '" . $field . "'.");
            }

            if ($role === 'Attachment') {
                if (!in_array($fieldType, $this->attachmentFieldTypeList)) {
                    throw new Error("Field type '{$fieldType}' is not allowed for attachment.");
                }

                if (isset($attachment->modelAttributes)) {

                    $model   = $attachment->modelAttributes;
                    $private = $model->private ? "private" : "public";

                    $config = $this->getConfigManager()->getByType([ConfigManager::getType($model->type)]);

                    foreach ($config['validations'] as $type => $value) {
                        $this->getValidator()->validate($type, $entity, ($value[$private] ?? $value));
                    }
                }
            }
        }
    }

    /**
     * @return Validator
     */
    protected function getValidator(): Validator
    {
        return $this->getInjection("Validator");
    }

    /**
     * @param \Espo\Entities\Attachment $attachment
     *
     * @return mixed
     */
    private function getPath(\Espo\Entities\Attachment $attachment)
    {
        if ($attachment->get('sourceId')) {
            $attachment = $this->getRepository()->where(['id' => $attachment->get('sourceId')])->findOne();
        }

        if ($attachment->get("tmpPath")) {
            return $attachment->get("tmpPath");
        } else {
            return $this->getRepository()->getFilePath($attachment);
        }
    }

    /**
     * @param $width
     * @param $height
     *
     * @return string
     */
    private function getPosition($width, $height): string
    {
        $result = "Square";

        if ($width > $height) {
            $result = "Landscape";
        } elseif ($width < $height) {
            $result = "Portrait";
        }

        return $result;
    }

    /**
     * @param $relatedEntityType
     *
     * @return bool
     */
    private function hasAcl($relatedEntityType): bool
    {
        return !$this->getAcl()->checkScope($relatedEntityType,
                'create') && !$this->getAcl()->checkScope($relatedEntityType, 'edit');
    }

    /**
     * @return FileManager
     */
    protected function getFileManager(): FileManager
    {
        return $this->getInjection("DAMFileManager");
    }

    /**
     * @return Manager
     */
    protected function getFileStorageManager(): Manager
    {
        return $this->getInjection("fileStorageManager");
    }

    /**
     * @return ConfigManager
     */
    protected function getConfigManager(): ConfigManager
    {
        return $this->getInjection("ConfigManager");
    }

    /**
     * @return FilePathBuilder
     */
    protected function getFilePathBuilder(): FilePathBuilder
    {
        return $this->getInjection("filePathBuilder");
    }

    /**
     * @param Imagick $imagick
     *
     * @return string|null
     */
    public static function getColorSpace(Imagick $imagick): ?string
    {
        $colorId = $imagick->getImageColorspace();

        if (!$colorId) {
            return null;
        }

        foreach ((new \ReflectionClass($imagick))->getConstants() as $name => $value) {
            if (stripos($name, "COLORSPACE_") !== false && $value == $colorId) {
                $el = explode("_", $name);
                array_shift($el);

                return implode("_", $el);
            }
        }

        return null;
    }
}
