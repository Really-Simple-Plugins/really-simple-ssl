/* Creates A Store For Risk Data using Zustand */
import {create} from 'zustand';
import * as rsssl_api from "../../utils/api";
import {produce} from "immer";
import React, {useState} from "react";

const DynamicDataTableStore = create((set, get) => ({

    twoFAMethods: {},
    setTwoFAMethods: (methods) => set((state) => ({ ...state, twoFAMethods: methods })),
    processing: false,
    dataLoaded: false,
    pagination: {},
    dataActions: {},
    DynamicDataTable: [],

    fetchDynamicData: async (action) => {
        try {
            const response = await rsssl_api.doAction(action, get().dataActions);
            return response;
        } catch (e) {
            console.log(e);
        }
    },

    setDynamicData: (data) => {
        set(state => ({
            ...state,
            DynamicDataTable: data,
            dataLoaded: true,
            processing: false,
            //...
        }));
    },

    handleTableSearch: async (search, searchColumns) => {
        //Add the search to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, search, searchColumns};
            })
        );
    },

    handleTablePageChange: async (page, pageSize) => {
        //Add the page and pageSize to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, page, pageSize};
            })
        );
    },

    handleTableRowsChange: async (currentRowsPerPage, currentPage) => {
        //Add the page and pageSize to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, currentRowsPerPage, currentPage};
            })
        );
    },

    //this handles all pagination and sorting
    handleTableSort: async (column, sortDirection) => {
        //Add the column and sortDirection to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, sortColumn: column, sortDirection};
            })
        );
    },

    updateUserMeta: async (userId, updatedMeta) => {
        set(produce((state) => {
            const userIndex = state.DynamicDataTable.findIndex(user => user.id === userId);
            if (userIndex !== -1) {
                state.DynamicDataTable[userIndex].rsssl_two_fa_method = updatedMeta;
            }
        }));

        let data = {};
        data.userId = userId;
        data.method = updatedMeta;
        const response = await rsssl_api.doAction('store_two_fa_usermeta', data);
    },

}));

export default DynamicDataTableStore;