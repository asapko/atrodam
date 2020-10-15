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

namespace Dam\Controllers;

use Dam\Core\ConfigManager;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Utils\Json;

/**
 * Class DamConfig
 * @package Dam\Controllers
 */
class DamConfig extends AbstractController
{
    /**
     * @param $params
     * @param $data
     * @param $request
     * @return mixed
     */
    public function actionRead($params, $data, $request)
    {
        return $this->getConfigManager()->getConfig();
    }

    /**
     * @param $params
     * @param $data
     * @param $request
     * @return false|string
     * @throws NotFound
     */
    public function actionReadYaml($params, $data, $request)
    {
        if ($config = $this->getDamConfigService()->getYamlConfig()) {
            return Json::encode([
                "content" => $config,
            ]);
        }

        throw new NotFound();
    }

    /**
     * @param $params
     * @param $data
     * @param $request
     * @return mixed
     * @throws BadRequest
     */
    public function actionSaveYaml($params, $data, $request)
    {
        $service = $this->getDamConfigService();

        if ($service->validateYaml($data->content) && $service->saveYaml($data->content)) {

            $res = $this->getDamConfigService()->convertYamlToArray(yaml_parse($data->content));

            if ($res === false) {
                return Json::encode([
                    "result" => "error",
                ]);
            }

            $this->getConfig()->updateCacheTimestamp();
            $this->getConfig()->save();

            return Json::encode([
                "result" => "ok",
            ]);
        }

        throw new BadRequest();
    }

    /**
     * @return ConfigManager
     */
    protected function getConfigManager(): ConfigManager
    {
        return $this->getContainer()->get("ConfigManager");
    }

    protected function getDamConfigService(): \Dam\Services\DamConfig
    {
        return $this->getService("DamConfig");
    }
}