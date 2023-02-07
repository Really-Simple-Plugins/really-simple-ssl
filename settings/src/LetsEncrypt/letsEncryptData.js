import {create} from 'zustand';
import {produce} from "immer";
const useLetsEncryptData = create(( set, get ) => ({
    actionIndex:false,
    progress:0,
    maxIndex:1,
    attemptCount:0,
    maxAttempts:1,
    refreshTests:false,
    actions:[],
    action:false,
    setAttemptCount: (attemptCount) => {set(state => ({ attemptCount }))},
    setProgress: (progress) => {set(state => ({ progress }))},
    setActions: (actions) => {
        console.log("set actions");
        console.log(actions);
        let action = get().action;
        if (!action){
            set(state => ({ action:actions[0] }))
        }
        let maxIndex = actions.length-1
        set(state => ({ actions, maxIndex }))
    },
    setAction: (action) => {set(state => ({ action }))},
    setRefreshTests: (refreshTests) => {set(state => ({ refreshTests }))},
    setActionIndex: (actionIndex) => {set(state => ({ actionIndex }))},
    setMaxAttempts: (maxAttempts) => {set(state => ({ maxAttempts }))},
    updateActionProperty: (index, property, value) =>{
        set(
            produce((state) => {
                state.actions[index][property] = value;
            })
        )
    }
}));
export default useLetsEncryptData;

