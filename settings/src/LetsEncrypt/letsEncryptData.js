import {create} from 'zustand';
import produce from 'immer';

const useLetsEncryptData = create(( set, get ) => ({
    actionIndex:-1,
    progress:0,
    maxIndex:1,
    attemptCount:0,
    maxAttempts:1,
    refreshTests:false,
    actionsList:[],
    setAttemptCount: (attemptCount) => {set(state => ({ attemptCount }))},
    setProgress: (progress) => {set(state => ({ progress }))},
    setActionsList: (actionsList) => {set(state => ({ actionsList }))},
    setActionsListItem: (index, action) => {
        set(
            produce((state) => {
                state.actionsList[index] = action;
            })
        )
    },
    setActionsListProperty: (index, property, value) => {
        set(
            produce((state) => {
                state.actionsList[index][property] = value;
            })
        )
    },
    setRefreshTests: (refreshTests) => {set(state => ({ refreshTests }))},
    setActionIndex: (actionIndex) => {set(state => ({ actionIndex }))},
    setMaxAttempts: (maxAttempts) => {set(state => ({ maxAttempts }))},
}));
export default useLetsEncryptData;

