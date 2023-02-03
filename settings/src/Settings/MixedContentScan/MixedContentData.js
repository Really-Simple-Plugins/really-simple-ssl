import {create} from 'zustand';
import * as rsssl_api from "../../utils/api";

const UseMixedContent = create(( set, get ) => ({
    mixedContentData: [],
    dataLoaded:false,
    fixedItemId:false,
    action:'',
    nonce:'',
    completedStatus:'never',
    paused:false,
    progress:0,
    fetchMixedContentData: async () => {
        set({ state: 'running', paused: false } );
        const {data, progress, state, action, nonce, completed_status } = await getScanIteration(false);
        set({
            state: state,
            mixedContentData: data,
            progress: progress,
            action: action,
            nonce: nonce,
            completedStatus: completed_status,
            dataLoaded: true
        });
    },
    run: async (start) => {
        let currentState = get().state;
        currentState = typeof start !=='undefined' ? currentState:
        console.log("state "+currentState);

        const {data, progress, state, action, nonce, completed_status } = await getScanIteration(currentState);
        set({
            state: state,
            mixedContentData: data,
            progress: progress,
            action: action,
            nonce: nonce,
            completedStatus: completed_status,
        });
    },
    stop: async () => {
        set({ state: 'stop', paused: true } );
        const {data, progress, state, action, nonce, completed_status } = await getScanIteration('stop');
        set({
            state: state,
            mixedContentData: data,
            progress: progress,
            action: action,
            nonce: nonce,
            completedStatus: completed_status,
        });
    },
    setFixedItemId: (fixedItemId) => set({ fixedItemId } ),
    removeDataItem: (removeItem) => {
        let data = get().mixedContentData;
        for (const item of data) {
            if (item.id===removeItem.id){
                item.fixed = true;
            }
        }
        set({
            mixedContentData: data,
        });
    },
    ignoreDataItem: (ignoreItem) => {
        let data = get().mixedContentData;
        for (const item of data) {
            if (item.id===ignoreItem.id){
                item.ignored = true;
            }
        }
        set({
            mixedContentData: data,
        });
    }
}));

export default UseMixedContent;

const getScanIteration = async (state) => {
    return await rsssl_api.runTest('mixed_content_scan', state).then((response) => {
        let data = response.data;
        console.log("iteration");
        console.log(response);
        if (typeof data === 'object') {
            data = Object.values(data);
        }
        if ( !Array.isArray(data) ) {
            data = [];
        }
        response.data = data;

        return response;
    })
}

