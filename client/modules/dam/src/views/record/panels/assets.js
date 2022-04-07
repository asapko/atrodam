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

Espo.define('dam:views/record/panels/assets', 'views/record/panels/relationship',
    Dep => Dep.extend({

        actionSetAsMainImage: function (data) {
            const pathData = window.location.hash.replace('#', '').split('/view/');

            let inputData = {
                isMainImage: true,
                _relationEntity: pathData.shift(),
                _relationEntityId: pathData.pop(),
                _relationName: this.panelName
            };

            this.notify('Saving...');
            this.ajaxPutRequest(`Asset/${data.asset_id}`, inputData).done(asset => {
                this.model.trigger('asset:saved');
                this.notify('Saved', 'success');
                this.actionRefresh();
            });
        },

    })
);

