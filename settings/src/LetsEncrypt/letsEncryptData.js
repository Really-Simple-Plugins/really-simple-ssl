import {create} from 'zustand';
import produce from 'immer';
import * as rsssl_api from "../utils/api";
import {__} from "@wordpress/i18n";
import sleeper from "../utils/sleeper";
import {dispatch} from '@wordpress/data';
const useLetsEncryptData = create(( set, get ) => ({
    actionIndex:-1,
    progress:0,
    attemptCount:0,
    refreshTests:false,
    actionsList:[],
    updateVerificationType: async (verificationType) => {
        await rsssl_api.runLetsEncryptTest('update_verification_type', verificationType).then((response) => {
            let msg = verificationType==='dir' ? __('Switched to Directory', 'really-simple-ssl') : __('Switched to DNS', 'really-simple-ssl');
            const notice = dispatch('core/notices').createNotice(
                'success',
                msg,
                {
                    __unstableHTML: true,
                    id: 'rsssl_switched_to_dns',
                    type: 'snackbar',
                    isDismissible: true,
                }
            ).then(sleeper(3000)).then((response) => {
                dispatch('core/notices').removeNotice('rsssl_switched_to_dns');
            });
        });
    },
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
}));
export default useLetsEncryptData;

