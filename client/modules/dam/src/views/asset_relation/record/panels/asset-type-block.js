

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

Espo.define('dam:views/asset_relation/record/panels/asset-type-block', 'view',
    Dep => Dep.extend({
        template: "dam:asset_relation/record/panels/asset-type-block",
        sort    : false,
        show    : true,
        
        setup() {
            Dep.prototype.setup.call(this);
            this.sort = this.options.sort || false;
            this.createHeaderBlock();
            
            this.listenTo(this, "after:render", () => {
                this.showInfo();
            });
        },
        
        createHeaderBlock() {
            this.createView("headerBlock", "dam:views/asset_relation/record/panels/header", {
                model: this.model,
                el   : this.options.el + " .group-name",
                show : this.show
            });
        },
        
        showInfo() {
            this.show = true;
            if (this.collection) {
                this.collection.fetch().then(() => {
                    this.getView("list").reRender();
                });
            } else {
                this.getCollectionFactory().create("AssetRelation", (collection) => {
                    collection.url = `AssetRelation/byEntity/${this.model.get('entityName')}/${this.model.get('entityId')}?type=${this.model.get('name')}`;
                    collection.sortBy = "";
                    this.collection = collection;
                    this.waitForView("list");
                    this.createView('list', "dam:views/asset_relation/record/list", {
                        collection          : this.collection,
                        model               : this.model,
                        buttonsDisabled     : true,
                        checkboxes          : false,
                        el                  : this.options.el + ' .list-container',
                        layoutName          : "listSmall",
                        dragableListRows    : this.sort,
                        listRowsOrderSaveUrl: `AssetRelation/${this.model.get('entityName')}/${this.model.get('entityId')}/sortOrder`,
                        listLayout          : null,
                        skipBuildRows       : true,
                        rowActionsView      : this.model.get('rowActionsView') ? this.model.get('rowActionsView') : this.rowActionsView,
                    }, function (view) {
                        view.listenTo(collection, "sync", () => {
                            $(view.el).find('.list').slideDown("fast");
                            this.model.get('entityModel').fetch();
                        });
                        view.listenTo(view, "after:model-remove", () => {
                            let parent = this.getParentView();
                            parent.actionRefresh();
                            parent._refreshAssetPanel();
                            this.model.get('entityModel').fetch();
                        });
                        if (this.getMetadata().get(['scopes', this.model.get('entityName'), 'advancedFilters'])) {
                            view.listenTo(view, 'after:render', () => {
                                this.model.trigger("advanced-filters");
                            });
                        }
                        collection.fetch();
                    });
                });
            }
        },
        
        hideInfo() {
            this.show = false;
            if (this.hasView("list")) {
                let view = this.getView("list");
                $(view.el).find(".list").slideUp("fast");
            }
        }
    })
);