/* Creates A Store For Risk Data using Zustand */
import {create} from 'zustand';
import * as rsssl_api from "../../utils/api";
import {__} from "@wordpress/i18n";
import {produce} from "immer";
import React from "react";

const UserDataTableStore = create((set, get) => ({

    processing: false,
    dataLoaded: false,
    pagination: {},
    dataActions: {},
    UserDataTable: [],


    fetchUserData: async (action, dataActions) => {
        set({processing: true});
        set({dataLoaded: false});
        try {
            const response = await rsssl_api.doAction(
                action,
                dataActions
            );
            //now we set the EventLog
            if (response) {
                //if the response is empty we set the dummyData
                set({UserDataTable: response, dataLoaded: true, processing: false, pagination: response.pagination});
            }
        } catch (e) {
            console.log(e);
        }
    },

    handleUserTableSearch: async (search, searchColumns) => {
        //Add the search to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, search, searchColumns};
            })
        );
    },

    handleUserTablePageChange: async (page, pageSize) => {
        //Add the page and pageSize to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, page, pageSize};
            })
        );
    },

    handleUserTableRowsChange: async (currentRowsPerPage, currentPage) => {
        //Add the page and pageSize to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, currentRowsPerPage, currentPage};
            })
        );
    },

    //this handles all pagination and sorting
    handleUserTableSort: async (column, sortDirection) => {
        //Add the column and sortDirection to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, sortColumn: column, sortDirection};
            })
        );
    },

    handleUserTableFilter: async (column, filterValue) => {
        //Add the column and sortDirection to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, filterColumn: column, filterValue};
            })
        );
    },

    /*
* This function add a new row to the table
 */
    addRow: async (user, status) => {
        set({processing: true});
        try {
            const response = await rsssl_api.doAction('user_add_user', {user, status});
            // Consider checking the response structure for any specific success or failure signals
            if (response && response.request_success) {
                await get().fetchUserData('user_list');
                // Potentially notify the user of success, if needed.
            } else {
                // Handle any unsuccessful response if needed.
                console.log("Failed to add User: ", response.message);
            }
        } catch (e) {
            console.log(e);
            // Notify the user of an error.
        } finally {
            set({processing: false});
        }
    },

    /*
* This function updates the row only changing the status
 */
    updateRow: async (id, status) => {
        set({processing: true});
        try {
            const response = await rsssl_api.doAction(
                'user_update_row',
                {id, status}
            );
            //now we set the EventLog
            if (response) {
                await get().fetchUserData('user_list');
            }
        } catch (e) {
            console.log(e);
        }
    },


    updateMultiRow: async (ids, status) => {
        set({processing: true});
        try {
            const response = await rsssl_api.doAction(
                'user_update_multi_row',
                {ids, status}
            );
            //now we set the EventLog
            if (response) {
                await get().fetchUserData('user_list');
            }
        } catch (e) {
            console.log(e);
        }
    },
    resetRow: async (id) => {
        set({processing: true});
        try {
            const response = await rsssl_api.doAction(
                'delete_entry',
                {id}
            );
            //now we set the EventLog
            if (response) {
                await get().fetchUserData('user_list');
            }
        } catch (e) {
            console.log(e);
        }
    },

    resetMultiRow: async (ids) => {
        set({processing: true});
        try {
            const response = await rsssl_api.doAction(
                'delete_multi_entries',
                {ids}
            );
            //now we set the EventLog
            if (response) {
                await get().fetchUserData('user_list');
            }
        } catch (e) {
            console.log(e);
        }
    }
}));

export default UserDataTableStore;