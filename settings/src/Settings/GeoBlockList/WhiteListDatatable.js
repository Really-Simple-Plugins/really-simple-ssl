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
        addRow,
        removeRow,
        pagination,
        addRegion,
        removeRegion,
        resetRow,
        rowCleared,
    } = WhiteListTableStore();

    const {showSavedSettingsNotice, saveFields} = FieldsData();

    const [rowsSelected, setRowsSelected] = useState([]);
    const [modalOpen, setModalOpen] = useState(false);
    const [tableHeight, setTableHeight] = useState(600);  // Starting height
    const rowHeight = 50; // Height of each row.
    const moduleName = 'rsssl-group-filter-geo_block_list_white_listing';
    const {fields, fieldAlreadyEnabled, getFieldValue} = useFields();
    const [searchTerm, setSearchTerm] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const [rowsPerPage, setRowsPerPage] = useState(10);

    const handlePageChange = (page) => {
        setCurrentPage(page);
    };
    const handlePerRowsChange = (newRowsPerPage) => {
        setRowsPerPage(newRowsPerPage);
    };

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

    useEffect(() => {
            fetchWhiteListData(field.action);

    }, [fieldAlreadyEnabled('firewall_enabled')]);

    let enabled = getFieldValue('firewall_enabled');


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
        //based on the current page and the rows per page we get the rows that are selected
        const {selectedCount, selectedRows, allSelected, allRowsSelected} = state;
        let rows = [];
        if (allSelected) {
            rows = selectedRows.slice((currentPage - 1) * rowsPerPage, currentPage * rowsPerPage);
            setRowsSelected(rows);
        } else {
            setRowsSelected(selectedRows);
        }
    }, [currentPage, rowsPerPage]);

    const allowRegionByCode = useCallback(async (code, regionName = '') => {
        if (Array.isArray(code)) {
            const ids = code.map(item => ({
                iso2_code: item.iso2_code,
                country_name: item.country_name
            }));
            ids.forEach((id) => {
                removeRegion(id.iso2_code).then((result) => {
                    showSavedSettingsNotice(result.message);
                });
            });
            setRowsSelected([]);
            await fetchWhiteListData(field.action);
            setRowsSelected([]);
        } else {
            await removeRegion(code).then((result) => {
                showSavedSettingsNotice(result.message);
            });
        }
    }, [removeRegion]);

    const allowById = useCallback((id) => {
        //We check if the id is an array
        if (Array.isArray(id)) {
            //We get all the iso2 codes and names from the array
            const ids = id.map(item => ({
                id: item.id,
            }));
            //we loop through the ids and allow them one by one
            ids.forEach((id) => {
                resetRow(id.id).then((result) => {
                    showSavedSettingsNotice(result.message);
                });
            });
            setRowsSelected([]);
        } else {
            resetRow(id).then((result) => {
                showSavedSettingsNotice(result.message);
            });
        }
        fetchWhiteListData(field.action);
    }, [resetRow]);

    const blockRegionByCode = useCallback(async (code, region = '') => {
        if (Array.isArray(code)) {
            const ids = code.map(item => ({
                iso2_code: item.iso2_code,
                country_name: item.country_name
            }));
            ids.forEach((id) => {
                addRegion(id.iso2_code).then((result) => {
                    showSavedSettingsNotice(result.message);
                });
            });
            setRowsSelected([]);
            await fetchWhiteListData(field.action);
            setRowsSelected([]);
        } else {
            await addRegion(code).then((result) => {
                showSavedSettingsNotice(result.message);
            });
        }

    }, [addRegion]);

    const allowCountryByCode = useCallback(async (code) => {
        if (Array.isArray(code)) {
            const ids = code.map(item => ({
                iso2_code: item.iso2_code,
                country_name: item.country_name
            }));
            //we loop through the ids and allow them one by one
            ids.forEach((id) => {
                removeRow(id.iso2_code).then((result) => {
                    showSavedSettingsNotice(result.message);
                });
            });
            setRowsSelected([]);
            await fetchWhiteListData(field.action);
        } else {
            await removeRow(code).then((result) => {
                showSavedSettingsNotice(result.message);
            });
        }

    }, [removeRow]);

    const blockCountryByCode = useCallback(async (code, name) => {
        if (Array.isArray(code)) {
            //We get all the iso2 codes and names from the array
            const ids = code.map(item => ({
                iso2_code: item.iso2_code,
                country_name: item.country_name
            }));
            //we loop through the ids and block them one by one
            ids.forEach((id) => {
                addRow(id.iso2_code, id.country_name).then((result) => {
                    showSavedSettingsNotice(result.message);
                });
            });
            setRowsSelected([]);
        } else {
            await addRow(code, name).then((result) => {
                showSavedSettingsNotice(result.message);
            });
        }

    }, [addRow]);

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

    const handleClose = () => {
        setModalOpen(false);
    }

    const handleOpen = () => {
        setModalOpen(true);
    }

    const generateActionButtons = useCallback((id) => {
        return (<div className="rsssl-action-buttons">
                <ActionButton
                    onClick={() => allowById(id)} className="button-red">
                    {__("Reset", "really-simple-ssl")}
                </ActionButton>
        </div>)
    }, [moduleName, allowById, blockRegionByCode, allowRegionByCode, blockCountryByCode, allowCountryByCode]);



    for (const key in data) {
        const dataItem = {...data[key]};
        dataItem.action = generateActionButtons(dataItem.id);
        dataItem.flag = generateFlag(dataItem.iso2_code, dataItem.country_name);
        dataItem.status = __(dataItem.status = dataItem.status.charAt(0).toUpperCase() + dataItem.status.slice(1), 'really-simple-ssl');
        data[key] = dataItem;
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

    useEffect(() => {
        const filteredData = Object.entries(data)
            .filter(([_, dataItem]) => {
                return Object.values(dataItem).some(val => ((val ?? '').toString().toLowerCase().includes(searchTerm.toLowerCase())));
            })
            .map(([key, dataItem]) => {
                const newItem = { ...dataItem };
                newItem.action = generateActionButtons(newItem.id);
                newItem.flag = generateFlag(newItem.iso2_code, newItem.country_name);
                newItem.status = __(newItem.status = newItem.status.charAt(0).toUpperCase() + newItem.status.slice(1), 'really-simple-ssl');
                return [key, newItem];
            })
            .reduce((obj, [key, val]) => ({ ...obj, [key]: val }), {});
    }, [searchTerm, data]);



    return (
        <>
            <TrustIpAddressModal
                isOpen={modalOpen}
                onRequestClose={handleClose}
                value={ipAddress}
                status={'trusted'}
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
                <div className="rsssl-search-bar">
                    <div className="rsssl-search-bar__inner">
                        <div className="rsssl-search-bar__icon"></div>
                        <input
                            type="text"
                            className="rsssl-search-bar__input"
                            placeholder={__("Search", "really-simple-ssl")}
                            onChange={e => setSearchTerm(e.target.value)}
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
                            <>
                                <ActionButton
                                    onClick={() => allowById(rowsSelected)}  className="button-red">
                                        {__("Reset", "really-simple-ssl")}
                                    </ActionButton>
                                </>
                        </div>
                    </div>
                </div>
            )}
            <DataTable
                columns={columns}
                data={Object.values(data).filter((row) => {
                    return Object.values(row).some((val) => ((val ?? '').toString().toLowerCase()).includes(searchTerm.toLowerCase()));
                })}
                dense
                pagination={true}
                paginationComponentOptions={{
                    rowsPerPageText: __('Rows per page:', 'really-simple-ssl'),
                    rangeSeparatorText: __('of', 'really-simple-ssl'),
                    noRowsPerPage: false,
                    selectAllRowsItem: false,
                    selectAllRowsItemText: __('All', 'really-simple-ssl'),

                }}
                noDataComponent={__("No results", "really-simple-ssl")}
                persistTableHead
                selectableRows={true}
                clearSelectedRows={rowCleared}
                paginationPerPage={rowsPerPage}
                onChangePage={handlePageChange}
                onChangeRowsPerPage={handlePerRowsChange}
                onSelectedRowsChange={handleSelection}
                theme="really-simple-plugins"
                customStyles={customStyles}
            />
            {!enabled && (
                <div className="rsssl-locked">
                    <div className="rsssl-locked-overlay"><span
                        className="rsssl-task-status rsssl-open">{__('Disabled', 'really-simple-ssl')}</span><span>{__('Here you can add IP addresses that should never be blocked by region restrictions.', 'really-simple-ssl')}</span>
                    </div>
                </div>
            )}
        </>
    );
}

export default WhiteListDatatable;