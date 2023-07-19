import {__} from '@wordpress/i18n';
import React, {useEffect, useState} from 'react';
import DataTable, {createTheme} from "react-data-table-component";
import apiFetch from '@wordpress/api-fetch';
import useFields from "../FieldsData";
import DynamicDataTableStore from "./DynamicDataTableStore";

const DynamicDataTable = (props) => {
    const {
        twoFAMethods,
        setTwoFAMethods,
        DynamicDataTable,
        dataLoaded,
        pagination,
        dataActions,
        handleTableRowsChange,
        fetchDynamicData,
        setDynamicData,
        handleTableSort,
        handleTablePageChange,
        handleTableSearch,
        updateUserMeta
    } = DynamicDataTableStore();

    let field = props.field;
    let enabled = false;
    const {fields} = useFields();

    useEffect(() => {
        if (!dataLoaded) {
            fetchDynamicData(field.action)
                .then(response => {
                    // Check if response.data is defined and is an array before calling reduce
                    if(response.data && Array.isArray(response.data)) {
                        const methods = response.data.reduce((acc, user) => ({...acc, [user.id]: user.rsssl_two_fa_method}), {});
                        setTwoFAMethods(methods);
                        setDynamicData(response.data);
                    } else {
                        console.error('Unexpected response:', response);
                    }
                })
                .catch(err => {
                    console.error(err); // Log any errors
                });
        }
    }, [dataLoaded, field.action, fetchDynamicData]);

    useEffect(() => {
        if (dataActions) {
            fetchDynamicData(field.action)
                .then(response => setDynamicData(response.data));
        }
    }, [dataActions, field.action, fetchDynamicData, setDynamicData]);

    function handleTwoFAMethodChange(userId, newMethod) {
        setTwoFAMethods({
            ...twoFAMethods,
            [userId]: newMethod
        });

        apiFetch({
            path: `/wp/v2/users/${userId}`,
            method: 'POST',
            data: {
                meta: {
                    rsssl_two_fa_method: newMethod,
                },
            },
        })
            .then((response) => {
                updateUserMeta(userId, newMethod);
            })
            .catch((error) => {
                console.error('Error updating user meta:', error);
            });
    }


    function buildColumn(column) {
        let newColumn = {
            name: column.name,
            sortable: column.sortable,
            searchable: column.searchable,
            width: column.width,
            visible: column.visible,
            selector: row => row[column.column],
        };

        if (newColumn.name === 'Status') {
            newColumn.cell = row => (
                <select
                    value={twoFAMethods[row.id] || 'open'}
                    onChange={event => handleTwoFAMethodChange(row.id, event.target.value)}
                >
                    <option value="open">Open</option>
                    <option value="disabled">Disabled</option>
                    <option value="email">Email</option>
                </select>
            );
        }

        return newColumn;
    }

    let columns = [];

    field.columns.forEach(function (item, i) {
        let newItem = { ...item, key: item.column };
        newItem = buildColumn(newItem);
        newItem.visible = newItem.visible ?? true;
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
    columns.map(column => {
        if (column.searchable) {
            searchableColumns.push(column.column);
        }
    });

    fields.forEach(function (item, i) {
        if (item.id === 'two_fa_enabled') {
            enabled = item.value;
        }
    });

    if ( ! enabled ) {
        return (
            <>
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
                <DataTable
                    columns={columns}
                    data={DynamicDataTable}
                    dense
                    pagination
                    paginationServer
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
                <div className="rsssl-locked">
                    <div className="rsssl-locked-overlay"><span
                        className="rsssl-task-status rsssl-open">{__('Disabled', 'really-simple-ssl')}</span><span>{__('Activate Enable login security to enable this block.', 'really-simple-ssl')}</span>
                    </div>
                </div>
            </>
        );
    }

    return (
        <>
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
            <DataTable
                columns={columns}
                data={DynamicDataTable}
                dense
                pagination
                paginationServer
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
export default DynamicDataTable;