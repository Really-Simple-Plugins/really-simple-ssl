import React, { useEffect, useState, useCallback } from 'react';
import DataTable, { createTheme } from "react-data-table-component";
import CountryDataTableStore from "./CountryDataTableStore";
import EventLogDataTableStore from "../EventLog/EventLogDataTableStore";
import FilterData from "../FilterData";
import Flag from "../../utils/Flag/Flag";
import { button } from "@wordpress/components";
import { __ } from '@wordpress/i18n';
import useFields from "../FieldsData";

const CountryDatatable = (props) => {
    const {
        CountryDataTable,
        dataLoaded,
        fetchCountryData,
        handleCountryTableFilter,
        addRow,
        removeRow,
        pagination,
        handleCountryTablePageChange,
        handleCountryTableRowsChange,
        handleCountryTableSort,
        handleCountryTableSearch,
        addRegion,
        removeRegion,
        addRowMultiple,
        removeRowMultiple,
        resetRow,
        resetMultiRow,
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
    const [rowCleared, setRowCleared] = useState(false);
    const moduleName = 'rsssl-group-filter-limit_login_attempts_country';
    const {fields, fieldAlreadyEnabled, getFieldValue} = useFields();

    const buildColumn = useCallback((column) => ({
        name: column.name,
        sortable: column.sortable,
        searchable: column.searchable,
        width: column.width,
        visible: column.visible,
        column: column.column,
        selector: row => row[column.column],
    }), []);

    const columns = props.field.columns.map(buildColumn);

    const searchableColumns = columns
        .filter(column => column.searchable)
        .map(column => column.column);

    //get data if field was already enabled, so not changed right now.
    useEffect(() => {
        if (fieldAlreadyEnabled && !dataLoaded) {
            fetchCountryData(props.field.action);
        }
    }, [fieldAlreadyEnabled, dataLoaded, fetchCountryData, props.field.action]);

    useEffect(() => {
        // code to execute after DynamicDataTable has been updated
    }, [DynamicDataTable]);

    useEffect(() => {
        const currentFilter = getCurrentFilter(moduleName);
        if (!currentFilter) {
            setSelectedFilter('blocked', moduleName);
        }
        handleCountryTableFilter('status', currentFilter);
        setTimeout(() => {
            setRowCleared(true);
            setTimeout(() => setRowCleared(false), 100);
        }, 100);

    }, [selectedFilter, moduleName, handleCountryTableFilter, getCurrentFilter, setSelectedFilter, CountryDatatable]);

    useEffect(() => {
        setRowCleared(false);
        setRowsSelected([]);
    }, [CountryDataTable]);

    useEffect(() => {
        if (!dataLoaded) {
            fetchCountryData(props.field.action);
        }
    }, [dataLoaded, props.field.action, fetchCountryData]);

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

    const allowRegionByCode = async (code) => {
        if (Array.isArray(code)) {
            for (let item of code) {
                await removeRegion(item, 'blocked');
            }
            setRowsSelected([]);
        } else {
            await removeRegion(code, 'blocked');
        }
        setRowCleared(true);
        await new Promise(resolve => setTimeout(resolve, 100));
        setRowCleared(false);
        await fetchDynamicData('event_log');
    };


    const allowMultiple = useCallback((rows) => {
        const ids = rows.map(item => item.id);
        resetMultiRow(ids, 'blocked');
    }, [resetMultiRow]);

    const allowById = useCallback((id) => {
        resetRow(id, 'blocked');
    }, [resetRow]);

    const blockRegionByCode = useCallback((code) => {
        if (Array.isArray(code)) {
            code.forEach(item => addRegion(item.attempt_value, 'blocked'));
            setRowsSelected([]);
        } else {
            addRegion(code, 'blocked');
        }
        setTimeout(() => {
            setRowCleared(true);
            setTimeout(() => setRowCleared(false), 100);
            setTimeout(() =>  fetchDynamicData('event_log'), 100);
        }, 100);

    }, [addRegion]);

    const allowCountryByCode = useCallback((code) => {
        if (Array.isArray(code)) {
            const ids = code.map(item => item.iso2_code);
            removeRowMultiple(ids, 'blocked');
            setRowsSelected([]);
        } else {
            removeRow(code, 'blocked');
        }
        setTimeout(() => {
            setRowCleared(true);
            setTimeout(() => setRowCleared(false), 100);
            setTimeout(() =>  fetchDynamicData('event_log'), 100);
        }, 100);
    }, [removeRow, removeRowMultiple]);

    const blockCountryByCode = useCallback((code) => {
        if (Array.isArray(code)) {
            const ids = code.map(item => item.iso2_code);
            addRowMultiple(ids, 'blocked');
            setRowsSelected([]);
        } else {
            addRow(code, 'blocked');
        }
        setTimeout(() => {
            setRowCleared(true);
            setTimeout(() => setRowCleared(false), 100);
            setTimeout(() =>  fetchDynamicData('event_log'), 100);
        }, 100);
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

    const generateActionButtons = useCallback((id, status) => (
        <div className="rsssl-action-buttons">
            {getCurrentFilter(moduleName) === 'blocked' && (
                <ActionButton onClick={() => allowById(id)} className="button-secondary">
                    {__("Allow", "really-simple-ssl")}
                </ActionButton>
            )}
            {getCurrentFilter(moduleName) === 'regions' && (
                <>
                    <ActionButton onClick={() => blockRegionByCode(id)} className="button-primary">
                        {__("Block", "really-simple-ssl")}
                    </ActionButton>
                    <ActionButton onClick={() => allowRegionByCode(id)} className="button-secondary">
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
            dataItem.action = generateActionButtons(dataItem.attempt_value, dataItem.status);
        } else {
            dataItem.action = generateActionButtons(dataItem.id);
        }
        dataItem.attempt_value = generateFlag(dataItem.attempt_value, dataItem.country_name);
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

    return (
        <>
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
            <DataTable
                columns={columns}
                data={Object.values(data)}
                dense
                pagination
                paginationServer
                paginationTotalRows={pagination.totalRows ?? 0}
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
            />
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
