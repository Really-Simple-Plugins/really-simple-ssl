import {__} from '@wordpress/i18n';
import React, {useEffect, useRef, useState} from 'react';
import DataTable, {createTheme} from "react-data-table-component";
import IpAddressDataTableStore from "./IpAddressDataTableStore";
import DynamicDataTableStore from "../EventLog/DynamicDataTableStore";
import FilterData from "../FilterData";
import {Button} from "@wordpress/components";
import {produce} from "immer";
import Flag from "../../utils/Flag/Flag";
import Icon from "../../utils/Icon";
import Cidr from "./Cidr";
import AddIpAddressModal from "./AddIpAddressModal";

const IpAddressDatatable = (props) => {
    const {
        IpDataTable,
        dataLoaded,
        pagination,
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
        resetRow,
        resetMultiRow,
        addRow,
        statusSelected,
        setIpAddress,
        setStatusSelected,
        fetchCidrData,
        canSetCidr,
        setIdSelected,
        idSelected,
        validateIpRange,
        inputRangeValidated,
        cidr,
        ip_count,
    } = IpAddressDataTableStore()

    //here we set the selectedFilter from the Settings group
    const {selectedFilter, setSelectedFilter, activeGroupId, getCurrentFilter} = FilterData();
    const [addingIpAddress, setAddingIpAddress] = useState(false);
    const [rowsSelected, setRowsSelected] = useState([]);
    const {fetchDynamicData} = DynamicDataTableStore();

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

    useEffect(() => {
        const currentFilter = getCurrentFilter(moduleName);

        if (!currentFilter) {
            setSelectedFilter('blocked', moduleName);
        }
        handleIpTableFilter('status', currentFilter);
    }, [selectedFilter, moduleName]);

    useEffect(() => {
        if (!dataLoaded) {
            fetchIpData(field.action);
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
        } else {
            updateRow(data, 'blocked');
        }
        fetchDynamicData('event_log')
    }

    function trustIpAddresses(data) {
        //we check if the data is an array
        if (Array.isArray(data)) {
            let ids = [];
            data.map((item) => {
                ids.push(item.id);
            });
            updateMultiRow(ids, 'trusted');
            //we emtry the rowsSelected
            setRowsSelected([]);
        } else {
            updateRow(data, 'trusted');
        }
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
        } else {
            resetRow(data);
        }
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

    function generateActionButtons(id) {
        return (
            <>
                <div className="rsssl-action-buttons">
                    {/* if the id is new we show the Trust button */}
                    <div className="rsssl-action-buttons__inner">
                        <Button
                            className="button button-secondary rsssl-action-buttons__button"
                            onClick={() => {
                                trustIpAddresses(id);
                            }}
                        >
                            {__("Trust", "really-simple-ssl")}
                        </Button>
                    </div>
                    {/* if the id is new we show the Block button */}
                    <div className="rsssl-action-buttons__inner">
                        <Button
                            className="button button-primary rsssl-action-buttons__button"
                            onClick={() => {
                               blockIpAddresses(id);
                            }}
                        >
                            {__("Block", "really-simple-ssl")}
                        </Button>
                    </div>
                    {/* if the id is new we show the Reset button */}
                    <div className="rsssl-action-buttons__inner">
                        <Button
                            className="button button-red rsssl-action-buttons__button"
                            onClick={() => {
                                resetIpAddresses(id);
                            }
                            }
                        >
                            {__("Reset", "really-simple-ssl")}
                        </Button>
                    </div>
                </div>
            </>
        );
    }

    for (const key in data) {
        let dataItem = {...data[key]}

        dataItem.action = generateActionButtons(dataItem.id);

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
                    {(getCurrentFilter(moduleName) === 'blocked' || getCurrentFilter(moduleName) === 'trusted') && (
                        <div className="rsssl-add-button__inner">
                            <Button
                                className="button button-secondary rsssl-add-button__button"
                                onClick={handleOpen}
                            >
                                {__("Add IP Address", "really-simple-ssl")}
                            </Button>
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
                            {/* if the id is new we show the Trust button */}
                            <div className="rsssl-action-buttons__inner">
                                <Button
                                    className="button button-secondary rsssl-action-buttons__button"
                                    onClick={() => {
                                        trustIpAddresses(rowsSelected);
                                    }}
                                >
                                    {__("Trust", "really-simple-ssl")}
                                </Button>
                            </div>
                            {/* if the id is new we show the Block button */}
                            <div className="rsssl-action-buttons__inner">
                                <Button
                                    className="button button-primary rsssl-action-buttons__button"
                                    onClick={() => {
                                        blockIpAddresses(rowsSelected);
                                    }}
                                >
                                    {__("Block", "really-simple-ssl")}
                                </Button>
                            </div>
                            {/* if the id is new we show the Reset button */}
                            <div className="rsssl-action-buttons__inner">
                                <Button
                                    className="button button-red rsssl-action-buttons__button"
                                    onClick={() => {
                                        resetIpAddresses(rowsSelected);
                                    }}
                                >
                                    {__("Reset", "really-simple-ssl")}
                                </Button>
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
                clearSelectedRows={rowsSelected.length <= 0}
                theme="really-simple-plugins"
                customStyles={customStyles}
            ></DataTable>
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

