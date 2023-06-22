import {__} from '@wordpress/i18n';
import React, {useEffect, useState} from 'react';
import DataTable, {createTheme} from "react-data-table-component";
import useFields from "./FieldsData";

const TwoFaTable = (props) => {
    // @todo how to pass users
    const { users } = props;
    let columns = [];
    const {fields, fieldAlreadyEnabled, getFieldValue} = useFields();
    let field = props.field;

    function buildColumn(column) {
        return {
            name: column.name,
            sortable: column.sortable,
            width: column.width,
            visible: column.visible,
            selector: row => row[column.column],
        };
    }

    // let dummyData = [['','','','',''],['','','','',''],['','','','','']];
    field.columns.forEach(function (item, i) {
        let newItem = buildColumn(item)
        columns.push(newItem);
    });

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

    if (!users || users.length === 0) {
        return (
            <DataTable
                columns={columns}
                data={[]}
                dense
                pagination
                noDataComponent={__("No results", "really-simple-ssl")}
                persistTableHead
                theme="really-simple-plugins"
                customStyles={customStyles}
            />
        )
    }

    return (
        <DataTable
            columns={columns}
            data={users}
            dense
            pagination
            persistTableHead
            theme="really-simple-plugins"
            customStyles={customStyles}
        />
    )
}

export default TwoFaTable;
