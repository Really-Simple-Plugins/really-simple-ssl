import {create} from 'zustand';
import * as rsssl_api from "../../utils/api";
import {produce} from "immer";

const DataTableStore = create((set, get) => ({

    processing: false,
    dataLoaded: false,
    dataActions: {},
    sourceData: [],
    filteredData: [],
    searchTerm:'',
    searchColumns:[],
    reloadFields:false,
    setReloadFields: (reloadFields) => set({reloadFields}),
    clearAllData: () => set({sourceData: [], filteredData: []}),
    setProcessing: (processing) => set({processing}),
    fetchData: async (action, dataActions) => {
        set({processing: true});
        try {
            const response = await rsssl_api.doAction(
                action,
                dataActions
            );
            if (response && response.data ) {
                set({filteredData:response.data, sourceData: response.data, dataLoaded: true, processing: false});
            }
        } catch (e) {
            console.log(e);
        } finally {
            set({processing: false});
        }
    },
    handleSearch: (searchTerm, searchColumns) => {
        set({searchTerm})
        set({searchColumns})
        let data = get().sourceData;
        const filteredData = data.filter(item =>
            searchColumns.some(column =>
                item[column] && item[column].toLowerCase().includes(searchTerm.toLowerCase())
            ));
        set({filteredData: filteredData});
    },
    /*
    * This function handles the filter, it is called from the GroupSetting class
     */
    handleFilter: async (column, filterValue) => {
        //Add the column and sortDirection to the dataActions
        set(produce((state) => {
                state.dataActions = {...state.dataActions, filterColumn: column, filterValue};
            })
        );
    },
    restoreView: () => {
        //filter the data again
        let searchTerm = get().searchTerm;
        if ( searchTerm !== '' ) {
            let searchColumns = get().searchColumns;
            get().handleSearch(searchTerm, searchColumns);
        }
    },
    //only removes rows from the dataset clientside, does not do an API call
    removeRows:(ids) => {
        let filteredData = get().filteredData;
        let sourceData = get().sourceData;
        let newFilteredData = filteredData.filter(item => !ids.includes(item.id));
        let newSourceData = sourceData.filter(item => !ids.includes(item.id));
        set({filteredData: newFilteredData, sourceData: newSourceData});
        get().restoreView();
    },
    rowAction: async ( ids, action, actionType, reloadFields ) => {
        actionType = typeof actionType !== 'undefined' ? actionType : '';
        set({processing: true});
        if ( actionType === 'delete' ) {
            get().removeRows(ids);
        }
        let data = {
            ids: ids,
        };
        try {
            const response = await rsssl_api.doAction(
                action,
                data
            );

            if ( response.data ) {
                set({filteredData:response.data, sourceData: response.data, dataLoaded: true, processing: false});
                get().restoreView();
                if (reloadFields)  {
                    get().setReloadFields(reloadFields);
                }
            }

        } catch (e) {
        } finally {
            set({processing: false});
        }
    },
}));

export default DataTableStore;