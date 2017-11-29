import React from 'react'
import {Route} from 'react-router-dom'

import Operations from './View/Operations'
import Operation from './View/Operation'
import Location from './View/Location'
import Product from './View/Product'
import OperationSet from './View/OperationSet'

// match.params.id
let key = 0;
const routes = (RouteCp = Route) => [
    <RouteCp key={key++} path="/operations"  component={Operations} />,
    <RouteCp key={key++} path="/operation/:id" component={Operation} />,
    <RouteCp key={key++} path="/location/:id" component={Location} />,
    <RouteCp key={key++} path="/product/:id" component={Product} />,
    <RouteCp key={key++} path="/operation-set/:id" component={OperationSet} />
];

export default routes
