function decode(response){
    let json = response.json(); // there's always a body
    if (response.status >= 200 && response.status < 300) {
        return json
    } else {
        return json.then(Promise.reject.bind(Promise));
        // aka json.then(err => {throw err;})
    }
}

module.exports = {
    fetch: (url, opts)=>{
        opts = opts || {};
        opts.credentials = 'include';
        opts.headers = {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        };

        return window.fetch(url, opts).then(decode);
    },
    queryParams: function(params) {
        return Object.keys(params)
            .map(k => encodeURIComponent(k) + '=' + encodeURIComponent(params[k]))
            .join('&');
    }
};
