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

Espo.define('dam:views/asset/fields/file', 'views/fields/file',
    Dep => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, "change:name", () => {
                if (this.model.get('name')) {
                    const name = this.model.get('name');
                    const ext = (this.model.get('fileName') || '').split('.').pop();

                    if (!name.endsWith('.' + ext)) {
                        this.model.set('name', name + '.' + ext, {silent: true});
                        this.model.set('fileName', this.model.get('name'));
                    }

                    this.reRender();
                }
            });

            this.listenTo(this.model, "after:save", () => {
                this.reRender();
            });
        }
    })
);