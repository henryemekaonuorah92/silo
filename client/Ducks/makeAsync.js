import { bindActionCreators } from 'redux'

export function makeAsync (name, initialData = null) {
    const types = {
        request: `${name}REQUEST`,
        success: `${name}SUCCESS`,
        failure: `${name}FAILURE`
    }

    const actions = {
        request: () => ({type: types.request}),
        success: (data, entities, deleteEntities) => ({type: types.success, data, entities, deleteEntities}),
        failure: (message) => ({type: types.failure, message})
    }

    const helper = (dispatch) => (fn) => {
        let creators = bindActionCreators(actions, dispatch)
        fn(creators.success, creators.failure)
        creators.request()
    }

    const reducer = (state, action) => {
        if (state === undefined) {
            return {
                wip: false,
                lastFetch: null,
                lastError: null,
                data: initialData
            }
        }
        switch (action.type) {
            case types.request:
                return Object.assign({}, state, {wip: true})

            case types.success:
                return Object.assign({}, state, {
                    wip: false,
                    data: action.data,
                    lastFetch: new Date() | 0,
                    lastError: null
                })

            case types.failure:
                return Object.assign({}, state, {
                    wip: false,
                    lastError: action.message
                })

            default:
                return state
        }
    }

    return {
        actions,
        helper,
        reducer
    };
}
