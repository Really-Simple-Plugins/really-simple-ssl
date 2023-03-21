import {create} from 'zustand';
import * as rsssl_api from "../../utils/api";

const UseMixedContent = create(( set, get ) => ({
    mixedContentData: [],
    dataLoaded:false,
    fixedItemId:false,
    action:'',
    nonce:'',
    completedStatus:'never',
    progress:0,
    scanStatus:false,
    fetchMixedContentData: async () => {
        set({ scanStatus: 'running' } );
        const {data, progress, state, action, nonce, completed_status } = await getScanIteration(false);
        set({
            scanStatus: state,
            mixedContentData: data,
            progress: progress,
            action: action,
            nonce: nonce,
            completedStatus: completed_status,
            dataLoaded: true,
        });
    },
    start: async () => {
        const {data, progress, state, action, nonce, completed_status } = await getScanIteration('start');
        set({
            scanStatus: state,
            mixedContentData: data,
            progress: progress,
            action: action,
            nonce: nonce,
            completedStatus: completed_status,
            dataLoaded:true,
        });
    },
    runScanIteration: async () => {
        let currentState = get().scanStatus;
        if ( currentState==='stop' ) {
            return;
        }

        const {data, progress, state, action, nonce, completed_status } = await getScanIteration(currentState);
        if ( get().scanStatus !== 'stop' ) {
            set({
                scanStatus: state,
                mixedContentData: data,
                progress: progress,
                action: action,
                nonce: nonce,
                completedStatus: completed_status,
                dataLoaded:true,
            });
        }


    },
    stop: async () => {
        set({ scanStatus: 'stop' } );
        const {data, progress, state, action, nonce, completed_status } = await getScanIteration('stop');
        set({
            scanStatus: 'stop',
            mixedContentData: data,
            progress: progress,
            action: action,
            nonce: nonce,
            completedStatus: completed_status,
        });
    },
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
        if (typeof data === 'object') {
            data = Object.values(data);
        }
        if ( !Array.isArray(data) ) {
            data = [];
        }
        response.data = data;
        if ( state==='stop' ) {
            response.state = 'stop';
        }

        return response;
    })
}

