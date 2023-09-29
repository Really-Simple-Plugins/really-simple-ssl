import {__} from '@wordpress/i18n';
import React, {useEffect, useRef, useState} from 'react';
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
        handleIpTableRowsChange,
        updateMultiRow,
        fetchIpData,
        handleIpTableSort,
        handleIpTablePageChange,
        handleIpTableSearch,
        handleIpTableFilter,
        ipAddress,
        updateRow,
        resetRow,
        resetMultiRow,
        setStatusSelected,
    } = IpAddressDataTableStore()

    //here we set the selectedFilter from the Settings group
    const {selectedFilter, setSelectedFilter, activeGroupId, getCurrentFilter} = FilterData();
    const [addingIpAddress, setAddingIpAddress] = useState(false);
    const [rowsSelected, setRowsSelected] = useState([]);
    const [rowCleared, setRowCleared] = useState(false);
    const {fetchDynamicData} = EventLogDataTableStore();
    const {fields, fieldAlreadyEnabled, getFieldValue} = useFields();

    const moduleName = 'rsssl-group-filter-limit_login_attempts_ip_address';
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
                fetchIpData(field.action);
            }
        }
    }, [fields]);

    useEffect(() => {
        const currentFilter = getCurrentFilter(moduleName);

        if (!currentFilter) {
            setSelectedFilter('locked', moduleName);
        }
        handleIpTableFilter('status', currentFilter);
    }, [selectedFilter, moduleName]);


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
    if (!dataLoaded && columns.length === 0 && IpDataTable.length === 0) {
        return (
            <div className="rsssl-spinner">
                <div className="rsssl-spinner__inner">
                    <div className="rsssl-spinner__icon"></div>
                    <div className="rsssl-spinner__text">{__("Loading...", "really-simple-ssl")}</div>
                </div>
            </div>
        );
    }
    let enabled = false;

    fields.forEach(function (item, i) {
        if (item.id === 'enable_limited_login_attempts') {
            enabled = item.value;
        }
    });

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

    function blockIpAddresses(data) {
        //we check if the data is an array
        if (Array.isArray(data)) {
            let ids = [];
            data.map((item) => {
                ids.push(item.id);
            });
            updateMultiRow(ids, 'blocked');
            //we emtry the rowsSelected
            setRowsSelected([]);
            setRowCleared(true);
        } else {
            updateRow(data, 'blocked');
        }
        setRowCleared(false);
        fetchDynamicData('event_log')
    }

    function allowIpAddresses(data) {
        //we check if the data is an array
        if (Array.isArray(data)) {
            let ids = [];
            data.map((item) => {
                ids.push(item.id);
            });
            updateMultiRow(ids, 'allowed');
            //we entry the rowsSelected
            setRowsSelected([]);
            setRowCleared(true);
        } else {
            updateRow(data, 'allowed');
        }
        setRowCleared(false);
        fetchDynamicData('event_log')
    }

    function resetIpAddresses(data) {
        //we check if the data is an array
        if (Array.isArray(data)) {
            let ids = [];
            data.map((item) => {
                ids.push(item.id);
            });
            resetMultiRow(ids);
            //we emtry the rowsSelected
            setRowsSelected([]);
            setRowCleared(true);
        } else {
            resetRow(data);
        }
        setRowCleared(false);
        fetchDynamicData('event_log')
    }


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
                        <option key={i} value={item.value} disabled={disabled}>
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

    function generateGoodBad(value) {
        ``
        if (value > 0) {
            return (
                <Icon name="circle-check" color='green'/>
            )
        } else {
            return (
                <Icon name="circle-times" color='red'/>
            )
        }
    }

    function generateActionbuttons(id) {
        return (
            <>
                <div className="rsssl-action-buttons">
                    {/* if the id is new we show the Allow button */}
                    {getCurrentFilter(moduleName) === 'blocked' && (
                        <div className="rsssl-action-buttons__inner">
                            <button
                                className="button button-secondary button-datatable rsssl-action-buttons__button"
                                onClick={() => {
                                    allowIpAddresses(id);
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
                                className="button button-primary button-datatable rsssl-action-buttons__button"
                                onClick={() => {
                                    blockIpAddresses(id);
                                }}
                            >
                                {__("Block", "really-simple-ssl")}
                            </button>
                        </div>
                    )}
                    {/* if the id is new we show the Reset button */}
                    <div className="rsssl-action-buttons__inner">
                        <button
                            className="button button-red button-datatable rsssl-action-buttons__button"
                            onClick={() => {
                                resetIpAddresses(id);
                            }
                            }
                        >
                            {__("Reset", "really-simple-ssl")}
                        </button>
                    </div>
                </div>
            </>
        );
    }

    for (const key in data) {
        let dataItem = {...data[key]}

        dataItem.action = generateActionbuttons(dataItem.id);

        data[key] = dataItem;
    }

    function handleSelection(state) {
        setRowsSelected(state.selectedRows);
    }

    return (
        <>
            <AddIpAddressModal
                isOpen={addingIpAddress}
                onRequestClose={handleClose}
                options={options}
                value={ipAddress}
                status={getCurrentFilter(moduleName)}
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
                            >
                                {getCurrentFilter(moduleName) === 'blocked' && (
                                <>{__("Block IP Address", "really-simple-ssl")}</>
                                )}
                                {getCurrentFilter(moduleName) === 'allowed' && (
                                    <>{__("Allow IP Address", "really-simple-ssl")}</>
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
                            onChange={event => handleIpTableSearch(event.target.value, searchableColumns)}
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
                            {__("You have selected", "really-simple-ssl")} {rowsSelected.length} {__("rows", "really-simple-ssl")}
                        </div>

                        <div className="rsssl-action-buttons">
                            {/* if the id is new we show the Allow button */}
                            {getCurrentFilter(moduleName) === 'blocked' && (
                                <div className="rsssl-action-buttons__inner">
                                    <button
                                        className="button button-secondary button-datatable rsssl-action-buttons__button"
                                        onClick={() => {
                                            allowIpAddresses(rowsSelected);
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
                                        className="button button-primary button-datatable rsssl-action-buttons__button"
                                        onClick={() => {
                                            blockIpAddresses(rowsSelected);
                                        }}
                                    >
                                        {__("Block", "really-simple-ssl")}
                                    </button>
                                </div>
                            )}
                            {/* if the id is new we show the Reset button */}
                            <div className="rsssl-action-buttons__inner">
                                <button
                                    className="button button-red button-datatable rsssl-action-buttons__button"
                                    onClick={() => {
                                        resetIpAddresses(rowsSelected);
                                    }}
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
                data={data}
                dense
                pagination
                paginationServer
                paginationTotalRows={Object.values(data).length}
                onChangeRowsPerPage={handleIpTableRowsChange}
                onChangePage={handleIpTablePageChange}
                sortServer
                onSort={handleIpTableSort}
                paginationRowsPerPageOptions={[10, 25, 50, 100]}
                noDataComponent={__("No results", "really-simple-ssl")}
                persistTableHead
                selectableRows
                onSelectedRowsChange={handleSelection}
                clearSelectedRows={rowCleared}
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
export default IpAddressDatatable;

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

