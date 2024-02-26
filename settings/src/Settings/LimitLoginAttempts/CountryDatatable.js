import {useEffect, useState, useCallback} from 'react';
import DataTable, {createTheme} from "react-data-table-component";
import FieldsData from "../FieldsData";
import CountryDataTableStore from "./CountryDataTableStore";
import EventLogDataTableStore from "../EventLog/EventLogDataTableStore";
import FilterData from "../FilterData";
import Flag from "../../utils/Flag/Flag";
import {__} from '@wordpress/i18n';
import useFields from "../FieldsData";
import SearchBar from "../DynamicDataTable/SearchBar";

const CountryDatatable = (props) => {
    const {
        CountryDataTable,
        dataLoaded,
        fetchData,
        processing,
        handleCountryTableFilter,
        updateRow,
        pagination,
        handleCountryTablePageChange,
        handleCountryTableRowsChange,
        handleCountryTableSort,
        handleCountryTableSearch,
        addRegion,
        resetRegions,
        addRowMultiple,
        resetRow,
        resetMultiRow,
        updateRowRegion,
        dataActions,
        rowCleared,
        setDataActions,
    } = CountryDataTableStore();

    const {showSavedSettingsNotice, saveFields} = FieldsData();

    const {
        DynamicDataTable,
        fetchDynamicData,
    } = EventLogDataTableStore();

    const {
        selectedFilter,
        setSelectedFilter,
        activeGroupId,
        getCurrentFilter,
        setProcessingFilter,
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
        setProcessingFilter(processing);
        handleCountryTableFilter('status', currentFilter);

    }, [moduleName, handleCountryTableFilter, getCurrentFilter(moduleName), setSelectedFilter, CountryDatatable, processing]);

    useEffect(() => {
        if (dataActions.filterColumn === 'status') {
            const {search, searchColumns, ...rest} = dataActions;
            setDataActions(rest);
        }
    }, [dataActions.filterColumn])

    useEffect(() => {
        setRowsSelected([]);
    }, [CountryDataTable]);

    //if the dataActions are changed, we fetch the data
    useEffect(() => {
        //we make sure the dataActions are changed in the store before we fetch the data
        if (dataActions) {
            fetchData(field.action, dataActions)
        }
    }, [dataActions.sortDirection, dataActions.filterValue, dataActions.search, dataActions.page, dataActions.currentRowsPerPage, fieldAlreadyEnabled('enable_limited_login_attempts')]);

    let enabled = getFieldValue('enable_limited_login_attempts');


    useEffect(() => {
        return () => {
            saveFields(false, false)
        };
    }, [enabled]);


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

    const allowRegionByCode = useCallback(async (code, regionName = '') => {
        if (Array.isArray(code)) {
            const ids = code.map(item => item.id);
            const regions = code.map(item => item.iso2_code);
            regions.forEach((code) => {
                resetRegions(code, dataActions).then(
                    (response) => {
                        if (response.success) {
                            showSavedSettingsNotice(response.message);
                        } else {
                            showSavedSettingsNotice(response.message, 'error');
                        }
                    });
            });
            setRowsSelected([]);
        } else {
            await resetRegions(code, dataActions);
            showSavedSettingsNotice(__('%s is now allowed', 'really-simple-ssl')
                .replace('%s', regionName));
        }
        await fetchDynamicData('event_log');
    }, [resetRegions, getCurrentFilter(moduleName), dataActions]);


    const allowMultiple = useCallback((rows) => {
        const ids = rows.map(item => item.id);
        resetMultiRow(ids, dataActions).then((response) => {
            if (response && response.success) {
                showSavedSettingsNotice(response.message);
            } else {
                showSavedSettingsNotice(response.message, 'error');
            }
        });
    }, [resetMultiRow, getCurrentFilter(moduleName), dataActions]);

    const allowById = useCallback((id) => {
        resetRow(id, dataActions).then(
            (response) => {
                if (response.success) {
                    showSavedSettingsNotice(response.message);
                }
            }
        );
    }, [resetRow, getCurrentFilter(moduleName), dataActions]);

    const blockRegionByCode = useCallback(async (code, region = '') => {
        if (Array.isArray(code)) {
            const ids = code.map(item => item.id);
            const regions = code.map(item => item.iso2_code);
            regions.forEach((code) => {
                updateRowRegion(code, 'blocked', dataActions).then(
                    (response) => {
                        if (response.success) {
                            showSavedSettingsNotice(response.message);
                        } else {
                            showSavedSettingsNotice(response.message, 'error');
                        }
                    });
            });
        } else {
            updateRowRegion(code, 'blocked', dataActions).then(
                (response) => {
                    if (response.success) {
                        showSavedSettingsNotice(response.message);
                    } else {
                        showSavedSettingsNotice(response.message, 'error');
                    }
                });
        }

        await fetchDynamicData('event_log');

    }, [addRegion, getCurrentFilter(moduleName), dataActions]);

    const blockCountryByCode = useCallback(async (code) => {
        if (Array.isArray(code)) {
            const ids = code.map(item => item.iso2_code);

            setRowsSelected([]);
        } else {
            await updateRow(code, 'blocked', dataActions).then(
                (response) => {
                    if (response.success) {
                        showSavedSettingsNotice(response.message);
                    } else {
                        showSavedSettingsNotice(response.message, 'error');
                    }
                }
            );
        }

        await fetchDynamicData('event_log');

    }, [updateRow, addRowMultiple, dataActions, getCurrentFilter(moduleName)]);

    const data = {...CountryDataTable.data};

    const generateFlag = useCallback((flag, title) => (
        <>
            <Flag
                countryCode={flag}
                style={{
                    fontSize: '2em',
                }}
                title={title}
                continent={(getCurrentFilter(moduleName) === 'regions')}
            />
        </>
    ), []);

    const ActionButton = ({onClick, children, className}) => (
        // <div className={`rsssl-action-buttons__inner`}>
        <button
            className={`button ${className} rsssl-action-buttons__button`}
            onClick={onClick}
            disabled={processing}
        >
            {children}
        </button>
        // </div>
    );

    const generateActionButtons = useCallback((id, status, region_name) => (
        <div className="rsssl-action-buttons">
            {getCurrentFilter(moduleName) === 'blocked' && (
                <ActionButton onClick={() => allowById(id)}
                              className="button-secondary">
                    {__("Allow", "really-simple-ssl")}
                </ActionButton>
            )}
            {getCurrentFilter(moduleName) === 'regions' && (
                <>
                    <ActionButton
                        onClick={() => blockRegionByCode(id, region_name)} className="button-primary">
                        {__("Block", "really-simple-ssl")}
                    </ActionButton>
                    <ActionButton
                        onClick={() => allowRegionByCode(id, region_name)} className="button-secondary">
                        {__("Allow", "really-simple-ssl")}
                    </ActionButton>
                </>
            )}
            {getCurrentFilter(moduleName) === 'countries' && (
                <>
                    {status === 'blocked' ? (
                        <ActionButton
                            onClick={() => allowById(id)} className="button-secondary">
                            {__("Allow", "really-simple-ssl")}
                        </ActionButton>
                    ) : (
                        <ActionButton
                            onClick={() => blockCountryByCode(id)} className="button-primary">
                            {__("Block", "really-simple-ssl")}
                        </ActionButton>
                    )}
                </>
            )}
        </div>
    ), [getCurrentFilter, moduleName, allowById, blockRegionByCode, allowRegionByCode, blockCountryByCode]);


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

    const options = Object.entries(props.field.options).map(([value, label]) => ({value, label}));

    let paginationSet = true;
    if (typeof pagination === 'undefined') {
        paginationSet = false;
    }

    return (
        <>
            <div className="rsssl-container">
                <div>
                    {/* reserved for left side buttons */}
                </div>
                <SearchBar handleSearch={handleCountryTableSearch} searchableColumns={searchableColumns}/>
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
                            {__("You have selected %s rows", "really-simple-ssl").replace('%s', rowsSelected.length)}
                        </div>
                        <div className="rsssl-action-buttons">
                            {getCurrentFilter(moduleName) === 'countries' && (
                                <>
                                    <ActionButton
                                        onClick={() => allowById(rowsSelected)}>
                                        {__("Allow", "really-simple-ssl")}
                                    </ActionButton>
                                    <ActionButton
                                        onClick={() => blockCountryByCode(rowsSelected)} className="button-primary">
                                        {__("Block", "really-simple-ssl")}
                                    </ActionButton>
                                </>
                            )}
                            {getCurrentFilter(moduleName) === 'blocked' && (
                                <ActionButton
                                    onClick={() => allowMultiple(rowsSelected)}>
                                    {__("Allow", "really-simple-ssl")}
                                </ActionButton>
                            )}
                            {getCurrentFilter(moduleName) === 'regions' && (
                                <>
                                    <ActionButton
                                        onClick={() => blockRegionByCode(rowsSelected)} className="button-primary">
                                        {__("Block", "really-simple-ssl")}
                                    </ActionButton>
                                    <ActionButton
                                        onClick={() => allowRegionByCode(rowsSelected)} className="button-secondary">
                                        {__("Allow", "really-simple-ssl")}
                                    </ActionButton>

                                </>
                            )}
                        </div>
                    </div>
                </div>
            )}
            <DataTable
                columns={columns}
                data={processing ? [] : Object.values(data)}
                dense
                pagination={!processing}
                paginationServer
                paginationTotalRows={paginationSet ? pagination.totalRows : 10}
                paginationPerPage={paginationSet ? pagination.perPage : 10}
                paginationDefaultPage={paginationSet ? pagination.currentPage : 1}
                paginationComponentOptions={{
                    rowsPerPageText: __('Rows per page:', 'really-simple-ssl'),
                    rangeSeparatorText: __('of', 'really-simple-ssl'),
                    noRowsPerPage: false,
                    selectAllRowsItem: false,
                    selectAllRowsItemText: __('All', 'really-simple-ssl'),

                }}
                onChangeRowsPerPage={handleCountryTableRowsChange}
                onChangePage={handleCountryTablePageChange}
                sortServer={!processing}
                onSort={handleCountryTableSort}
                paginationRowsPerPageOptions={[10, 25, 50, 100]}
                noDataComponent={__("No results", "really-simple-ssl")}
                persistTableHead
                selectableRows={!processing}
                clearSelectedRows={rowCleared}
                onSelectedRowsChange={handleSelection}
                theme="really-simple-plugins"
                customStyles={customStyles}
            />
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

export default CountryDatatable;
