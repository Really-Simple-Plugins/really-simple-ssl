import {__} from '@wordpress/i18n';
import React, {useEffect, useRef, useState} from 'react';
import DataTable, {createTheme} from "react-data-table-component";
import UserDataTableStore from "./UserDataTableStore";
import FilterData from "../FilterData";

import {button} from "@wordpress/components";
import {produce} from "immer";
import AddIpAddressModal from "./AddIpAddressModal";
import AddUserModal from "./AddUserModal";
import EventLogDataTableStore from "../EventLog/EventLogDataTableStore";
import useFields from "../FieldsData";

const UserDatatable = (props) => {
    const {
        UserDataTable,
        dataLoaded,
        pagination,
        resetRow,
        resetMultiRow,
        dataActions,
        handleUserTableRowsChange,
        fetchUserData,
        handleUserTableSort,
        handleUserTablePageChange,
        handleUserTableSearch,
        handleUserTableFilter,
        updateMultiRow,
        updateRow,
    } = UserDataTableStore()

    //here we set the selectedFilter from the Settings group
    const {selectedFilter, setSelectedFilter, activeGroupId, getCurrentFilter} = FilterData();
    const [rowsSelected, setRowsSelected] = useState([]);
    const [addingUser, setAddingUser] = useState(false);
    const [user, setUser] = useState('');
    const [rowCleared, setRowCleared] = useState(false);
    const {fetchDynamicData} = EventLogDataTableStore();
    const moduleName = 'rsssl-group-filter-limit_login_attempts_users';
    const {fields, fieldAlreadyEnabled, getFieldValue} = useFields();

    //we create the columns
    let columns = [];
    //getting the fields from the props
    let field = props.field;
    //we loop through the fields
    field.columns.forEach(function (item, i) {
        let newItem = buildColumn(item)
        columns.push(newItem);
    });

    //get data if field was already enabled, so not changed right now.
    useEffect(() => {
        if (fieldAlreadyEnabled) {
            if (!dataLoaded) {
                fetchUserData(field.action);
            }
        }
    }, [fields]);

    useEffect(() => {
        const currentFilter = getCurrentFilter(moduleName);

        if (!currentFilter) {
            setSelectedFilter('locked', moduleName);
        }
        handleUserTableFilter('status', currentFilter);
    }, [selectedFilter, moduleName]);

    let enabled = false;

    fields.forEach(function (item, i) {
        if (item.id === 'enable_limited_login_attempts') {
            enabled = item.value;
        }
    });

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

    //only show the datatable if the data is loaded
    if (!dataLoaded && columns.length === 0 && UserDataTable.length === 0) {
        return (
            <div className="rsssl-spinner">
                <div className="rsssl-spinner__inner">
                    <div className="rsssl-spinner__icon"></div>
                    <div className="rsssl-spinner__text">{__("Loading...", "really-simple-ssl")}</div>
                </div>
            </div>
        );
    }

    let searchableColumns = [];
    //setting the searchable columns
    columns.map(column => {
        if (column.searchable) {
            searchableColumns.push(column.column);
        }
    });


    const handleOpen = () => {
        setAddingUser(true);
    };

    const handleClose = () => {
        setAddingUser(false);
    };

    //now we get the options for the select control
    let options = props.field.options;
    //we divide the key into label and the value into value
    options = Object.entries(options).map((item) => {
        return {label: item[1], value: item[0]};
    });

    function handleStatusChange(value, id) {

    }

    function blockUsers(data) {
        //we check if the data is an array
        if (Array.isArray(data)) {
            let ids = [];
            data.map((item) => {
                ids.push(item.id);
            });
            updateMultiRow(ids, 'blocked');
            //we emtry the rowsSelected
            setRowsSelected([]);
        } else {
            updateRow(data, 'blocked');
        }
        fetchDynamicData('event_log')
    }

    function allowUsers(data) {
        //we check if the data is an array
        if (Array.isArray(data)) {
            let ids = [];
            data.map((item) => {
                ids.push(item.id);
            });
            updateMultiRow(ids, 'allowed');
            //we emtry the rowsSelected
            setRowsSelected([]);
        } else {
            updateRow(data, 'allowed');
        }
        fetchDynamicData('event_log')
    }

    function resetUsers(data) {
        //we check if the data is an array
        if (Array.isArray(data)) {
            let ids = [];
            data.map((item) => {
                ids.push(item.id);
            });
            resetMultiRow(ids);
            //we emtry the rowsSelected
            setRowsSelected([]);
        } else {
            resetRow(data);
        }
        fetchDynamicData('event_log');
    }
    function handleSelection(state) {
        setRowsSelected(state.selectedRows);
    }

    function generateActionbuttons(id) {
        return (
            <>
                <div className="rsssl-action-buttons">
                    {/* if the id is new we show the Allow button */}
                    {getCurrentFilter(moduleName) === 'blocked' && (
                    <div className="rsssl-action-buttons__inner">
                        <button
                            className="button button-secondary rsssl-action-buttons__button"
                            onClick={() => {
                                allowUsers(id);
                            }}
                        >
                            {__("Allow", "really-simple-ssl")}
                        </button>
                    </div>
                    )}
                    {/* if the id is new we show the Block button */}
                    {getCurrentFilter(moduleName) === 'allowed' && (
                    <div className="rsssl-action-buttons__inner">
                        <button
                            className="button button-primary rsssl-action-buttons__button"
                            onClick={() => {
                                blockUsers(id);
                            }}
                        >
                            {__("Block", "really-simple-ssl")}
                        </button>
                    </div>
                    )}
                    {/* if the id is new we show the Reset button */}
                    <div className="rsssl-action-buttons__inner">
                        <button
                            className="button button-red rsssl-action-buttons__button"
                            onClick={() => {
                                resetUsers(id);
                            }}
                        >
                            {__("Reset", "really-simple-ssl")}
                        </button>
                    </div>
                </div>
            </>
        );
    }

    //we convert the data to an array
    let data = {...UserDataTable.data};

    function generateOptions(status, id) {
        return (
            <select
                className="rsssl-select"
                value={status}
                onChange={(event) => handleStatusChange(event.target.value, id)}
            >
                {options.map((item, i) => {
                    let disabled = false;
                    if (item.value === 'locked') {
                        disabled = true;
                    }
                    return (
                        <option key={i} value={item.value} disabled={disabled}>
                            {item.label}
                        </option>
                    );
                })}
            </select>
        );
    }

    for (const key in data) {
        let dataItem = {...data[key]}
        //we add the action buttons
        dataItem.action = generateActionbuttons(dataItem.id);
        data[key] = dataItem;
    }

    return (
        <>
            <AddUserModal
                isOpen={addingUser}
                onRequestClose={handleClose}
                options={options}
                value={user}
                status={getCurrentFilter(moduleName)}
            >
            </AddUserModal>
            <div className="rsssl-container">
                {/*display the add button on left side*/}
                <div className="rsssl-add-button">
                    {(getCurrentFilter(moduleName) === 'blocked' || getCurrentFilter(moduleName) === 'allowed') && (
                    <div className="rsssl-add-button__inner">
                        <button
                            className="button button-secondary rsssl-add-button__button"
                            onClick={handleOpen}
                        >
                            {getCurrentFilter(moduleName) === 'blocked' && (
                                <>{__("Block User", "really-simple-ssl")}</>
                            )}
                            {getCurrentFilter(moduleName) === 'allowed' && (
                                <>{__("Allow User", "really-simple-ssl")}</>
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
                            onChange={event => handleUserTableSearch(event.target.value, searchableColumns)}
                        />
                    </div>
                </div>
            </div>
            { /*Display the action form what to do with the selected*/}
            {rowsSelected.length > 0 && (
                <div style={{
                    marginTop: '1em',
                    marginBottom: '1em',
                }}>
                    <div className={"rsssl-multiselect-datatable-form rsssl-primary"}
                    >
                        <div>
                            {__("You have selected", "really-simple-ssl")} {rowsSelected.length} {__("rows", "really-simple-ssl")}
                        </div>

                        <div className="rsssl-action-buttons">
                            {/* if the id is new we show the Allow button */}
                            {getCurrentFilter(moduleName) === 'blocked' && (
                            <div className="rsssl-action-buttons__inner">
                                <button
                                    className="button button-secondary rsssl-action-buttons__button"
                                    onClick={() => {
                                        allowUsers(rowsSelected);
                                    }}
                                >
                                    {__("Allow", "really-simple-ssl")}
                                </button>
                            </div>
                            )}
                            {/* if the id is new we show the Block button */}
                            {getCurrentFilter(moduleName) === 'allowed' && (
                            <div className="rsssl-action-buttons__inner">
                                <button
                                    className="button button-primary rsssl-action-buttons__button"
                                    onClick={() => {
                                        blockUsers(rowsSelected);
                                    }}
                                >
                                    {__("Block", "really-simple-ssl")}
                                </button>
                            </div>
                            )}
                            {/* if the id is new we show the Reset button */}
                            <div className="rsssl-action-buttons__inner">
                                <button
                                    className="button button-red rsssl-action-buttons__button"
                                    onClick={() => {
                                      resetUsers(rowsSelected);
                                    }
                                    }
                                >
                                    {__("Reset", "really-simple-ssl")}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
            {/*Display the datatable*/}
            <DataTable
                columns={columns}
                data={Object.values(data)}
                dense
                pagination
                paginationServer
                paginationTotalRows={pagination.totalRows}
                onChangeRowsPerPage={handleUserTableRowsChange}
                onChangePage={handleUserTablePageChange}
                sortServer
                onSort={handleUserTableSort}
                paginationRowsPerPageOptions={[10, 25, 50, 100]}
                selectableRows
                onSelectedRowsChange={handleSelection}
                clearSelectedRows={rowCleared}
                noDataComponent={__("No results", "really-simple-ssl")}
                persistTableHead
                theme="really-simple-plugins"
                customStyles={customStyles}
            ></DataTable>
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
export default UserDatatable;

function buildColumn(column) {
    return {
        name: column.name,
        sortable: column.sortable,
        searchable: column.searchable,
        width: column.width,
        visible: column.visible,
        column: column.column,
        selector: row => row[column.column],
    };
}

