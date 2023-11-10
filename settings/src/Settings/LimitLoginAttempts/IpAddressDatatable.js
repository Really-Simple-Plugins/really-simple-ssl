import {__} from '@wordpress/i18n';
import React, {useEffect, useRef, useState, StrictMode, useCallback} from 'react';
import DataTable, {createTheme} from "react-data-table-component";
import IpAddressDataTableStore from "./IpAddressDataTableStore";
import EventLogDataTableStore from "../EventLog/EventLogDataTableStore";
import FilterData from "../FilterData";
import {button} from "@wordpress/components";
import {produce} from "immer";
import Flag from "../../utils/Flag/Flag";
import Icon from "../../utils/Icon";
import AddIpAddressModal from "./AddIpAddressModal";
import useFields from "../FieldsData";

const IpAddressDatatable = (props) => {
    const {
        IpDataTable,
        dataLoaded,
        dataActions,
        handleIpTableRowsChange,
        updateMultiRow,
        fetchIpData,
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
            fetchIpData(field.action, dataActions);
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


    const blockIpAddresses = useCallback(async (data) => {
        //we check if the data is an array
        if (Array.isArray(data)) {
            const ids = data.map((item) => item.id);
            await updateMultiRow(ids, 'blocked');
            setRowsSelected([]);
        } else {
            await updateRow(data, 'blocked');
        }
        await fetchDynamicData('event_log')
    }, [updateMultiRow, updateRow, fetchDynamicData]);

    const allowIpAddresses = useCallback(async (data) => {
        //we check if the data is an array
        if (Array.isArray(data)) {
            const ids = data.map((item) => item.id);
            await updateMultiRow(ids, 'allowed');
            setRowsSelected([]);
        } else {
            await updateRow(data, 'allowed');
        }
        await fetchDynamicData('event_log')
    }, [updateMultiRow, updateRow, fetchDynamicData]);

    const resetIpAddresses = useCallback(async (data) => {
        //we check if the data is an array
        if (Array.isArray(data)) {
            const ids = data.map((item) => item.id);
            await resetMultiRow(ids, dataActions);
            //we emtry the rowsSelected
            setRowsSelected([]);
        } else {
            await resetRow(data, dataActions);
        }
        fetchDynamicData('event_log')
    }, [resetMultiRow, resetRow, fetchDynamicData]);


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

    let paginationSet = true;
    if (typeof pagination === 'undefined') {
        paginationSet = false;
    }

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

                <div className="rsssl-add-button">
                    {(getCurrentFilter(moduleName) === 'blocked' || getCurrentFilter(moduleName) === 'allowed') && (
                        <div className="rsssl-add-button__inner">
                            <button
                                className="button button-secondary button-datatable rsssl-add-button__button"
                                onClick={handleOpen}
                                disabled={processing}
                            >
                                {getCurrentFilter(moduleName) === 'blocked' && (
                                    <>{__("Block IP Address", "really-simple-ssl")}</>
                                )}
                                {getCurrentFilter(moduleName) === 'allowed' && (
                                    <>{__("Trust IP Address", "really-simple-ssl")}</>
                                )}
                            </button>
                        </div>
                    )}
                </div>

                {/*Display the search bar*/}
                <div className="rsssl-search-bar">
                    <div className="rsssl-search-bar__inner">
                        <div className="rsssl-search-bar__icon"></div>
                        <input
                            type="text"
                            className="rsssl-search-bar__input"
                            placeholder={__("Search", "really-simple-ssl")}
                            disabled={processing}
                            onKeyUp={(event) => {
                                if (event.key === 'Enter') {
                                    handleIpTableSearch(event.target.value, searchableColumns);
                                }
                            }}
                        />
                    </div>
                </div>
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