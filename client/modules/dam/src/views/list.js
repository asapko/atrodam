

Espo.define('dam:views/list', 'views/list',
    Dep => Dep.extend({

        setup() {
            this.quickCreate = this.getMetadata().get(`clientDefs.${this.scope}.quickCreate`);

            Dep.prototype.setup.call(this);
        },

        navigateToEdit(id) {
            let router = this.getRouter();
            let url = `#${this.scope}/view/${id}`;
            let canEdit =  this.getAcl().check(this.scope, 'edit');

            router.dispatch(this.scope, 'view', {
                id: id,
                mode: 'edit',
                setEditMode: canEdit
            });
            router.navigate(url, {trigger: false});
        },

        actionQuickCreate() {
            let options = _.extend({
                scope: this.scope,
                attributes: this.getCreateAttributes() || {}
            }, this.getMetadata().get(`clientDefs.${this.scope}.quickCreateOptions`) || {})

            this.notify('Loading...');
            let viewName = this.getMetadata().get('clientDefs.' + this.scope + '.modalViews.edit') || 'views/modals/edit';

            this.createView('quickCreate', viewName, options, function (view) {
                view.render();
                view.notify(false);
                this.listenToOnce(view, 'after:save', function () {
                    this.collection.fetch();

                    if (this.getMetadata().get(`clientDefs.${this.scope}.navigateToEntityAfterQuickCreate`)) {
                        this.navigateToEdit(view.getView('edit').model.id);
                    }
                }, this);
            }.bind(this));
        }
    })
);

