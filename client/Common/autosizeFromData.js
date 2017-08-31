const React = require('react');

module.exports = (WrappedComponent)=>{
    return ({children, data, ...props}) => {
        const rowHeight = "rowHeight" in props ? props.rowHeight : 36;
        return <WrappedComponent
                    height={Math.min(data.length + 3, 12) * rowHeight}
                    headerHeight={30}
                    offsetHeight={150}
                    rowsCount={data.length}
                    rowHeight={rowHeight}
                    {...props}>{children}</WrappedComponent>
        ;
    }
};
