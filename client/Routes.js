import React from 'react'
import {Route} from 'react-router-dom'

import Operations from './View/Operations'
import Operation from './View/Operation'
import Location from './View/Location'
import Product from './View/Product'
import OperationSet from './View/OperationSet'

const withId = (WrappedComponent) => ({match, ...props}) => (<WrappedComponent id={match.params.id} {...props}/>);

export {withId};

// match.params.id
let key = 0;
const routes = (RouteCp = Route) => [
    <RouteCp key={key++} path="/operations"  component={Operations} />,
    <RouteCp key={key++} path="/operation/:id" component={withId(Operation)} />,
    <RouteCp key={key++} path="/location/:id" component={withId(Location)} />,
    <RouteCp key={key++} path="/product/:id" component={withId(Product)} />,
    <RouteCp key={key++} path="/operation-set/:id" component={withId(OperationSet)} />
];

export default routes


