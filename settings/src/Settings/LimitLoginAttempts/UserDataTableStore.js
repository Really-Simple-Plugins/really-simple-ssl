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
    rowCleared: false,

    fetchData: async (action, dataActions) => {
        //we check if the processing is already true, if so we return
        set({processing: true});
        set({dataLoaded: false});
        set({rowCleared: true});
        if (Object.keys(dataActions).length === 0) {
            let dataActions = get().dataActions;
        }

        if ( !get().processing ) {
            return;
        }

        if (Object.keys(dataActions).length === 0) {
            return;
        }

        //we empty all existing data
        set({UserDataTable: []});

        try {
            const response = await rsssl_api.doAction(
                action,
                dataActions
            );
            //now we set the EventLog
            //now we set the EventLog
            if (response && response.request_success) {
                set({UserDataTable: response, dataLoaded: true, processing: false, pagination: response.pagination});
            }
            set({ rowCleared: true });
        } catch (e) {
            console.log(e);
        } finally {
            set({processing: false});
            set({rowCleared: false});

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
* This function updates the row only changing the status
 */
    updateRow: async (value, status, dataActions) => {
        set({processing: true});
        let data = {
            value: value,
            status: status
        };
        try {
            const response = await rsssl_api.doAction(
                'user_update_row',
               data
            );
            if (response && response.request_success) {
                await get().fetchData('rsssl_limit_login_user', dataActions);
                return { success: true, message: response.message, response };
            } else {
                return { success: false, message: response?.message || 'Failed to add user', response };
            }
        } catch (e) {
            return { success: false, message: 'Error occurred', error: e };
        } finally {
            set({processing: false});
        }
    },

    resetRow: async (id, dataActions) => {
        set({processing: true});
        try {
            const response = await rsssl_api.doAction(
                'delete_entries',
                {id}
            );
            //now we set the EventLog
            if (response && response.success) {
                await get().fetchData('rsssl_limit_login_user', dataActions);
                // Return the success message from the API response.
                return { success: true, message: response.message, response };
            } else {
                // Return a custom error message or the API response message.
                return { success: false, message: response?.message || 'Failed to reset user', response };
            }
        } catch (e) {
            console.error(e);
            // Return the caught error with a custom message.
            return { success: false, message: 'Error occurred', error: e };
        } finally {
            set({processing: false});
        }
    },

    resetMultiRow: async (ids, dataActions) => {
        set({processing: true});
        try {
            const response = await rsssl_api.doAction(
                'delete_entries',
                {ids}
            );
            console.error(response);
            //now we set the EventLog
            if (response && response.success) {
                if (response.success) {
                    await get().fetchUserData('rsssl_limit_login_user', dataActions);
                    return {success: true, message: response.message, response};
                } else
                    return {success: false, message: response?.message || 'Failed to reset user', response};
            }
        } catch (e) {
            console.error(e);
            return { success: false, message: 'Error occurred', error: e };
        } finally {
            set({processing: false});
        }
    }
}));

export default UserDataTableStore;