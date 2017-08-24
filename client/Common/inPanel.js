const React = require('react');
const Measure = require('react-measure');

/**
 * HOC put WrappedComponent inside a Bootstrap Panel body and auto scale it to width by setting "width" prop
 * Perfect for fixed-table Table
 *
 * @param WrappedComponent
 * @returns {*}
 */
module.exports = (WrappedComponent)=>{
    return React.createClass({

        displayName: "inPanel",

        getInitialState: ()=>({
            dimensions: {
                width: -1,
                height: -1,
            }
        }),

        render: function(){
            return <Measure onMeasure={dimensions=>{this.setState({dimensions});}}>
                <div className="panel-body panel-body_noPadding">
                    {this.state.dimensions.width != -1 && (
                        <WrappedComponent
                            width={this.state.dimensions.width} // Bootstrap 15px padding on row
                            {...this.props} />
                    )}
                </div>
            </Measure>
        },
    });
};
