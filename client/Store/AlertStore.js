let AlertStore = {
    _state: {
        alerts: []
    },

    getState: function() {
        return {
            alerts: this._state.alerts.slice()
        }
    },

    clear: function(){
        this._state.alerts = [];
        this.onChange();
    },

    onChange: function() {}
};

const push = function(level, message) {
    this._state.alerts = [];
    this._state.alerts.push({level:level, message: message});
    this.onChange();
};

AlertStore.success = push.bind(AlertStore, "success");
AlertStore.warning = push.bind(AlertStore, "warning");
AlertStore.error = push.bind(AlertStore, "error");

module.exports = AlertStore;
