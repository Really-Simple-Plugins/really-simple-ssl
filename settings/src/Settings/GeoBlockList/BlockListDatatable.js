import React, { useEffect, useState, useCallback } from '@wordpress/element';
import DataTable, {createTheme} from "react-data-table-component";
import FieldsData from "../FieldsData";
import WhiteListTableStore from "./WhiteListTableStore";
import FilterData from "../FilterData";
import Flag from "../../utils/Flag/Flag";
import {__} from '@wordpress/i18n';
import useFields from "../FieldsData";
import AddButton from "./AddButton";
import TrustIpAddressModal from "./TrustIpAddressModal";

const BlockListDatatable = (props) => {
    const {
        BlockListData,
        WhiteListTable,
        fetchData,
        processing_block,
        ipAddress,
        pagination,
        resetRow,
        rowCleared,
    } = WhiteListTableStore();

    const {showSavedSettingsNotice, saveFields} = FieldsData();

    const [rowsSelected, setRowsSelected] = useState([]);
    const [modalOpen, setModalOpen] = useState(false);
    const [tableHeight, setTableHeight] = useState(600);  // Starting height
    const rowHeight = 50; // Height of each row.
    const moduleName = 'rsssl-group-filter-firewall_block_list_listing';
    const {fields, fieldAlreadyEnabled, getFieldValue} = useFields();
    const [searchTerm, setSearchTerm] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const [rowsPerPage, setRowsPerPage] = useState(10);
    const [DataTable, setDataTable] = useState(null);
    const [theme, setTheme] = useState(null);

    useEffect(() => {
        import('react-data-table-component').then((module) => {
            const { default: DataTable, createTheme } = module;
            setDataTable(() => DataTable);
            setTheme(() => createTheme('really-simple-plugins', {
                divider: {
                    default: 'transparent',
                },
            }, 'light'));
        });
    }, []);

    const handlePageChange = (page) => {
        setCurrentPage(page);
    };
    const handlePerRowsChange = (newRowsPerPage) => {
        setRowsPerPage(newRowsPerPage);
    };

    const {
        getCurrentFilter,
    } = FilterData();

    const [filter, setFilter] = useState(getCurrentFilter(moduleName));

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
    }), [filter]);
    let field = props.field;
    const columns = field.columns.map(buildColumn);

    const searchableColumns = columns
        .filter(column => column.searchable)
        .map(column => column.column);

    useEffect(() => {
        setRowsSelected([]);
    }, [BlockListData]);

    let enabled = getFieldValue('enable_firewall');

    useEffect(() => {
        const currentFilter = getCurrentFilter(moduleName);
        if (typeof currentFilter === 'undefined') {
            setFilter('all');
        } else {
            setFilter(currentFilter);
        }
        setRowsSelected([]);
        // resetRowSelection(true);
        // resetRowSelection(false);
    }, [getCurrentFilter(moduleName)]);

    useEffect(() => {
        return () => {
            saveFields(false, false)
        };
    }, [enabled]);

    useEffect(() => {
        if (typeof filter !== 'undefined') {
            fetchData(field.action, filter);
        }
    }, [filter, WhiteListTable]);

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
            // if the filter is still undefined we set it to all
            fetchData(field.action, filter ? filter : 'all');
            setRowsSelected([]);
        } else {
            resetRow(id).then((result) => {
               showSavedSettingsNotice(result.message);
            });
            fetchData(field.action, filter ? filter : 'all');
        }
    }, [resetRow]);

    const data = {...BlockListData.data};

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
            disabled={processing_block}
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
    }, [moduleName, allowById]);



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
        let intervals = [];
        const filteredData = Object.entries(data)
            .filter(([_, dataItem]) => {
                return Object.values(dataItem).some(val => ((val ?? '').toString().toLowerCase().includes(searchTerm.toLowerCase())));
            })
            .map(([key, dataItem]) => {
                const newItem = { ...dataItem };
                newItem.action = generateActionButtons(newItem.id);
                newItem.flag = generateFlag(newItem.iso2_code, newItem.country_name);
                newItem.status = __(newItem.status = newItem.status.charAt(0).toUpperCase() + newItem.status.slice(1), 'really-simple-ssl');
                // if the newItem.time_left not is 0 we count down in seconds the value
                if (newItem.time_left > 0) {
                    const interval = setInterval(() => {
                        newItem.time_left--;
                    }, 1000);
                    intervals.push(interval);
                }
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
                status={'blocked'}
                filter={filter? filter : 'all'}
            >
            </TrustIpAddressModal>
            <div className="rsssl-container">
                {/*display the add button on left side*/}
                <AddButton
                    moduleName={moduleName}
                    handleOpen={handleOpen}
                    processing={processing_block}
                    allowedText={__("Block IP Address", "really-simple-ssl")}
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
            {DataTable &&
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
            />}
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

export default BlockListDatatable;