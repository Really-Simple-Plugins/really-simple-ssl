import {__} from '@wordpress/i18n';
import React, {useEffect, useRef, useState, StrictMode, useCallback} from 'react';
import DataTable, {createTheme} from "react-data-table-component";
import IpAddressDataTableStore from "./IpAddressDataTableStore";
import EventLogDataTableStore from "../EventLog/EventLogDataTableStore";
import FilterData from "../FilterData";
import Flag from "../../utils/Flag/Flag";
import AddIpAddressModal from "./AddIpAddressModal";
import useFields from "../FieldsData";
import FieldsData from "../FieldsData";
import SearchBar from "../DynamicDataTable/SearchBar";
import AddButton from "../DynamicDataTable/AddButton";

const IpAddressDatatable = (props) => {
    const {
        IpDataTable,
        dataLoaded,
        dataActions,
        handleIpTableRowsChange,
        updateMultiRow,
        fetchData,
        handleIpTableSort,
        handleIpTablePageChange,
        handleIpTableSearch,
        handleIpTableFilter,
        ipAddress,
        updateRow,
        pagination,
        resetRow,
        resetMultiRow,
        setStatusSelected,
        rowCleared,
        processing
    } = IpAddressDataTableStore()

    const {
        DynamicDataTable,
        fetchDynamicData,
    } = EventLogDataTableStore();

    //here we set the selectedFilter from the Settings group
    const {
        selectedFilter,
        setSelectedFilter,
        activeGroupId,
        getCurrentFilter,
        setProcessingFilter,
    } = FilterData();

    const [addingIpAddress, setAddingIpAddress] = useState(false);
    const [rowsSelected, setRowsSelected] = useState([]);
    const {fields, fieldAlreadyEnabled, getFieldValue, saveFields} = useFields();
    const {showSavedSettingsNotice} = FieldsData();
    const [tableHeight, setTableHeight] = useState(600);  // Starting height
    const rowHeight = 50; // Height of each row.

    const moduleName = 'rsssl-group-filter-limit_login_attempts_ip_address';

    const buildColumn = useCallback((column) => ({
        name: column.name,
        sortable: column.sortable,
        searchable: column.searchable,
        width: column.width,
        visible: column.visible,
        column: column.column,
        selector: row => row[column.column],
    }), []);
    //getting the fields from the props
    let field = props.field;
    //we loop through the fields
    const columns = field.columns.map(buildColumn);


    useEffect(() => {
        const currentFilter = getCurrentFilter(moduleName);
        if (!currentFilter) {
            setSelectedFilter('locked', moduleName);
        }
        setProcessingFilter(processing);
        handleIpTableFilter('status', currentFilter);
    }, [moduleName, handleIpTableFilter, getCurrentFilter(moduleName), setSelectedFilter, IpDataTable, processing]);

    useEffect(() => {
        setRowsSelected([]);
    }, [IpDataTable]);

    //if the dataActions are changed, we fetch the data
    useEffect(() => {
        //we make sure the dataActions are changed in the store before we fetch the data
        if (dataActions) {
            fetchData(field.action, dataActions);
        }
    }, [dataActions.sortDirection, dataActions.filterValue, dataActions.search, dataActions.page, dataActions.currentRowsPerPage, fieldAlreadyEnabled('enable_limited_login_attempts')]);


    const customStyles = {
        headCells: {
            style: {
                paddingLeft: '0', // override the cell padding for head cells
                paddingRight: '0',
            },
        },
        cells: {
            style: {
                paddingLeft: '0', // override the cell padding for data cells
                paddingRight: '0',
            },
        },
    };
    createTheme('really-simple-plugins', {
        divider: {
            default: 'transparent',
        },
    }, 'light');


    let enabled = getFieldValue('enable_limited_login_attempts');

    const handleOpen = () => {
        setAddingIpAddress(true);
    };

    const handleClose = () => {
        setAddingIpAddress(false);
    };

    let searchableColumns = [];
    //setting the searchable columns
    columns.map(column => {
        if (column.searchable) {
            searchableColumns.push(column.column);
        }
    });

    //now we get the options for the select control
    let options = props.field.options;
    //we divide the key into label and the value into value
    options = Object.entries(options).map((item) => {
        return {label: item[1], value: item[0]};
    });


    function handleStatusChange(value, id) {
        //if the id is not 'new' we update the row
        if (id !== 'new') {
            updateRow(id, value);
        } else {
            //if the id is 'new' we set the statusSelected
            setStatusSelected(value);
        }
    }

    //we convert the data to an array
    let data = Object.values({...IpDataTable.data});

    const resetIpAddresses = useCallback(async (data) => {
        if (Array.isArray(data)) {
            const ids = data.map((item) => item.id);
            await resetMultiRow(ids, dataActions).then((response) => {
                if (response && response.success) {
                    showSavedSettingsNotice(response.message);
                } else {
                    showSavedSettingsNotice(response.message);
                }
            });
            setRowsSelected([]);
        } else {
            await resetRow(data, dataActions).then((response) => {
                if (response && response.success) {
                    showSavedSettingsNotice(response.message);
                } else {
                    showSavedSettingsNotice(response.message);
                }
            });
        }
        await fetchDynamicData('event_log')
    }, [resetMultiRow, resetRow, fetchDynamicData, dataActions]);


    function generateOptions(status, id) {
        //if the there is no id we set it to new
        if (!id) {
            id = 'new';
        }
        return (
            <select
                className="rsssl-select"
                value={status}
                onChange={(event) => handleStatusChange(event.target.value, id)}
            >
                {options.map((item, i) => {
                    //if item value = locked the option will show but is nog selectable
                    let disabled = false;
                    if (item.value === 'locked') {
                        disabled = true;
                    }

                    return (
                        <option key={'ip-options-'+i} value={item.value} disabled={disabled}>
                            {item.label}
                        </option>
                    );
                })}
            </select>
        );
    }

    function generateFlag(flag, title) {
        return (
            <>
                <Flag
                    countryCode={flag}
                    style={{
                        fontSize: '2em',
                        marginLeft: '0.3em',
                    }}
                    title={title}
                ></Flag>
            </>

        )
    }

    const ActionButton = ({onClick, children, className}) => (
        <div className={`rsssl-action-buttons__inner`}>
            <button
                className={`button ${className} rsssl-action-buttons__button`}
                onClick={onClick}
                disabled={processing}
            >
                {children}
            </button>
        </div>
    );

    function generateActionbuttons(id) {
        return (
            <>
                <div className="rsssl-action-buttons">
                    <ActionButton
                        className="button-red"
                        onClick={() => {
                            resetIpAddresses(id);
                        }}>
                        {__("Delete", "really-simple-ssl")}
                    </ActionButton>
                </div>
            </>
        );
    }

    for (const key in data) {
        let dataItem = {...data[key]}

        dataItem.action = generateActionbuttons(dataItem.id);
        dataItem.status = __(dataItem.status = dataItem.status.charAt(0).toUpperCase() + dataItem.status.slice(1), 'really-simple-ssl');

        data[key] = dataItem;
    }

    function handleSelection(state) {
        setRowsSelected(state.selectedRows);
    }

    let paginationSet;
    paginationSet = typeof pagination !== 'undefined';

    useEffect(() => {
        if (Object.keys(data).length === 0 ) {
            setTableHeight(100); // Adjust depending on your UI measurements
        } else {
            setTableHeight(rowHeight * (paginationSet ? pagination.perPage + 2 : 12)); // Adjust depending on your UI measurements
        }

    }, [paginationSet, pagination?.perPage, data]);
    let debounceTimer;

    return (
        <>
            <AddIpAddressModal
                isOpen={addingIpAddress}
                onRequestClose={handleClose}
                options={options}
                value={ipAddress}
                status={getCurrentFilter(moduleName)}
                dataActions={dataActions}
            >
            </AddIpAddressModal>
            <div className="rsssl-container">
                {/*display the add button on left side*/}
                <AddButton
                    getCurrentFilter={getCurrentFilter}
                    moduleName={moduleName}
                    handleOpen={handleOpen}
                    processing={processing}
                    blockedText={__("Block IP Address", "really-simple-ssl")}
                    allowedText={__("Trust IP Address", "really-simple-ssl")}
                />

                {/*Display the search bar*/}
                <SearchBar
                    handleSearch={handleIpTableSearch}
                    searchableColumns={searchableColumns}
                />
            </div>

            { /*Display the action form what to do with the selected*/}
            {rowsSelected.length > 0 && (
                <div
                    style={{
                        marginTop: '1em',
                        marginBottom: '1em',
                    }}>
                    <div className={"rsssl-multiselect-datatable-form rsssl-primary"}
                    >
                        <div>
                            {__("You have selected %s rows", "really-simple-ssl").replace('%s', rowsSelected.length)}
                        </div>

                        <div className="rsssl-action-buttons">
                            <ActionButton
                                className="button-red"
                                onClick={() => {
                                    resetIpAddresses(rowsSelected);
                                }}
                            >
                                {__("Delete", "really-simple-ssl")}
                            </ActionButton>
                        </div>
                    </div>
                </div>
            )}

            {/*Display the datatable*/}
            <div style={{ height: `${tableHeight}px`, position: 'relative' }}>
            <DataTable
                columns={columns}
                data={processing ? [] : data}
                dense
                paginationServer
                paginationTotalRows={paginationSet? pagination.totalRows: 10}
                paginationPerPage={paginationSet? pagination.perPage: 10}
                paginationDefaultPage={paginationSet?pagination.currentPage: 1}
                paginationComponentOptions={{
                    rowsPerPageText: __('Rows per page:', 'really-simple-ssl'),
                    rangeSeparatorText: __('of', 'really-simple-ssl'),
                    noRowsPerPage: false,
                    selectAllRowsItem: false,
                    selectAllRowsItemText: __('All', 'really-simple-ssl'),

                }}
                loading={dataLoaded}
                pagination={!processing}
                onChangeRowsPerPage={handleIpTableRowsChange}
                onChangePage={handleIpTablePageChange}
                sortServer={!processing}
                onSort={handleIpTableSort}
                paginationRowsPerPageOptions={[10, 25, 50, 100]}
                noDataComponent={__("No results", "really-simple-ssl")}
                persistTableHead
                selectableRows={!processing}
                onSelectedRowsChange={handleSelection}
                clearSelectedRows={rowCleared}
                theme="really-simple-plugins"
                customStyles={customStyles}
            ></DataTable>
            </div>
            {!enabled && (
                <div className="rsssl-locked">
                    <div className="rsssl-locked-overlay"><span
                        className="rsssl-task-status rsssl-open">{__('Disabled', 'really-simple-ssl')}</span><span>{__('Activate Limit login attempts to enable this block.', 'really-simple-ssl')}</span>
                    </div>
                </div>
            )}
        </>
    );

}

export default IpAddressDatatable;