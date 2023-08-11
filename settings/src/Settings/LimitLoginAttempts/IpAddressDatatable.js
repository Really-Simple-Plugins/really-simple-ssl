import {__} from '@wordpress/i18n';
import React, {useEffect, useRef, useState} from 'react';
import DataTable, {createTheme} from "react-data-table-component";
import IpAddressDataTableStore from "./IpAddressDataTableStore";
import FilterData from "../FilterData";
import {Button} from "@wordpress/components";
import {produce} from "immer";
import Flag from "../../utils/Flag/Flag";
import Icon from "../../utils/Icon";
import Cidr from "./Cidr";

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
    const [calculateCidr, setCalculateCidr] = useState(false);

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
    let data = Object.values({...IpDataTable.data});


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
        // Set both states to false
        setAddingIpAddress(false);
        setCalculateCidr(false);
    }

    function handleCancelCidr() {
        setCalculateCidr(false);
    }

    function handleSubmit(newIp) {
        // Validate and add the new IP address here
        // ...

        // Reset the state
        setAddingIpAddress(false);
        setCalculateCidr(false);
        // we check if statusSelected is not empty
        if (statusSelected !== '') {
            //we add the row
            addRow(newIp, statusSelected);
        }
    }

// Observe changes to addingIpAddress and calculateCidr
    useEffect(() => {
        // This code will run after addingIpAddress or calculateCidr is updated
        if (!addingIpAddress && !calculateCidr) {
            let data = Object.values({...IpDataTable.data});
        }
        // You can also handle other logic here that depends on the updated values
    }, [addingIpAddress, calculateCidr]);

    useEffect(() => {
        if (canSetCidr) {
            setIpAddress(cidr);
        }
    }, [canSetCidr])




    if (addingIpAddress) {
        data.unshift({
            attempt_value:
                <div>
                    <input
                        id={'ipAddress'}
                        type="text"
                        placeholder="Enter IP Address"
                        className={'rsssl-input'}
                        value={ipAddress}
                        onChange={(event) => setIpAddress(event.target.value)}
                        // ... other attributes here ...
                    /><br></br>
                    <a className={'button button-small button-secondary right'}
                       onClick={() => setCalculateCidr(true)}
                    >advanced</a>
                </div>,

            status: generateOptions(statusSelected, 'new'),
            // datetime: <Cidr/>,
            api: <div>
                <button onClick={handleCancel} className={'button button-small button-secondary'}>Cancel</button><br/>
                <button className={'button button-small button-primary'}
                 onClick={() => handleSubmit(ipAddress)}
                >Save</button>
            </div>,
        });
    }

// When calculating CIDR
    if (calculateCidr) {
        data.splice(1, 0, {
            attempt_value: <Cidr></Cidr>,
            status: <button
                className={'button button-primary'}
                onClick={() => fetchCidrData('get_mask_from_range')}
                disabled={!inputRangeValidated}
            >Validate Range</button>,
            api: <button className={'button button-small button-secondary'}
            onClick={handleCancelCidr}
            >Cancel</button>,
            datetime: <div className={'left'}>
                <strong>{__("CIDR Notation", "really-simple-ssl")}</strong><br></br>
                <span>{cidr}</span><br/>
                <hr/>
                <strong>{__("IP Count", "really-simple-ssl")}</strong><br></br>
                <span>{ip_count}</span>
            </div>,
        });
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

