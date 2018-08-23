import merge from 'lodash/merge'

/**
 * Manage entities returned by the various actions
 * @param state
 * @param action
 * @returns {*}
 */
export function makeEntities (name) {
    const types = {
        remove: `${name}REMOVE`
    }

    const actions = {
        remove: (deleteEntities) => ({type: types.remove, deleteEntities}),
    }

    const reducer = (state = {}, action) => {
        if (action.hasOwnProperty('entities')) {
            return merge({}, state, action.entities)
        }

        if (action.hasOwnProperty('deleteEntities')) {
            let newState = merge({}, state)
            Object.keys(action.deleteEntities).map(n => {
                action.deleteEntities[n].map(id => {
                    delete newState[n][id]
                })
            })

            return newState
        }

        return state
    }

    return {
        actions,
        reducer
    };
}
