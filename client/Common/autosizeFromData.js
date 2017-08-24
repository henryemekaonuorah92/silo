const React = require('react');

module.exports = (WrappedComponent)=>{
    return ({children, data, ...props}) => <WrappedComponent
                height={Math.min(data.length + 3, 12) * 36}
                headerHeight={30}
                offsetHeight={150}
                rowsCount={data.length}
                rowHeight={36}
                {...props}>{children}</WrappedComponent>
    ;
};
