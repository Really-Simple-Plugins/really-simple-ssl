import React, { useEffect, useState, useCallback } from 'react';
import DataTable, { createTheme } from "react-data-table-component";
import FieldsData from "../FieldsData";
import WhiteListTableStore from "./WhiteListTableStore";
import FilterData from "../FilterData";
import Flag from "../../utils/Flag/Flag";
import { __ } from '@wordpress/i18n';
import useFields from "../FieldsData";
import SearchBar from "../DynamicDataTable/SearchBar";
import AddButton from "./AddButton";
import AddIpAddressModal from "../LimitLoginAttempts/AddIpAddressModal";
import TrustIpAddressModal from "./TrustIpAddressModal";

const WhiteListDatatable = (props) => {
    const {
        WhiteListTable,
        dataLoaded,
        fetchWhiteListData,
        processing,
        ipAddress,
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
    } = WhiteListTableStore();

    const {showSavedSettingsNotice, saveFields} = FieldsData();

    const [rowsSelected, setRowsSelected] = useState([]);
    const moduleName = 'rsssl-group-filter-geo_block_list_white_listing';
    const {fields, fieldAlreadyEnabled, getFieldValue} = useFields();

    /**
     * Build a column configuration object.
     *
     * @param {object} column - The column object.
     * @param {string} column.name - The name of the column.
     * @param {boolean} column.sortable - Whether the column is sortable.
     * @param {boolean} column.searchable - Whether the column is searchable.
     * @param {number} column.width - The width of the column.
     * @param {boolean} column.visible - Whether the column is visible.
     * @param {string} column.column - The column identifier.
     *
     * @returns {object} The column configuration object.
     */
    const buildColumn = useCallback((column) => ({
        //if the filter is set to region and the columns = status we do not want to show the column
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
        setRowsSelected([]);
    }, [WhiteListTable]);

    //if the dataActions are changed, we fetch the data
    useEffect(() => {
        //we make sure the dataActions are changed in the store before we fetch the data
            fetchWhiteListData(field.action, dataActions);

    }, [dataActions.sortDirection, dataActions.search, dataActions.page,
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
            await fetchWhiteListData(field.action, dataActions);
            setRowsSelected([]);
        } else {
            await removeRegion(code, dataActions).then((result) => {
                showSavedSettingsNotice(result.message);
            });
        }
    }, [removeRegion, dataActions]);

    const allowById = useCallback((id) => {
        resetRow(id, 'blocked', dataActions);
    }, [resetRow, dataActions]);

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
            await fetchWhiteListData(field.action, dataActions);
            setRowsSelected([]);
        } else {
            await addRegion(code, dataActions).then((result) => {
                showSavedSettingsNotice(result.message);
            });
        }

    }, [addRegion, dataActions]);

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
            await fetchWhiteListData(field.action, dataActions);
        } else {
            await removeRow(code, dataActions).then((result) => {
                showSavedSettingsNotice(result.message);
            });
        }

    }, [removeRow, dataActions]);

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

    }, [addRow, dataActions]);

    const data = {...WhiteListTable.data};

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

    const addingIpAddress = ({
        ip
    }) => {
       alert(ip);
    }

    const handleClose = () => {
        alert('close');
    }

    const handleOpen = () => {
        alert('open');
    }

    const generateActionButtons = useCallback((code, name, region_name) => {
        return (<div className="rsssl-action-buttons">
                <ActionButton
                    onClick={() => blockCountryByCode(code, name)} className="button-red">
                    {__("Reset", "really-simple-ssl")}
                </ActionButton>
        </div>)
    }, [moduleName, allowById, blockRegionByCode, allowRegionByCode, blockCountryByCode, allowCountryByCode]);



    for (const key in data) {
        const dataItem = {...data[key]};
        dataItem.action = generateActionButtons(dataItem.iso2_code, dataItem.status, dataItem.region);
        dataItem.flag = generateFlag(dataItem.iso2_code, dataItem.country_name);
        dataItem.status = __(dataItem.status = dataItem.status.charAt(0).toUpperCase() + dataItem.status.slice(1), 'really-simple-ssl');
        data[key] = dataItem;
    }

    let paginationSet = true;
    if (typeof pagination === 'undefined') {
        paginationSet = false;
    }


    return (
        <>
            <TrustIpAddressModal
                isOpen={addingIpAddress}
                onRequestClose={handleClose}
                value={ipAddress}
                status={'trusted'}
                dataActions={dataActions}
            >
            </TrustIpAddressModal>
            <div className="rsssl-container">
                    {/*display the add button on left side*/}
                    <AddButton
                        moduleName={moduleName}
                        handleOpen={handleOpen}
                        processing={processing}
                        blockedText={__("Block IP Address", "really-simple-ssl")}
                        allowedText={__("Trust IP Address", "really-simple-ssl")}
                    />
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
                            <>
                                <ActionButton
                                    onClick={() => blockCountryByCode(rowsSelected)}  className="button-primary">
                                        {__("Block", "really-simple-ssl")}
                                    </ActionButton>
                                </>
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

export default WhiteListDatatable;