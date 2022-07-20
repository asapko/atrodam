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
 *
 * This software is not allowed to be used in Russia and Belarus.
 */

declare(strict_types=1);

namespace Dam\Services;

use Dam\Core\ConfigManager;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Templates\Services\Base;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Log;
use Espo\ORM\Entity;
use Treo\Core\EventManager\Event;

/**
 * Class Asset
 *
 * @package Dam\Services
 */
class Asset extends Base
{
    /**
     * @var string[]
     */
    protected $mandatorySelectAttributeList = ['fileId', 'type'];

    /**
     * @inheritDoc
     */
    public function prepareEntityForOutput(Entity $entity)
    {
        parent::prepareEntityForOutput($entity);

        $file = $entity->get('file');
        if (!empty($file)) {
            $entity->set('icon', $this->prepareAssetIcon((string)$file->get('name')));
            $entity->set('private', $file->get('private'));

            $pathData = $this->getEntityManager()->getRepository('Attachment')->getAttachmentPathsData($entity->get('fileId'));
            if (!empty($pathData['download'])) {
                $entity->set('url', rtrim($this->getConfig()->get('siteUrl', ''), '/') . '/' . $pathData['download']);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function isRequiredField(string $field, Entity $entity, $typeResult): bool
    {
        if ($field == 'filesIds') {
            return false;
        }

        return parent::isRequiredField($field, $entity, $typeResult);
    }

    /**
     * @inheritDoc
     */
    public function createEntity($data)
    {
        if (property_exists($data, 'url') && !empty($data->url)) {
            if (empty($attachment = $this->getService('Attachment')->createEntityByUrl($data->url, false))) {
                throw new BadRequest(sprintf($this->translate('wrongUrl', 'exceptions', 'Asset'), $data->url));
            }

            if (!empty($asset = $this->getEntityManager()->getRepository('Asset')->where(['fileId' => $attachment->get('id')])->findOne())) {
                if (property_exists($data, 'type') && $data->type !== $asset->get('type')) {
                    throw new BadRequest(sprintf($this->translate('assetExistWithOtherType', 'exceptions', 'Asset'), $data->url));
                }
                return $this->readEntity($asset->get('id'));
            }

            $data->name = $attachment->get('name');
            $data->fileId = $attachment->get('id');

            unset($data->url);
        }

        $entity = $this->getRepository()->get();
        $entity->set($data);

        // Are all required fields filled ?
        $this->checkRequiredFields($entity, $data);

        if ($this->isMassCreating($entity)) {
            return $this->massCreateAssets($data);
        }

        return parent::createEntity($data);
    }

    public function updateEntity($id, $data)
    {
        if (property_exists($data, '_sortedIds') && property_exists($data, '_id') && property_exists($data, '_scope') && property_exists($data, '_link')) {
            $this->getRepository()->updateSortOrder($data->_id, $data->_sortedIds, $data->_scope, $data->_link);
            return $this->getEntity($id);
        }

        return parent::updateEntity($id, $data);
    }

    protected function updateRelationData(Entity $entity, \stdClass $data): void
    {
        if (property_exists($data, '_relationEntityId') && property_exists($data, 'sorting') && $data->sorting === null) {
            $data->sorting = $this->getRepository()->getNextSorting($data->_relationEntity, $data->_relationName, $data->_relationEntityId);
        }

        parent::updateRelationData($entity, $data);
    }

    /**
     * @param \Dam\Entities\Asset $asset
     *
     * @return mixed
     */
    public function updateMetaData(\Dam\Entities\Asset $asset)
    {
        $attachment = $asset->get('file');

        $metaData = $this->getServiceFactory()->create("Attachment")->getFileMetaData($attachment);

        return $this->getService("AssetMetaData")->insertData("asset", $asset->id, $metaData);
    }

    /**
     * @param \Dam\Entities\Asset $asset
     */
    public function getFileInfo(\Dam\Entities\Asset $asset)
    {
        $fileInfo = $this->getService("Attachment")->getFileInfo($asset->get("file"));

        $asset->set(
            [
                "size"     => round($fileInfo['size'] / 1024, 1),
                "sizeUnit" => "kb",
            ]
        );

        if ($this->isImage($asset)) {
            $imageInfo = $this->getService("Attachment")->getImageInfo($asset->get("file"));
            $this->updateAttributes($asset, $imageInfo);
        }
    }

    /**
     * @param \Dam\Entities\Asset $asset
     * @param array               $imageInfo
     */
    public function updateAttributes(\Dam\Entities\Asset $asset, array $imageInfo)
    {
        $asset->set(
            $this->attributeMapping("height"),
            ($imageInfo['height'] ?? false) ? $imageInfo['height'] : null
        );
        $asset->set(
            $this->attributeMapping("width"),
            ($imageInfo['width'] ?? false) ? $imageInfo['width'] : null
        );
        $asset->set(
            $this->attributeMapping("color-space"),
            ($imageInfo['color_space'] ?? false) ? $imageInfo['color_space'] : null
        );
        $asset->set(
            $this->attributeMapping("color-depth"),
            ($imageInfo['color_depth'] ?? false) ? $imageInfo['color_depth'] : null
        );
        $asset->set(
            $this->attributeMapping("orientation"),
            ($imageInfo['orientation'] ?? false) ? $imageInfo['orientation'] : null
        );
    }

    /**
     * @param \Dam\Entities\Asset $main
     * @param \Dam\Entities\Asset $foreign
     *
     * @return mixed
     */
    public function linkToAsset(\Dam\Entities\Asset $main, \Dam\Entities\Asset $foreign)
    {
        if ($main->id === $foreign->id) {
            throw new BadRequest($this->translate("JoinMainAsset", "exceptions", "Asset"));
        }

        return $this->getRepository()->linkAsset($main, $foreign);
    }

    /**
     * @param \Dam\Entities\Asset $main
     * @param \Dam\Entities\Asset $foreign
     *
     * @return mixed
     */
    public function unlinkToAsset(\Dam\Entities\Asset $main, \Dam\Entities\Asset $foreign)
    {
        return $this->getRepository()->unlinkAsset($main, $foreign);
    }

    protected function massCreateAssets(\stdClass $data): Entity
    {
        $inTransaction = false;
        if (!$this->getEntityManager()->getPDO()->inTransaction()) {
            $this->getEntityManager()->getPDO()->beginTransaction();
            $inTransaction = true;
        }

        $entity = $this->getRepository()->get();

        try {
            foreach ($data->filesIds as $fileId) {
                $fileName = $data->filesNames->$fileId;

                $postData = clone $data;
                $postData->fileId = $fileId;
                $postData->fileName = $fileName;
                $postData->name = $fileName;
                $postData->sorting = null;

                unset($postData->filesIds);
                unset($postData->filesNames);
                unset($postData->filesTypes);

                $entity = parent::createEntity($postData);
            }

            if ($inTransaction) {
                $this->getEntityManager()->getPDO()->commit();
            }
        } catch (\Throwable $e) {
            if ($inTransaction) {
                $this->getEntityManager()->getPDO()->rollBack();
            }
            throw new BadRequest("{$fileName}: {$e->getMessage()}");
        }

        return $entity;
    }

    /**
     * @inheritDoc
     */
    protected function init()
    {
        parent::init();

        $this->addDependency("language");
        $this->addDependency("configManager");
        $this->addDependency('log');
        $this->addDependency('eventManager');
        $this->addDependency('queueManager');
    }

    protected function prepareUrl(string $downloadPath): string
    {
        return rtrim($this->getConfig()->get('siteUrl', ''), '/') . '/' . $downloadPath;
    }

    /**
     * @return ConfigManager
     */
    protected function getConfigManager(): ConfigManager
    {
        return $this->getInjection("configManager");
    }

    /**
     * @return Log
     */
    protected function getLog(): Log
    {
        return $this->getInjection("log");
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    protected function getService($name)
    {
        return $this->getServiceFactory()->create($name);
    }

    /**
     * @param Entity $entity
     *
     * @return array
     */
    protected function checkIssetLink(Entity $entity)
    {
        $list = [];

        foreach ($entity->getRelations() as $key => $relation) {
            if ($this->isMulti($entity, $relation, $key)) {
                $list[] = [
                    "entityName" => $relation['entity'],
                    "entityId"   => $entity->get($relation['key']),
                ];
            }
        }

        return $list;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    protected function skipEntityAssets(string $key)
    {
        return !$this->getMetadata()->get(['entityDefs', 'Asset', 'links', $key, 'entityAsset']);
    }

    /**
     * @param Entity $entity
     * @param        $relation
     * @param        $key
     *
     * @return bool
     */
    protected function isMulti(Entity $entity, $relation, $key): bool
    {
        return $relation['type'] === "belongsTo"
            && $entity->isAttributeChanged($relation['key'])
            && $key !== "ownerUser"
            && !$this->skipEntityAssets($key);
    }

    protected function attributeMapping(string $name): string
    {
        return $this->getConfigManager()->get(["attributeMapping", $name, "field"]) ?? $name;
    }

    protected function translate(string $label, string $category, string $scope): string
    {
        return $this->getInjection("language")->translate($label, $category, $scope);
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    protected function isMassCreating(Entity $entity): bool
    {
        return !empty($entity->get('filesIds')) && $entity->isNew();
    }

    protected function prepareAssetIcon(string $fileName): ?string
    {
        $fileNameParts = explode('.', $fileName);
        $fileExt = strtolower(array_pop($fileNameParts));

        return in_array($fileExt, $this->getMetadata()->get('fields.asset.hasPreviewExtensions', [])) ? null : $fileExt;
    }

    protected function isImage(Entity $asset): bool
    {
        $fileNameParts = explode('.', $asset->get("file")->get('name'));
        $fileExt = strtolower(array_pop($fileNameParts));

        return in_array($fileExt, $this->getMetadata()->get('dam.image.extensions', []));
    }
}
