

Espo.define('dam:views/collection/record/panels/asset_category', 'views/record/panels/relationship',
    Dep => Dep.extend({
        
        boolFilterData: {
            notEntity() {
                return (this.collection || []).map(model => model.id);
            },
            notChildCategory : function () {
                return true;
            }
        },
        
        setup() {
            Dep.prototype.setup.call(this);
            let select = this.actionList.find(item => item.action === (this.defs.selectAction || 'selectRelated'));
            
            if (select) {
                select.data = {
                    link: this.link,
                    scope: this.scope,
                    boolFilterListCallback: 'getSelectBoolFilterList',
                    boolFilterDataCallback: 'getSelectBoolFilterData',
                    primaryFilterName: this.defs.selectPrimaryFilterName || null
                };
            }
        },
        
        getSelectBoolFilterData(boolFilterList) {
            let data = {};
            if (Array.isArray(boolFilterList)) {
                boolFilterList.forEach(item => {
                    if (this.boolFilterData && typeof this.boolFilterData[item] === 'function') {
                        data[item] = this.boolFilterData[item].call(this);
                    }
                });
            }
            return data;
        },
        
        getSelectBoolFilterList() {
            return this.defs.selectBoolFilterList || null
        },
        
    })
);
