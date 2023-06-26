import {__} from '@wordpress/i18n';
import React, {useEffect, useState} from 'react';
import DataTable, {createTheme} from "react-data-table-component";
import EventData from "./EventData";
import * as rsssl_api from "../../utils/api";

const EventViewer = (props) => {
    const {EventLog, dataLoaded, pagination, dataActions, handleTableRowsChange,fetchEventLog, handleTableSort, handleTablePageChange} = EventData()

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
            fetchEventLog();
        }
    });

    useEffect(() => {
        if (dataActions) {
            fetchEventLog();
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
    if (!dataLoaded && columns.length === 0 && EventLog.length === 0) {
        return (
            <div className="rsssl-spinner">
                <div className="rsssl-spinner__inner">
                    <div className="rsssl-spinner__icon"></div>
                    <div className="rsssl-spinner__text">{__("Loading...", "really-simple-ssl")}</div>
                </div>
            </div>
        );
    }



    return (
        <>
            {/*Display the datatable*/}
            <DataTable
                columns={columns}
                data={EventLog.data}
                dense
                pagination
                onChangePage={handleTablePageChange}
                paginationServer
                onSort={handleTableSort}
                sortServer
                paginationTotalRows={pagination.totalRows}
                onChangeRowsPerPage={handleTableRowsChange}
                noDataComponent={__("No results", "really-simple-ssl")}
                persistTableHead
                theme="really-simple-plugins"
                customStyles={customStyles}
            ></DataTable>
        </>
    );

}
export default EventViewer;

function buildColumn(column) {
    return {
        name: column.name,
        sortable: column.sortable,
        width: column.width,
        visible: column.visible,
        column: column.column,
        selector: row => row[column.column],
    };
}

