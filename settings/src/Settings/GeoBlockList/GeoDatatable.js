import { useEffect, useState, useCallback } from 'react';
import DataTable, { createTheme } from "react-data-table-component";
import FieldsData from "../FieldsData";
import GeoDataTableStore from "./GeoDataTableStore";
import EventLogDataTableStore from "../EventLog/EventLogDataTableStore";
import FilterData from "../FilterData";
import Flag from "../../utils/Flag/Flag";
import { __ } from '@wordpress/i18n';
import useFields from "../FieldsData";

const GeoDatatable = (props) => {
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
        removeRegion,
        resetRow,
        dataActions,
        rowCleared,
    } = GeoDataTableStore();

    const {showSavedSettingsNotice, saveFields} = FieldsData();

    const {
        selectedFilter,
        setSelectedFilter,
        activeGroupId,
        getCurrentFilter,
        setProcessingFilter,
    } = FilterData();

    const [rowsSelected, setRowsSelected] = useState([]);
    const moduleName = 'rsssl-group-filter-geo_block_list_listing';
    const {fields, fieldAlreadyEnabled, getFieldValue} = useFields();

    const buildColumn = useCallback((column) => ({
        //if the filter is set to region and the columns = status we do not want to show the column
        omit: getCurrentFilter(moduleName) === 'regions' && column.column === 'region_name',
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
    }, [moduleName, handleCountryTableFilter, getCurrentFilter(moduleName), setSelectedFilter, GeoDatatable, processing]);

    useEffect(() => {
        setRowsSelected([]);
    }, [CountryDataTable]);

    //if the dataActions are changed, we fetch the data
    useEffect(() => {
        //we make sure the dataActions are changed in the store before we fetch the data
        if (dataActions) {
            fetchCountryData(field.action, dataActions)
        }
    }, [dataActions.sortDirection, dataActions.filterValue, dataActions.search, dataActions.page,
        dataActions.currentRowsPerPage, fieldAlreadyEnabled('geo_blocklist_enabled')]);

    let enabled = getFieldValue('geo_blocklist_enabled');


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
            const ids = code.map(item => ({
                iso2_code: item.iso2_code,
                country_name: item.country_name
            }));
            ids.forEach((id) => {
                removeRegion(id.iso2_code, dataActions).then((result) => {
                    showSavedSettingsNotice(result.message);
                });
            });
            setRowsSelected([]);
            await fetchCountryData('rsssl_geo_list', dataActions);
            setRowsSelected([]);
        } else {
            await removeRegion(code, dataActions).then((result) => {
                showSavedSettingsNotice(result.message);
            });
        }
    }, [removeRegion, getCurrentFilter(moduleName), dataActions]);

    const allowById = useCallback((id) => {
        resetRow(id, 'blocked', dataActions);
    }, [resetRow,getCurrentFilter(moduleName), dataActions]);

    const blockRegionByCode = useCallback(async (code, region = '') => {
        if (Array.isArray(code)) {
            const ids = code.map(item => ({
                iso2_code: item.iso2_code,
                country_name: item.country_name
            }));
            ids.forEach((id) => {
                addRegion(id.iso2_code, dataActions).then((result) => {
                    showSavedSettingsNotice(result.message);
                });
            });
            setRowsSelected([]);
            await fetchCountryData('rsssl_geo_list', dataActions);
            setRowsSelected([]);
        } else {
            await addRegion(code, dataActions).then((result) => {
                showSavedSettingsNotice(result.message);
            });
        }

    }, [addRegion, getCurrentFilter(moduleName), dataActions]);

    const allowCountryByCode = useCallback(async (code) => {
        if (Array.isArray(code)) {
            const ids = code.map(item => ({
                iso2_code: item.iso2_code,
                country_name: item.country_name
            }));
            //we loop through the ids and allow them one by one
            ids.forEach((id) => {
                removeRow(id.iso2_code, dataActions).then((result) => {
                    showSavedSettingsNotice(result.message);
                });
            });
            setRowsSelected([]);
            await fetchCountryData('rsssl_geo_list', dataActions);
        } else {
            await removeRow(code, dataActions).then((result) => {
                showSavedSettingsNotice(result.message);
            });
        }

    }, [removeRow, dataActions, getCurrentFilter(moduleName)]);

    const blockCountryByCode = useCallback(async (code, name) => {
        if (Array.isArray(code)) {
            //We get all the iso2 codes and names from the array
            const ids = code.map(item => ({
                iso2_code: item.iso2_code,
                country_name: item.country_name
            }));
            //we loop through the ids and block them one by one
            ids.forEach((id) => {
                addRow(id.iso2_code, id.country_name, dataActions).then((result) => {
                    showSavedSettingsNotice(result.message);
                });
            });
            setRowsSelected([]);
        } else {
            await addRow(code, name, dataActions).then((result) => {
                showSavedSettingsNotice(result.message);
            });
        }

    }, [addRow, dataActions, getCurrentFilter(moduleName)]);

    const data = {...CountryDataTable.data};

    console.log(getCurrentFilter(moduleName))

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

    const ActionButton = ({ onClick, children, className }) => (
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

    const generateActionButtons = useCallback((code, name, region_name) => {
        return (<div className="rsssl-action-buttons">
            {getCurrentFilter(moduleName) === 'blocked' && (
                <ActionButton onClick={() => allowCountryByCode(code)}
                              className="button-secondary">
                    {__("Allow", "really-simple-ssl")}
                </ActionButton>
            )}
            {getCurrentFilter(moduleName) === 'regions' && (
                <>
                <ActionButton onClick={() => blockRegionByCode(code, region_name)}
                                className="button-primary">
                    {__("Block", "really-simple-ssl")}
                </ActionButton>
                <ActionButton onClick={() => allowRegionByCode(code, region_name)}
                                className="button-secondary">
                    {__("Allow", "really-simple-ssl")}
                </ActionButton>
                </>
            )}
            {getCurrentFilter(moduleName) === 'countries' && (
                <ActionButton
                    onClick={() => blockCountryByCode(code, name)} className="button-primary">
                    {__("Block", "really-simple-ssl")}
                </ActionButton>
            )}
        </div>)
    }, [getCurrentFilter, moduleName, allowById, blockRegionByCode, allowRegionByCode, blockCountryByCode, allowCountryByCode]);



    for (const key in data) {
        const dataItem = {...data[key]};
        if (getCurrentFilter(moduleName) === 'regions' || getCurrentFilter(moduleName) === 'countries') {
            dataItem.action = generateActionButtons(dataItem.iso2_code, dataItem.country_name, dataItem.region);
        } else {
            dataItem.action = generateActionButtons(dataItem.iso2_code, dataItem.status, dataItem.region);
        }
        dataItem.flag = generateFlag(dataItem.iso2_code, dataItem.country_name);
        dataItem.status = __(dataItem.status = dataItem.status.charAt(0).toUpperCase() + dataItem.status.slice(1), 'really-simple-ssl');
        data[key] = dataItem;
    }

    const options = Object.entries(props.field.options).map(([value, label]) => ({ value, label }));

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
                <div className="rsssl-search-bar">
                    <div className="rsssl-search-bar__inner">
                        <div className="rsssl-search-bar__icon"></div>
                        <input
                            type="text"
                            className="rsssl-search-bar__input"
                            placeholder={__("Search", "really-simple-ssl")}
                            disabled={processing}
                            onKeyUp={event => {
                                if (event.key === 'Enter') {
                                    handleCountryTableSearch(event.target.value, searchableColumns);
                                }
                            }}
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
                            {__("You have selected %s rows", "really-simple-ssl").replace('%s', rowsSelected.length)}
                        </div>
                        <div className="rsssl-action-buttons">
                            {getCurrentFilter(moduleName) === 'countries' && (
                                <>
                                    <ActionButton
                                        onClick={() => blockCountryByCode(rowsSelected)}  className="button-primary">
                                        {__("Block", "really-simple-ssl")}
                                    </ActionButton>
                                </>
                            )}
                            {getCurrentFilter(moduleName) === 'regions' && (
                                <>
                                    <ActionButton
                                        onClick={() => allowRegionByCode(rowsSelected)}  className="button-secondary">
                                        {__("Allow", "really-simple-ssl")}
                                    </ActionButton>
                                    <ActionButton
                                        onClick={() => blockRegionByCode(rowsSelected)}  className="button-primary">
                                        {__("Block", "really-simple-ssl")}
                                    </ActionButton>
                                </>
                            )}
                            {getCurrentFilter(moduleName) === 'blocked' && (
                                <ActionButton
                                    onClick={() => allowCountryByCode(rowsSelected)}>
                                    {__("Allow", "really-simple-ssl")}
                                </ActionButton>
                            )}
                        </div>
                    </div>
                </div>
            )}
            <DataTable
                columns={columns}
                data={processing? [] : Object.values(data)}
                dense
                pagination={!processing}
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
                        className="rsssl-task-status rsssl-open">{__('Disabled', 'really-simple-ssl')}</span><span>{__('Activate region restrictions to enable this block.', 'really-simple-ssl')}</span>
                    </div>
                </div>
            )}
        </>
    );
}

export default GeoDatatable;
