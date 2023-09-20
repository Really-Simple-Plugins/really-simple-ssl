import React, { useEffect, useState, useCallback } from 'react';
import DataTable, { createTheme } from "react-data-table-component";
import CountryDataTableStore from "./CountryDataTableStore";
import EventLogDataTableStore from "../EventLog/EventLogDataTableStore";
import { ToastContainer, toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import FilterData from "../FilterData";
import Flag from "../../utils/Flag/Flag";
import { button } from "@wordpress/components";
import { __ } from '@wordpress/i18n';
import useFields from "../FieldsData";
import Icon from "../../utils/Icon";

const CountryDatatable = (props) => {
    const {
        CountryDataTable,
        dataLoaded,
        fetchCountryData,
        processing,
        handleCountryTableFilter,
        addRow,
        removeRow,
        pagination,
        handleCountryTablePageChange,
        handleCountryTableRowsChange,
        handleCountryTableSort,
        handleCountryTableSearch,
        addRegion,
        addRegions,
        removeRegion,
        removeRegions,
        addRowMultiple,
        removeRowMultiple,
        resetRow,
        resetMultiRow,
        dataActions,
        rowCleared,
    } = CountryDataTableStore();

    const {
        DynamicDataTable,
        fetchDynamicData,
    } = EventLogDataTableStore();

    const {
        selectedFilter,
        setSelectedFilter,
        activeGroupId,
        getCurrentFilter
    } = FilterData();

    const [rowsSelected, setRowsSelected] = useState([]);
    const moduleName = 'rsssl-group-filter-limit_login_attempts_country';
    const {fields, fieldAlreadyEnabled, getFieldValue} = useFields();

    const buildColumn = useCallback((column) => ({
        //if the filter is set to region and the columns = status we do not want to show the column
        omit: getCurrentFilter(moduleName) === 'regions' && column.column === 'status',
        name: column.name,
        sortable: column.sortable,
        searchable: column.searchable,
        width: column.width,
        visible: column.visible,
        column: column.column,
        selector: row => row[column.column],
    }), []);
    let field = props.field;
    const columns = field.columns.map(buildColumn);

    const searchableColumns = columns
        .filter(column => column.searchable)
        .map(column => column.column);

    useEffect(() => {
        const currentFilter = getCurrentFilter(moduleName);
        if (!currentFilter) {
            setSelectedFilter('blocked', moduleName);
        }
        handleCountryTableFilter('status', currentFilter);

    }, [selectedFilter, moduleName, handleCountryTableFilter, getCurrentFilter, setSelectedFilter, CountryDatatable]);

    useEffect(() => {
        setRowsSelected([]);
    }, [CountryDataTable]);

    useEffect(() => {
        if (fieldAlreadyEnabled) {
            if (!dataLoaded) {
                fetchDynamicData(field.action);
            }
        }
    }, [fields]);

    //if the dataActions are changed, we fetch the data
    useEffect(() => {
        //we make sure the dataActions are changed in the store before we fetch the data
        if (dataActions) {
            fetchCountryData(field.action, dataActions)
        }
    }, [dataActions.sortDirection, dataActions.filterValue, dataActions.search, dataActions.page]);

    let enabled = false;

    fields.forEach(function (item, i) {
        if (item.id === 'enable_limited_login_attempts') {
            enabled = item.value;
        }
    });

    const customStyles = {
        headCells: {
            style: {
                paddingLeft: '0',
                paddingRight: '0',
            },
        },
        cells: {
            style: {
                paddingLeft: '0',
                paddingRight: '0',
            },
        },
    };

    createTheme('really-simple-plugins', {
        divider: {
            default: 'transparent',
        },
    }, 'light');

    const handleSelection = useCallback((state) => {
        setRowsSelected(state.selectedRows);
    }, []);

    const allowRegionByCode = async (code, regionName = '') => {
        if (Array.isArray(code)) {
            const ids = code.map(item => item.id);
            const regions = code.map(item => item.region);
            await removeRegions(ids);
            let regionsString = regions.join(', ');
            notifySuccess(regionsString + ' ' + __('has been removed', 'really-simple-ssl'));
            setRowsSelected([]);
        } else {
            await removeRegion(code, 'blocked');
            notifySuccess(regionName + ' ' + __('has been allowed', 'really-simple-ssl'));
        }
        await fetchDynamicData('event_log');
    };


    const allowMultiple = useCallback((rows) => {
        const ids = rows.map(item => item.id);
        resetMultiRow(ids, 'blocked');
    }, [resetMultiRow]);

    const allowById = useCallback((id) => {
        resetRow(id, 'blocked');
    }, [resetRow]);

    const blockRegionByCode = useCallback(async (code, region = '') => {
        if (Array.isArray(code)) {
            const ids = code.map(item => item.id);
            const regions = code.map(item => item.region);
            await addRegions(ids, 'blocked');
            let regionsString = regions.join(', ');
            notifySuccess(regionsString + ' ' + __('has been blocked', 'really-simple-ssl'));
            setRowsSelected([]);
        } else {
            await addRegion(code, 'blocked');
            notifySuccess(region + ' ' + __('has been blocked', 'really-simple-ssl'));
        }

        await fetchDynamicData('event_log');

    }, [addRegion]);

    const allowCountryByCode = useCallback(async (code) => {
        if (Array.isArray(code)) {
            const ids = code.map(item => item.iso2_code);
            await removeRowMultiple(ids, 'blocked');
            setRowsSelected([]);
        } else {
            await removeRow(code, 'blocked');
        }

        await fetchDynamicData('event_log');

    }, [removeRow, removeRowMultiple]);

    const blockCountryByCode = useCallback(async (code) => {
        if (Array.isArray(code)) {
            const ids = code.map(item => item.iso2_code);
            await addRowMultiple(ids, 'blocked');
            setRowsSelected([]);
        } else {
            addRow(code, 'blocked');
        }

        await fetchDynamicData('event_log');

    }, [addRow, addRowMultiple]);

    const data = {...CountryDataTable.data};

    const generateFlag = useCallback((flag, title) => (
        <>
            <Flag
                countryCode={flag}
                style={{
                    fontSize: '2em',
                }}
                title={title}
            />
        </>
    ), []);

    const generateGoodBad = useCallback((value) => (
        value > 0 ? (
            <Icon name="circle-check" color='green'/>
        ) : (
            <Icon name="circle-times" color='red'/>
        )
    ), []);


    const ActionButton = ({ onClick, children, className }) => (
        <div className="rsssl-action-buttons__inner">
            <button
                className={`button ${className} rsssl-action-buttons__button`}
                onClick={onClick}
            >
                {children}
            </button>
        </div>
    );

    const generateActionButtons = useCallback((id, status, region_name) => (
        <div className="rsssl-action-buttons">
            {getCurrentFilter(moduleName) === 'blocked' && (
                <ActionButton onClick={() => allowById(id)} className="button-secondary">
                    {__("Allow", "really-simple-ssl")}
                </ActionButton>
            )}
            {getCurrentFilter(moduleName) === 'regions' && (
                <>
                    <ActionButton onClick={() => blockRegionByCode(id, region_name)} className="button-primary">
                        {__("Block", "really-simple-ssl")}
                    </ActionButton>
                    <ActionButton onClick={() => allowRegionByCode(id, region_name)} className="button-secondary">
                        {__("Allow", "really-simple-ssl")}
                    </ActionButton>
                </>
            )}
            {getCurrentFilter(moduleName) === 'countries' && (
                <>
                    {status === 'blocked' ? (
                        <ActionButton onClick={() => allowCountryByCode(id)} className="button-secondary">
                            {__("Allow", "really-simple-ssl")}
                        </ActionButton>
                    ) : (
                        <ActionButton onClick={() => blockCountryByCode(id)} className="button-primary">
                            {__("Block", "really-simple-ssl")}
                        </ActionButton>
                    )}
                </>
            )}
        </div>
    ), [getCurrentFilter, moduleName, allowById, blockRegionByCode, allowRegionByCode, blockCountryByCode, allowCountryByCode]);



    for (const key in data) {
        const dataItem = {...data[key]};
        if (getCurrentFilter(moduleName) === 'regions' || getCurrentFilter(moduleName) === 'countries') {
            dataItem.action = generateActionButtons(dataItem.attempt_value, dataItem.status, dataItem.region);
        } else {
            dataItem.action = generateActionButtons(dataItem.id);
        }
        dataItem.attempt_value = generateFlag(dataItem.attempt_value, dataItem.country_name);
        dataItem.status = __(dataItem.status = dataItem.status.charAt(0).toUpperCase() + dataItem.status.slice(1), 'really-simple-ssl');
        data[key] = dataItem;
    }


    if (!dataLoaded && columns.length === 0 && CountryDataTable.length === 0) {
        return (
            <div className="rsssl-spinner">
                <div className="rsssl-spinner__inner">
                    <div className="rsssl-spinner__icon"></div>
                    <div className="rsssl-spinner__text">{__("Loading...", "really-simple-ssl")}</div>
                </div>
            </div>
        );
    }

    const options = Object.entries(props.field.options).map(([value, label]) => ({ value, label }));

    const notifySuccess = (message) => toast.success(message);
    const notifyError = (message) => toast.error(message);

    return (
        <>
            {ToastContainer && (
                <ToastContainer
                    position="bottom-right"
                    autoClose={10000}
                    limit={3}
                    hideProgressBar
                    newestOnTop
                    closeOnClick
                    pauseOnFocusLoss
                    pauseOnHover
                    theme="light"
                /> )}
            <div className="rsssl-container">
                <div>
                    {/* reserved for left side buttons */}
                </div>
                <div className="rsssl-search-bar">
                    <div className="rsssl-search-bar__inner">
                        <div className="rsssl-search-bar__icon"></div>
                        <input
                            type="text"
                            className="rsssl-search-bar__input"
                            placeholder={__("Search", "really-simple-ssl")}
                            onChange={event => handleCountryTableSearch(event.target.value, searchableColumns)}
                        />
                    </div>
                </div>
            </div>
            {rowsSelected.length > 0 && (
                <div
                    style={{
                        marginTop: '1em',
                        marginBottom: '1em',
                    }}
                >
                    <div className={"rsssl-multiselect-datatable-form rsssl-primary"}>
                        <div>
                            {__("You have selected", "really-simple-ssl")} {rowsSelected.length} {__("rows", "really-simple-ssl")}
                        </div>
                        <div className="rsssl-action-buttons">
                            {getCurrentFilter(moduleName) === 'countries' && (
                                <>
                                    <ActionButton onClick={() => allowCountryByCode(rowsSelected)}>
                                        {__("Allow", "really-simple-ssl")}
                                    </ActionButton>
                                    <ActionButton onClick={() => blockCountryByCode(rowsSelected)}  className="button-primary">
                                        {__("Block", "really-simple-ssl")}
                                    </ActionButton>
                                </>
                            )}
                            {getCurrentFilter(moduleName) === 'blocked' && (
                                <ActionButton onClick={() => allowMultiple(rowsSelected)}>
                                    {__("Allow", "really-simple-ssl")}
                                </ActionButton>
                            )}
                            {getCurrentFilter(moduleName) === 'regions' && (
                                <>
                                    <ActionButton onClick={() => allowRegionByCode(rowsSelected)}  className="button-primary">
                                        {__("Allow", "really-simple-ssl")}
                                    </ActionButton>
                                    <ActionButton onClick={() => blockRegionByCode(rowsSelected)}>
                                        {__("Block", "really-simple-ssl")}
                                    </ActionButton>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            )}
            {!processing ?
            <DataTable
                columns={columns}
                data={Object.values(data)}
                dense
                pagination
                paginationServer
                paginationTotalRows={pagination.totalRows}
                paginationPerPage={pagination.perPage ?? 10}
                paginationDefaultPage={pagination.currentPage}
                paginationComponentOptions={{
                    rowsPerPageText: __('Rows per page:', 'really-simple-ssl'),
                    rangeSeparatorText: __('of', 'really-simple-ssl'),
                    noRowsPerPage: false,
                    selectAllRowsItem: false,
                    selectAllRowsItemText: __('All', 'really-simple-ssl'),

                }}
                onChangeRowsPerPage={handleCountryTableRowsChange}
                onChangePage={handleCountryTablePageChange}
                sortServer
                onSort={handleCountryTableSort}
                paginationRowsPerPageOptions={[10, 25, 50, 100]}
                noDataComponent={__("No results", "really-simple-ssl")}
                persistTableHead
                selectableRows
                clearSelectedRows={rowCleared}
                onSelectedRowsChange={handleSelection}
                theme="really-simple-plugins"
                customStyles={customStyles}
            /> :
            <div className="rsssl-spinner" style={{
                display: 'flex',
                justifyContent: 'center',
                alignItems: 'center',
                marginTop: "100px"
            }}>
                <div className="rsssl-spinner__inner">
                    <div className="rsssl-spinner__icon" style={{
                        border: '8px solid white',
                        borderTop: '8px solid #f4bf3e',
                        borderRadius: '50%',
                        width: '120px',
                        height: '120px',
                        animation: 'spin 2s linear infinite'
                    }}></div>
                    <div className="rsssl-spinner__text" style={{
                        position: 'absolute',
                        top: '50%',
                        left: '50%',
                        transform: 'translate(-50%, -50%)',
                    }}>{__("Loading data, please stand by...", "really-simple-ssl")}</div>
                </div>
            </div>
            }
            {!enabled && (
                <div className="rsssl-locked">
                    <div className="rsssl-locked-overlay"><span
                        className="rsssl-task-status rsssl-open">{__('Disabled', 'really-simple-ssl')}</span><span>{__('Limit login attempts to enable this block.', 'really-simple-ssl')}</span>
                    </div>
                </div>
            )}
        </>
    );
}

export default CountryDatatable;
