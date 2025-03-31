/* Creates A Store For Risk Data using Zustand */
import {create} from 'zustand';
import * as rsssl_api from "../../utils/api";
import {produce} from "immer";
import apiFetch from "@wordpress/api-fetch";

const DynamicDataTableStore = create((set, get) => ({
    processing: false,
    dataLoaded: false,
    pagination: {},
    dataActions: {currentPage:1, currentRowsPerPage:5, filterValue: 'all',filterColumn: 'user_role'},
    totalRecords:0,
    DynamicDataTable: [],

    setDataLoaded: (dataLoaded) => set((state) => ({ ...state, dataLoaded: dataLoaded })),
    hardResetUser: async (id) => {
        if (get().processing) return;
        set({processing: true});
        try {
            const response = await rsssl_api.doAction(
                'two_fa_reset_user',
                {id}
            );
            if (response) {
                set(state => ({
                    ...state,
                    processing: false,
                }));
                // Return the response for the calling function to use
                return response;
            }
        } catch (e) {
            console.log(e);
        }
    },
    fetchDynamicData: async (role_filter = 'all') => {
        if (get().processing) return;
        set({processing: true});

        let allData = [];        // Will hold all fetched rows
        let offset = 0;          // Start from 0
        const limit = 1000;       // How many to fetch per request
        let totalRecords = 0;
        let negativeCount = 0;

        try {
            while (true) {
                // Clone current dataActions and add pagination params
                const params = {
                    ...get().dataActions,
                    number: limit, // Tells the server how many rows you want
                    offset: offset,
                    negative_count: negativeCount,
                    ...(role_filter !== 'all' && { role_filter: role_filter })
                };

                // Make the request
                const response = await rsssl_api.doAction('two_fa_table', params);

                // If something went wrong or no data returned, break out
                if (!response || !response.data) {
                    break;
                }

                // Accumulate data
                allData.push(...response.data);

                // Grab totalRecords from the response (the server must return it)
                if (response.totalRecords) {
                    totalRecords = response.totalRecords;
                    negativeCount = response.negative_count;
                }

                // If the number of records fetched in this request is less than the limit,
                // it means there are no more records to fetch.
                if (response.data.length < limit) {
                    break;
                }

                // Otherwise, increment offset to fetch the "next page"
                offset += limit;
            }

            // Once the loop is done, we have all the data in allData
            set(state => ({
                ...state,
                DynamicDataTable: allData,  // Store *all* fetched rows
                dataLoaded: true,
                processing: false,
                totalRecords: totalRecords, // Keep track if you need it
            }));

            // Optionally return it if this function is called from elsewhere
            return {
                data: allData,
                totalRecords,
            };

        } catch (e) {
            console.log(e);

            set(state => ({
                ...state,
                processing: false,
                dataLoaded: true,
            }));
        }
    },

    handleTableSearch: async (search, searchColumns) => {
        const typingTimer = setTimeout(async () => {
            set(produce((state) => {
                state.dataActions = {...state.dataActions, search, searchColumns};
            }));
            await get().fetchDynamicData();
        }, 500);

        return () => {
            clearTimeout(typingTimer);
        };
    },

    handlePageChange: async (page, pageSize) => {
        //Add the page and pageSize to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, currentPage: page};
            })
        );
        await get().fetchDynamicData();
    },

    handleRowsPerPageChange: async (currentRowsPerPage, currentPage) => {
        //Add the page and pageSize to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, currentRowsPerPage, currentPage};
            })
        );
        await get().fetchDynamicData();
    },

    //this handles all pagination and sorting
    handleTableSort: (sortColumn, sortDirection) => {
        set(
            produce((state) => {
                // Update sort parameters in dataActions
                state.dataActions.sortColumn = sortColumn.column;
                state.dataActions.sortDirection = sortDirection;

                // Sort the existing DynamicDataTable in place
                state.DynamicDataTable.sort((a, b) => {
                    const aVal = a[sortColumn.column];
                    const bVal = b[sortColumn.column];

                    // Compare the two values. Adjust comparison logic if necessary
                    if (aVal < bVal) {
                        return sortDirection === 'asc' ? -1 : 1;
                    }
                    if (aVal > bVal) {
                        return sortDirection === 'asc' ? 1 : -1;
                    }
                    return 0;
                });
            })
        );
    },

    handleUsersTableFilter: async (column, filterValue) => {
        //Add the column and sortDirection to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, filterColumn: column, filterValue};
            })
        );
        // Fetch the data again
        await get().fetchDynamicData();
    },

}));

export default DynamicDataTableStore;