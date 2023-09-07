import {__} from '@wordpress/i18n';
import React, {useRef, useEffect, useState} from 'react';
import DataTable, {createTheme} from "react-data-table-component";
import apiFetch from '@wordpress/api-fetch';
import useFields from "../FieldsData";
import TwoFaDataTableStore from "./TwoFaDataTableStore";
import {Button} from "@wordpress/components";
import FilterData from "../FilterData";

const DynamicDataTable = (props) => {
    const {
        twoFAMethods,
        setTwoFAMethods,
        handleUsersTableFilter,
        DynamicDataTable,
        dataLoaded,
        pagination,
        dataActions,
        handleTableRowsChange,
        fetchDynamicData,
        // setDynamicData,
        handleTableSort,
        handleTablePageChange,
        handleTableSearch,
        updateUserMeta,
    } = TwoFaDataTableStore();

    const {
        selectedFilter,
        setSelectedFilter,
        activeGroupId,
        getCurrentFilter
    } = FilterData();

    const moduleName = 'rsssl-group-filter-two_fa_users';

    let field = props.field;
    const [enabled, setEnabled] = useState(false);
    const {fields, getFieldValue, saveFields} = useFields();
    const [rowsSelected, setRowsSelected] = useState([]);
    const [rowCleared, setRowCleared] = useState(false);

    const twoFAEnabledRef = useRef();

    useEffect(() => {
        twoFAEnabledRef.current = getFieldValue('two_fa_enabled');
        saveFields(true, false)
    }, [getFieldValue('two_fa_enabled')]);

    useEffect(() => {
        const currentFilter = getCurrentFilter(moduleName);
        if (!currentFilter) {
            setSelectedFilter('email', moduleName);
        }
        handleUsersTableFilter('status_for_user', currentFilter);
        setTimeout(() => {
            setRowCleared(true);
            setTimeout(() => setRowCleared(false), 100);
        }, 100);

    }, [selectedFilter, moduleName, handleUsersTableFilter, getCurrentFilter, setSelectedFilter, DynamicDataTable]);

    useEffect(() => {
        const value = getFieldValue('two_fa_enabled');
        setEnabled(value);
    }, [fields]);

    useEffect(() => {
        if (!dataLoaded || enabled !== getFieldValue('two_fa_enabled')) {
            fetchDynamicData(field.action)
                .then(response => {
                    // Check if response.data is defined and is an array before calling reduce
                    if (response.data && Array.isArray(response.data)) {
                        const methods = response.data.reduce((acc, user) => ({
                            ...acc,
                            [user.id]: user.rsssl_two_fa_method
                        }), {});
                        setTwoFAMethods(methods);
                    } else {
                        console.error('Unexpected response:', response);
                    }
                })
                .catch(err => {
                    console.error(err); // Log any errors
                });
        }
    }, [dataLoaded, field.action, fetchDynamicData, getFieldValue('two_fa_enabled')]); // Add getFieldValue('two_fa_enabled') as a dependency


    useEffect(() => {
        if (dataActions) {
            fetchDynamicData(field.action);
        }
    }, [dataActions]);


    function buildColumn(column) {
        let newColumn = {
            name: column.name,
            column: column.column,
            sortable: column.sortable,
            searchable: column.searchable,
            width: column.width,
            visible: column.visible,
            selector: row => row[column.column],
        };

        if (newColumn.name === 'Action') {
            newColumn.cell = row => (
                <div className="rsssl-action-buttons">
                    <div className="rsssl-action-buttons__inner">
                        <button
                            className="button button-red rsssl-action-buttons__button"
                            onClick={() => handleTwoFAMethodChange(row.id, 'reset')}
                        >
                            {__("Reset", "really-simple-ssl")}
                        </button>
                    </div>
                </div>
            );
        }
        return newColumn;
    }

    let columns = [];

    field.columns.forEach(function (item, i) {
        let newItem = {...item, key: item.column};
        newItem = buildColumn(newItem);
        newItem.visible = newItem.visible ?? true;
        columns.push(newItem);
    });

    let searchableColumns = columns
        .filter(column => column.searchable)
        .map(column => column.column);

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

    function handleTwoFAMethodChange(userId, newMethod, reload = true) {

        // Function to handle reset logic
        const resetUserMethod = (id) => {
            return apiFetch({
                path: `/wp/v2/users/${id}`,
                method: 'GET',
            })
                .then(async (response) => {
                    const userRoles = response.roles;
                    const forcedRoles = getFieldValue('two_fa_forced_roles');
                    const optionalRoles = getFieldValue('two_fa_optional_roles');

                    // if any of userRoles is in forcedRoles, newMethod = 'email'
                    if (userRoles.some(role => forcedRoles.includes(role))) {
                        newMethod = 'email';
                    }
                    // if any of userRoles is in optionalRoles, newMethod = 'open'
                    else if (userRoles.some(role => optionalRoles.includes(role))) {
                        newMethod = 'open';
                    }
                    // if none of the roles match, you can set a default method or throw an error
                    else {
                        newMethod = 'disabled';
                    }

                    // Now, update the user's 2FA method
                    return apiFetch({
                        path: `/wp/v2/users/${id}`,
                        method: 'POST',
                        data: {
                            meta: {
                                rsssl_two_fa_method: newMethod,
                            },
                        },
                    });
                })
                .then(() => {
                    fetchDynamicData(field.action);
                })
                .catch((error) => {
                    console.error('Error:', error);
                });
        }

        if (Array.isArray(userId)) {
            const promises = userId.map((user) => {
                if (newMethod === 'reset') {
                    return resetUserMethod(user.id)
                        .then(() => {
                            setRowsSelected([]);
                            setRowCleared(true);
                        });
                } else {
                    return handleTwoFAMethodChange(user.id, newMethod, false)
                        .then(() => {
                            setRowsSelected([]);
                            setRowCleared(true);
                        });
                }
            });

            Promise.all(promises)
                .then(() => {
                    setRowsSelected([]);
                    setRowCleared(true);
                    fetchDynamicData(field.action);
                });

        } else {
            if (newMethod === 'reset') {
                resetUserMethod(userId);
            } else {
                setTwoFAMethods({
                    ...twoFAMethods,
                    [userId]: newMethod
                });

                return apiFetch({
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
                        if (reload) {
                            fetchDynamicData(field.action);
                        }
                    })
                    .catch((error) => {
                        console.error('Error updating user meta:', error);
                    });
            }
        }
    }

    function handleSelection(state) {
        setRowCleared(false);
        setRowsSelected(state.selectedRows);
    }

    return (
        <>
            <div className="rsssl-container" style={
                {
                    marginTop: "20px",
                }
            }>
                <div>
                    {/* Reserved for actions left */}
                </div>
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
            {rowsSelected.length > 0 && (
                <div
                    style={{
                        marginTop: '1em',
                        marginBottom: '1em',
                    }}
                >
                    <div className={"rsssl-multiselect-datatable-form rsssl-primary"}>
                        <div>
                            {__("You have selected", "really-simple-ssl")} {rowsSelected.length} {__("rows", "really-simple-ssl")}
                        </div>
                        <div className="rsssl-action-buttons">
                            <div className="rsssl-action-buttons__inner">
                                <button
                                    className="button button-red rsssl-action-buttons__button"
                                    onClick={() => handleTwoFAMethodChange(rowsSelected, 'reset')}
                                >
                                    {__("Reset", "really-simple-ssl")}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {dataLoaded ?
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
                    selectableRows
                    selectableRowsHighlight={true}
                    onSelectedRowsChange={handleSelection}
                    clearSelectedRows={rowCleared}
                    theme="really-simple-plugins"
                    customStyles={customStyles}
                ></DataTable>
                :
                <div className="rsssl-spinner" style={{
                    display: 'flex',
                    justifyContent: 'center',
                    alignItems: 'center',
                    marginTop: "100px"
                }}>
                    <div className="rsssl-spinner__inner">
                        <div className="rsssl-spinner__icon" style={{
                            border: '8px solid white',
                            borderTop: '8px solid #f4bf3e',
                            borderRadius: '50%',
                            width: '120px',
                            height: '120px',
                            animation: 'spin 2s linear infinite'
                        }}></div>
                        <div className="rsssl-spinner__text" style={{
                            position: 'absolute',
                            top: '50%',
                            left: '50%',
                            transform: 'translate(-50%, -50%)',
                        }}>{__("Loading data, please stand by...", "really-simple-ssl")}</div>
                    </div>
                </div>
            }
            {!enabled &&
                <div className="rsssl-locked">
                    <div className="rsssl-locked-overlay"><span
                        className="rsssl-task-status rsssl-open">{__('Disabled', 'really-simple-ssl')}</span><span>{__('Activate login protection to enable this block.', 'really-simple-ssl')}</span>
                    </div>
                </div>
            }
        </>
    );


}
export default DynamicDataTable;
