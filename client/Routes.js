import React from 'react'
import {Route} from 'react-router-dom'

import Operations from './View/Operations'
import Operation from './View/Operation'
import Location from './View/Location'
import Product from './View/Product'
import OperationSet from './View/OperationSet'

// match.params.id
const routes = [
    <Route path="/operations" component={Operations} />,
    <Route path="/operation/:id" component={Operation} />,
    <Route path="/location/:id" component={Location} />,
    <Route path="/product/:id" component={Product} />,
    <Route path="/operation-set/:id" component={OperationSet} />
];

export default routes
