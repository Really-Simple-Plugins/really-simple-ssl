/* Creates A Store For Risk Data using Zustand */
import {create} from 'zustand';
import * as rsssl_api from "../../utils/api";
import {produce} from "immer";
import apiFetch from "@wordpress/api-fetch";

const DynamicDataTableStore         = create(
	(set, get) => ({
		processing: false,
		dataLoaded: false,
		pagination: {},
		dataActions: {currentPage:1, currentRowsPerPage:5},
		totalRecords:0,
		DynamicDataTable: [],
		setDataLoaded: (dataLoaded) => set(
		(state) => ({ ...state,
    dataLoaded: dataLoaded })
	),
	resetUserMethod: async( id, roles ) => {
    let newMethod               = 'open';
    return apiFetch(
			{
				path: ` / wp / v2 / users / ${id}`,
				method: 'GET',
			}
		)
			.then(
				async( response ) => {
                const userRoles = response.roles;
                if (userRoles.some( role => roles.includes( role ) )) {
                    newMethod = 'open';
                }
                // if none of the roles match, you can set a default method or throw an error
					else {
						newMethod = 'disabled';
					}

					// Now, update the user's 2FA method
					return apiFetch(
						{
							path: ` / wp / v2 / users / ${id}`,
							method: 'POST',
							data: {
								meta: {
									rsssl_two_fa_status: newMethod,
								},
							},
						}
					);
				}
			)
			.then(
				() => {
                get().fetchDynamicData();
				}
			)
			.catch(
				(error) => {
                console.error( 'Error:', error );
				}
			);
    },
    fetchDynamicData: async() => {
			try {
				const response = await rsssl_api.doAction(
					'two_fa_table',
					get().dataActions
				);
				//now we set the EventLog
				if (response) {
						set(
							state => ({
								...state,
								DynamicDataTable: response.data,
								dataLoaded: true,
								processing: false,
								pagination: response.pagination,
								totalRecords: response.totalRecords,
							})
						);
						// Return the response for the calling function to use
						return response;
				}

			} catch (e) {
    console.log( e );
			}
		},
    handleTableSearch: async( search, searchColumns ) => {
			set(
        produce(
			(state) => {
				state.dataActions = {...state.dataActions, search, searchColumns};
                }
			)
    );
	await get().fetchDynamicData();
    },
    handlePageChange: async( page, pageSize ) => {
			//Add the page and pageSize to the dataActions
			set(
				produce(
					(state) => {
                    state.dataActions = {...state.dataActions, currentPage: page};
                    }
				)
			);
    await get().fetchDynamicData();
    },
    handleRowsPerPageChange: async( currentRowsPerPage, currentPage ) => {
			//Add the page and pageSize to the dataActions
			set(
				produce(
					(state) => {
                    state.dataActions = {...state.dataActions, currentRowsPerPage, currentPage};
                    }
				)
			);
    await get().fetchDynamicData();
    },
    //this handles all pagination and sorting
		handleTableSort: async( column, sortDirection ) => {
    //Add the column and sortDirection to the dataActions
			set(
				produce(
					(state) => {
                    state.dataActions = {...state.dataActions, sortColumn: column, sortDirection};
					}
				)
			);
    await get().fetchDynamicData();
    },
    handleUsersTableFilter: async( column, filterValue ) => {
			//Add the column and sortDirection to the dataActions
			set(
				produce(
					(state) => {
                    state.dataActions = {...state.dataActions, filterColumn: column, filterValue};
                    }
				)
			);
    // We fetch the data again
		await get().fetchDynamicData();
    },
	})
);

export default DynamicDataTableStore;