;
const ModifierFactory = function(){
    this.views = {};
    this.editors = {};
    this.add('boxable', null, require('./TestEditor').Editor);
};

ModifierFactory.prototype = {
    add: function(name, view, editor){
        if (view) {
            this.views[name] = view;
        }
        if (editor) {
            this.editors[name] = editor;
        }
    },
    listEditors: function(){
        return Object.keys(this.editors);
    },
    getView: function(name) {
        return this.views.hasOwnProperty(name) ? this.views[name] : null;
    },
    getEditor: function(name) {
        return this.editors.hasOwnProperty(name) ? this.editors[name] : null;
    },
};

module.exports = new ModifierFactory;
