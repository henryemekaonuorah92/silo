;
import React from 'react';

/**
 * @see https://github.com/petermoresi/react-download-link/blob/master/download-link.es6
 */
module.exports = React.createClass({

    propTypes: {
        filename: React.PropTypes.string,
        exportFile: React.PropTypes.func
    },

    getDefaultProps() {
        return {
            filename: 'file.txt',
            exportFile: () => {}
        }
    },

    handleDownloadClick: function(event) {

        function magicDownload(text, fileName){
            let blob = new Blob([text], {
                type: 'text/csv;charset=utf8;'
            });

            // create hidden link
            let element = document.createElement('a');
            document.body.appendChild(element);
            element.setAttribute('href', window.URL.createObjectURL(blob));
            element.setAttribute('download', fileName);
            element.style.display = '';

            element.click();

            document.body.removeChild(element);
            event.stopPropagation();
        }

        let fileType = event.target.innerText,
            text = this.props.exportFile(fileType);

        if (text instanceof Promise) {
            text.then(
                result => magicDownload(result, this.props.filename)
            )
        } else {
            magicDownload(text, this.props.filename)
        }

    },

    render: function() {
        const rest = Object.assign({}, this.props);
        delete rest.filename; delete rest.label; delete rest.exportFile;
        return (
            <a onClick={this.handleDownloadClick} {...rest}>
                {this.props.children}
            </a>
        );
    }
});
