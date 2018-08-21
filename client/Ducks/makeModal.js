
export function makeModal (name) {
    const types = {
        toggle: `${name}TOGGLE_MODAL`,
        close: `${name}CLOSE_MODAL`
    }

    const actions = {
        toggle: () => ({type: types.toggle}),
        close: () => ({type: types.close}),
    }

    const reducer = (state = {show: false}, action) => {
        switch (action.type) {
            case types.toggle:
                return {show: !state.show}

            case types.close:
                return {show: false}

            default:
                return state
        }
    }

    return {
        actions,
        types,
        reducer
    };
}
