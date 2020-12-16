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

namespace Dam\Core\Validation\Items;

use Dam\Core\Validation\Base;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;

/**
 * Class Mime
 *
 * @package Dam\Core\Validation\Items
 */
class Mime extends Base
{
    /**
     * @return bool
     */
    public function validate(): bool
    {
        $mimeType = (string)mime_content_type($this->getFilePath());

        if (isset($this->params['list'])) {
            return in_array($mimeType, $this->params['list']);
        } elseif (isset($this->params['pattern'])) {
            return stripos($mimeType, $this->params['pattern']) === false ? false : true;
        }

        return true;
    }

    /**
     * @throws BadRequest
     */
    public function onValidateFail()
    {
        throw new BadRequest($this->exception('Asset has incorrect MIME type.'));
    }
}