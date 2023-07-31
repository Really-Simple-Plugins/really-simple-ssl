import {__} from '@wordpress/i18n';
import React, {useEffect, useState} from 'react';
import DataTable, {createTheme} from "react-data-table-component";
import IpAddressDataTableStore from "./IpAddressDataTableStore";
import * as rsssl_api from "../../utils/api";
import {Button} from "@wordpress/components";

const IpAddressDatatable = (props) => {
    const {
        DynamicDataTable,
        dataLoaded,
        pagination,
        dataActions,
        handleTableRowsChange,
        fetchDynamicData,
        handleTableSort,
        handleTablePageChange,
        handleTableSearch
    } = IpAddressDataTableStore()


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
        if (!dataLoaded) {
            fetchDynamicData(field.action);
        }
    });

    useEffect(() => {
        if (dataActions) {
            fetchDynamicData(field.action);
        }
    }, [dataActions]);

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
    if (!dataLoaded && columns.length === 0 && DynamicDataTable.length === 0) {
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

    //and now we add the options as a dropdown select to the status column
    columns.map(column => {
        if (column.column === 'status') {
            column.cell = row => <select
                className="rsssl-select"
                value={row.status}
                onChange={event => handleStatusChange(event.target.value, row.id)}
            >
                {options.map(option => {
                    return <option key={option.value} value={option.value}>{option.label}</option>
                })}
            </select>
        }
    });

    return (
        <>
            <div className="rsssl-container">
                {/*display the add button on left side*/}
                <div className="rsssl-add-button">
                    <div className="rsssl-add-button__inner">
                        <Button
                            className="button button-secondary rsssl-add-button__button"
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
                            onChange={event => handleTableSearch(event.target.value, searchableColumns)}
                        />
                    </div>
                </div>
            </div>
            {/*Display the datatable*/}
            <DataTable
                columns={columns}
                data={DynamicDataTable.data}
                dense
                pagination
                paginationServer
                paginationTotalRows={pagination.totalRows}
                onChangeRowsPerPage={handleTableRowsChange}
                onChangePage={handleTablePageChange}
                sortServer
                onSort={handleTableSort}
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

