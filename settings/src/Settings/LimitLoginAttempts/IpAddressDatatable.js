import {__} from '@wordpress/i18n';
import React, {useEffect, useRef, useState} from 'react';
import DataTable, {createTheme} from "react-data-table-component";
import IpAddressDataTableStore from "./IpAddressDataTableStore";
import FilterData from "../FilterData";
import {Button} from "@wordpress/components";
import {produce} from "immer";
import Flag from "../../Flag/Flag";
import Icon from "../../utils/Icon";
import CidrCalculator from "./CidrCalculator";

const IpAddressDatatable = (props) => {
    const {
        IpDataTable,
        dataLoaded,
        pagination,
        dataActions,
        handleIpTableRowsChange,
        fetchIpData,
        handleIpTableSort,
        handleIpTablePageChange,
        handleIpTableSearch,
        handleIpTableFilter,
        ipAddress,
        updateRow,
        statusSelected,
        setIpAddress,
        setStatusSelected,
        setIdSelected,
        idSelected,
    } = IpAddressDataTableStore()

    //here we set the selectedFilter from the Settings group
    const {selectedFilter, setSelectedFilter, activeGroupId, getCurrentFilter} = FilterData();
    const [addingIpAddress, setAddingIpAddress] = useState(false);

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
            setSelectedFilter('all', moduleName);
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
            console.log(value);
            //if the id is 'new' we set the statusSelected
            setStatusSelected(value);
        }
    }
    //we convert the data to an array
    let data = {...IpDataTable.data};


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

    useEffect(() => {

    },[])

    function generateGoodBad(value) {``
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

    for (const key in data) {
        let dataItem = {...data[key]}

        dataItem.status = generateOptions(dataItem.status, dataItem.id);
        dataItem.iso2_code = generateFlag('NL', 'Netherlands');
        dataItem.api = generateGoodBad(dataItem.api);

        data[key] = dataItem;
    }

    function handleAddClick() {
        setAddingIpAddress(true);
    }

    function handleCancel() {
        // Reset the state
        setAddingIpAddress(false);
        // Remove the temporary row
        delete data[0];
        // Restore the original data
        data[0] = data[0.5];
    }

    function handleSubmit(newIp) {
        // Validate and add the new IP address here
        // ...

        // Reset the state
        setAddingIpAddress(false);
    }

    if (addingIpAddress) {
        data[0.5] = data[0];

        data[0] = {
            // Your temporary row's data here, e.g.,
            attempt_value:
                <input
                    type="text"
                    placeholder="Enter IP Address"
                    value={ipAddress}
                    // ... other attributes here ...
                />,
            status: generateOptions(statusSelected, 'new'),
            iso2_code: <button onClick={handleCancel}>Cancel</button>,
            datetime: <CidrCalculator/>,
            api: <button>Save</button>,
        };
    }



    return (
        <>
            <div className="rsssl-container">
                {/*display the add button on left side*/}
                <div className="rsssl-add-button">
                    <div className="rsssl-add-button__inner">
                        <Button
                            className="button button-secondary rsssl-add-button__button"
                            onClick={handleAddClick}
                        >
                            {__("Add IP Address", "really-simple-ssl")}
                        </Button>
                    </div>
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
            {/*Display the datatable*/}
            <DataTable
                columns={columns}
                data={Object.values(data)}
                dense
                pagination
                paginationServer
                paginationTotalRows={pagination.totalRows}
                onChangeRowsPerPage={handleIpTableRowsChange}
                onChangePage={handleIpTablePageChange}
                sortServer
                onSort={handleIpTableSort}
                paginationRowsPerPageOptions={[10, 25, 50, 100]}
                noDataComponent={__("No results", "really-simple-ssl")}
                persistTableHead
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

