import {create} from 'zustand';
import * as rsssl_api from "../../utils/api";

const UseLearningMode = create(( set, get ) => ({
    learningModeData: [],
    dataLoaded: false,
    fetchLearningModeData: async (type) => {
        let data = {};
        data.type = type;
        data.lm_action = 'get';
        let learningModeData = await rsssl_api.doAction('learning_mode_data', data).then((response) => {
            return response;
        })

        if ( typeof learningModeData === 'object' && learningModeData.request_success === true ) {
            learningModeData = Object.values(learningModeData);
        }

        if ( !Array.isArray(learningModeData) ) {
            learningModeData = [];
        }
        set({
            learningModeData: learningModeData,
            dataLoaded:true,
        });
    },
    updateStatus: async (enabled, updateItem, type) => {
        let learningModeData = get().learningModeData;
        let data = {};
        data.type = type;
        data.updateItemId = updateItem.id;
        data.enabled = enabled==1 ? 0 : 1;
        data.lm_action = 'update';

        //for fast UX feel, update the state before we post
        for (const item of learningModeData){
            if (updateItem.id === item.id && item.status) {
                item.status = data.enabled;
            }
        }
        set({
            learningModeData: learningModeData,
        });
        learningModeData = await rsssl_api.doAction('learning_mode_data', data).then((response) => {
            return response;
        })
        if ( typeof learningModeData === 'object' ) {
            learningModeData = Object.values(learningModeData);
        }
        if ( !Array.isArray(learningModeData) ) {
            learningModeData = [];
        }
        set({
            learningModeData: learningModeData,
            dataLoaded:true,
        });
    },
    deleteData: async (deleteItem, type) => {
        let learningModeData = get().learningModeData;

        let data = {};
        data.type = type;
        data.updateItemId = deleteItem.id;
        data.lm_action = 'delete';
        //for fast UX feel, update the state before we post
        learningModeData.forEach(function(item, i) {
            if (item.id === deleteItem.id) {
                learningModeData.splice(i, 1);
            }
        });
        set({
            learningModeData: learningModeData,
        });
        learningModeData = await rsssl_api.doAction('learning_mode_data', data).then((response) => {
            return response;
        })
        if ( typeof learningModeData === 'object' ) {
            learningModeData = Object.values(learningModeData);
        }
        if ( !Array.isArray(learningModeData) ) {
            learningModeData = [];
        }
        set({
            learningModeData: learningModeData,
            dataLoaded:true,
        });
    },

}));

export default UseLearningMode;


