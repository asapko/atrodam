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

Espo.define('dam:views/fields/code-from-name', 'views/fields/varchar',
    Dep => Dep.extend({

        getPatternValidationMessage() {
            return this.translate('fieldHasPattern', 'messages').replace('{field}', this.translate(this.name, 'fields', this.model.name));
        },
        
        setup() {
            Dep.prototype.setup.call(this);
            
            this.listenTo(this.model, 'change:name', () => {
                if (!this.model.get('code') || this.model.isNew()) {
                    this.model.set(this.name, this.transformToPattern(this.model.get('name')));
                    
                }
            });
        },

        transformToPattern(value) {
            let result = value
                .toLowerCase()
                .replace(/ /g, '_');

            return this.replaceDiacriticalCharacters(result).replace(/[^a-z_0-9]/gu, '');
        },

        replaceDiacriticalCharacters(value) {
            let diacritalSymbolsReplaceMap = {
                'a': 'ÀÁÂÃÅÆĀĂĄàáâãåæāăą',
                'ae': 'Ää',
                'c': 'ÇĆĈĊČçćĉċč',
                'd': 'ĎĐďđ',
                'e': 'ÈÉÊËÐĒĔĖĘĚèéêëðēĕėęě',
                'g': 'ĜĞĠĢĝğġģ',
                'h': 'ĤĦĥħ',
                'i': 'ÌÍÎÏĨĪĬĮİĲìíîïĩīĭįıĳ',
                'j': 'Ĵĵ',
                'k': 'Ķķĸ',
                'l': 'ĹĻĽĿŁĺļľŀł',
                'n': 'ÑŃŅŇŊñńņňŉŋ',
                'o': 'ÒÓÔÕØŌŎŐŒòóôõøōŏőœ',
                'oe': 'Öö',
                'p': 'Þþ',
                'r': 'ŔŖŘŕŗř',
                's': 'ŚŜŞŠśŝşšſ',
                'ss': 'ẞß',
                't': 'ŢŤŦţťŧ',
                'u': 'ÙÚÛŨŪŬŮŰŲùúûũūŭůűų',
                'ue': 'Üü',
                'w': 'Ŵŵ',
                'y': 'ÝŶŸýÿŷ',
                'z': 'ŹŻŽźżž'
            };

            let replaceMap = {};
            for (let letter in diacritalSymbolsReplaceMap) {
                let replaces = diacritalSymbolsReplaceMap[letter];

                for (let j = 0; j < replaces.length; j++) {
                    replaceMap[replaces[j]] = letter;
                }
            }

            return value.replace(/[^\u0000-\u007F]/g, function (l) {
                return replaceMap[l] || '';
            })
        }
        
    })
);
