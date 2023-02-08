import {create} from 'zustand';
const useLetsEncryptData = create(( set, get ) => ({
    actionIndex:false,
    progress:0,
    maxIndex:1,
    attemptCount:0,
    maxAttempts:1,
    refreshTests:false,
    actions:[],
    setAttemptCount: (attemptCount) => {set(state => ({ attemptCount }))},
    setProgress: (progress) => {set(state => ({ progress }))},
    setActions: (actions) => {
        let maxIndex = actions.length;
        set(state => ({ actions, maxIndex }))
    },
    setRefreshTests: (refreshTests) => {set(state => ({ refreshTests }))},
    setActionIndex: (actionIndex) => {set(state => ({ actionIndex }))},
    setMaxAttempts: (maxAttempts) => {set(state => ({ maxAttempts }))},
}));
export default useLetsEncryptData;

