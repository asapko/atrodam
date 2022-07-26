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
 *  This software is not allowed to be used in Russia and Belarus.
 */

declare(strict_types=1);

namespace Dam;

use Espo\Core\Utils\DataUtil;
use Treo\Core\ModuleManager\AbstractModule;

/**
 * Class Module
 */
class Module extends AbstractModule
{
    /**
     * @inheritdoc
     */
    public static function getLoadOrder(): int
    {
        return 5119;
    }

    public function onLoad()
    {
        parent::onLoad();

        $this->container->setClassAlias('configManager', \Dam\Core\ConfigManager::class);
        $this->container->setClassAlias('thumbnail', \Dam\Core\Thumbnail\Image::class);
        $this->container->setClassAlias('validator', \Dam\Core\Validation\Validator::class);
    }

    /**
     * @inheritdoc
     */
    public function loadMetadata(\stdClass &$data)
    {
        $metadata = $this
            ->getObjUnifier()
            ->unify('metadata', $this->path . 'app/Resources/metadata', true);

        // checking if module PIM installed
        if ($this->container->get('metadata')->isModuleInstalled('PIM')) {
            unset($metadata->themes);
        }

        $data = DataUtil::merge($data, $metadata);

    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
}
