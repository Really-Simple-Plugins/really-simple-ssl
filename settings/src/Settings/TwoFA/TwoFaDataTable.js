import {__} from '@wordpress/i18n';
import React, {useRef, useEffect, useState} from 'react';
import DataTable, {createTheme} from "react-data-table-component";
import useFields from "../FieldsData";
import TwoFaDataTableStore from "./TwoFaDataTableStore";
import FilterData from "../FilterData";

const DynamicDataTable = (props) => {
    const {
        resetUserMethod,
        handleUsersTableFilter,
        handleRowsPerPageChange,
        totalRecords,
        DynamicDataTable,
        setDataLoaded,
        dataLoaded,
        pagination,
        dataActions,
        fetchDynamicData,
        handleTableSort,
        handlePageChange,
        handleTableSearch,
        processing
    } = TwoFaDataTableStore();

    const {
        setSelectedFilter,
        getCurrentFilter
    } = FilterData();

    const moduleName = 'rsssl-group-filter-two_fa_users';

    let field = props.field;
    const [enabled, setEnabled] = useState(false);
    const [reloadWhenSaved, setReloadWhenSaved] = useState(false);
    const {fields, getFieldValue, saveFields, changedFields} = useFields();
    const [rowsSelected, setRowsSelected] = useState([]);
    const [rowCleared, setRowCleared] = useState(false);
    const twoFAEnabledRef = useRef();

    useEffect(() => {
        twoFAEnabledRef.current = getFieldValue('two_fa_enabled');
        saveFields(true, false)
    }, [getFieldValue('two_fa_enabled')]);

    //we want to reload the table, but only after the save action has completed. So we store this for now.
    useEffect(() => {
        setReloadWhenSaved(true);
    }, [getFieldValue('two_fa_forced_roles'), getFieldValue('two_fa_optional_roles')]);

    //when the data is saved, changefields=0 again,
    useEffect(() => {
        if (!reloadWhenSaved) {
            return;
        }
        if (changedFields.length===0) {
            setDataLoaded(false);
            setReloadWhenSaved(false);
            fetchDynamicData();
        }
    }, [changedFields]);

    useEffect(() => {
        if (!dataLoaded) {
            return;
        }

        const currentFilter = getCurrentFilter(moduleName);
        if ( !currentFilter ) {
            setSelectedFilter('active', moduleName);
        }
        setRowCleared(true);
        handleUsersTableFilter('rsssl_two_fa_status', currentFilter);
    }, [getCurrentFilter(moduleName)]);

    useEffect(() => {
        const value = getFieldValue('two_fa_enabled');
        setEnabled(value);
    }, [fields]);

    useEffect(() => {
        if (!dataLoaded || enabled !== getFieldValue('two_fa_enabled')) {
            fetchDynamicData()
         }
    }, [dataLoaded, getFieldValue('two_fa_enabled')]); // Add getFieldValue('login_protection_enabled') as a dependency

    const allAreForced = (users) => {
        let forcedRoles = getFieldValue('two_fa_forced_roles');
        if (!Array.isArray(forcedRoles)) {
            forcedRoles = [];
        }

        if (Array.isArray(users)) {
            //for each users, check if the user has a forced role
            for (const user of users) {
                if ( !forcedRoles.includes(user.user_role.toLowerCase()) ) {
                    return false;
                }
            }
            return true;
        } else {
            return forcedRoles.includes(users.user_role.toLowerCase());
        }
    }

    /**
     * Check if the one, or all users have an open status
     * @param users
     * @returns {boolean}
     */
    const allAreOpen = (users) => {
        if (Array.isArray(users)) {
            //for each users, check if the user has a forced role
            for (const user of users) {
                if ( user.rsssl_two_fa_status !== 'open' ) {
                    return false;
                }
            }
            return true;
        } else {
            return users.rsssl_two_fa_status === 'open';
        }
    }

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

    async function handleReset(users) {
        // Function to handle reset logic
        const resetRoles = getFieldValue('two_fa_optional_roles');
        if (Array.isArray(users)) {
            //loop through all users one by one, and reset the user
            for (const user of users) {
                await resetUserMethod(user.id, resetRoles, user.user_role.toLowerCase());
            }
        } else {
            await resetUserMethod(users.id, resetRoles, users.user_role.toLowerCase());
        }

        await fetchDynamicData();
        setRowsSelected([]);
        setRowCleared(true);
    }

    function handleSelection(state) {
        setRowsSelected(state.selectedRows);
    }

    let resetDisabled = allAreForced(rowsSelected) || allAreOpen(rowsSelected);
    let displayData = [];
    let inputData= DynamicDataTable ? DynamicDataTable : [];
    inputData.forEach(user => {
        let recordCopy = {...user}
        //forced roles can't be reset if it's just the email method. An open status also can't be reset.
        let btnDisabled =  allAreForced(user) || allAreOpen(user);
        recordCopy.resetControl = <button disabled={processing || btnDisabled}
                                      className="button button-red rsssl-action-buttons__button"
                                      onClick={() => handleReset(user)}
                                    >
                                    {__("Reset", "really-simple-ssl")}
                                </button>
        displayData.push(recordCopy);
    });

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
                            {__("You have selected %s users", "really-simple-ssl").replace("%s", rowsSelected.length)}
                        </div>
                        <div className="rsssl-action-buttons">
                            <div className="rsssl-action-buttons__inner">
                                <button disabled={resetDisabled || processing}
                                    className="button button-red rsssl-action-buttons__button"
                                    onClick={() => handleReset(rowsSelected)}
                                >
                                    {__("Reset", "really-simple-ssl")}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {dataLoaded &&
                <DataTable
                    columns={columns}
                    data={displayData}
                    dense
                    pagination
                    paginationServer
                    onChangePage={handlePageChange}
                    onChangeRowsPerPage={handleRowsPerPageChange}
                    paginationTotalRows={totalRecords}
                    paginationRowsPerPageOptions={[5, 25, 50, 100]}
                    paginationPerPage={dataActions.currentRowsPerPage}
                    paginationState={pagination}
                    sortServer
                    onSort={handleTableSort}
                    noDataComponent={__("No results", "really-simple-ssl")}
                    persistTableHead
                    selectableRows
                    selectableRowsHighlight={true}
                    onSelectedRowsChange={handleSelection}
                    clearSelectedRows={rowCleared}
                    theme="really-simple-plugins"
                    customStyles={customStyles}
                ></DataTable>
            }
            {!enabled &&
                <div className="rsssl-locked">
                    <div className="rsssl-locked-overlay"><span
                        className="rsssl-task-status rsssl-open">{__('Disabled', 'really-simple-ssl')}</span><span>{__('Activate Two Step Verification to enable this block.', 'really-simple-ssl')}</span>
                    </div>
                </div>
            }
        </>
    );


}
export default DynamicDataTable;
