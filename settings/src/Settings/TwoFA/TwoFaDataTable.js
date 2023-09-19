import {__} from '@wordpress/i18n';
import React, {useRef, useEffect, useState} from 'react';
import DataTable, {createTheme} from "react-data-table-component";
import useFields from "../FieldsData";
import TwoFaDataTableStore from "./TwoFaDataTableStore";
import FilterData from "../FilterData";

const DynamicDataTable = (props) => {
    const {
        resetUserMethod,
        setTwoFAMethods,
        handleUsersTableFilter,
        handleRowsPerPageChange,
        totalRecords,
        DynamicDataTable,
        setDataLoaded,
        dataLoaded,
        pagination,
        dataActions,
        fetchDynamicData,
        // setDynamicData,
        handleTableSort,
        handlePageChange,
        handleTableSearch,
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
    const [reloadWhenSaved, setReloadWhenSaved] = useState(false);
    const {fields, getFieldValue, saveFields, changedFields} = useFields();
    const [rowsSelected, setRowsSelected] = useState([]);
    const [rowCleared, setRowCleared] = useState(false);
    const twoFAEnabledRef = useRef();

    useEffect(() => {
        twoFAEnabledRef.current = getFieldValue('login_protection_enabled');
        saveFields(true, false)
    }, [getFieldValue('login_protection_enabled')]);

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
        const currentFilter = getCurrentFilter(moduleName);
        if (!currentFilter) {
            setSelectedFilter('active', moduleName);
        }
        handleUsersTableFilter('rsssl_two_fa_status', currentFilter);
        setTimeout(() => {
            setRowCleared(true);
            setTimeout(() => setRowCleared(false), 100);
        }, 100);

    }, [selectedFilter, moduleName, handleUsersTableFilter, getCurrentFilter, setSelectedFilter]);

    useEffect(() => {
        const value = getFieldValue('login_protection_enabled');
        setEnabled(value);
    }, [fields]);

    useEffect(() => {
        if (!dataLoaded || enabled !== getFieldValue('login_protection_enabled')) {
            fetchDynamicData()
                .then(response => {
                    // Check if response.data is defined and is an array before calling reduce
                    let data = Array.isArray(response.data) ? response.data : [];
                    if (response.data && Array.isArray(response.data)) {
                        const methods = data.reduce((acc, user) => ({
                            ...acc,
                            [user.id]: user.rsssl_two_fa_status
                        }), {});
                        setTwoFAMethods(methods);
                    }
                })
                .catch(err => {
                    console.error(err); // Log any errors
                });
         }
    }, [dataLoaded, field.action, fetchDynamicData, getFieldValue('login_protection_enabled')]); // Add getFieldValue('login_protection_enabled') as a dependency


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

    function handleTwoFAMethodChange (userId, newMethod, reload = true) {
        // Function to handle reset logic
        const resetRoles = getFieldValue('two_fa_optional_roles');
        if (Array.isArray(userId)) {
            userId.map(async (user) => {
                await resetUserMethod(user.id, resetRoles);
                setRowsSelected([]);
                setRowCleared(true);
            });
        } else {
            resetUserMethod(userId, resetRoles);
        }
    }

    function handleSelection(state) {
        setRowCleared(false);
        setRowsSelected(state.selectedRows);
    }
   let resetDisabled =  rowsSelected.length===1 && rowsSelected[0].user_role==='Administrator';
    let displayData = DynamicDataTable || [];
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
                                <button disabled={resetDisabled}
                                    className="button button-red rsssl-action-buttons__button"
                                    onClick={() => handleTwoFAMethodChange(rowsSelected)}
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
                        className="rsssl-task-status rsssl-open">{__('Disabled', 'really-simple-ssl')}</span><span>{__('Activate login protection to enable this block.', 'really-simple-ssl')}</span>
                    </div>
                </div>
            }
        </>
    );


}
export default DynamicDataTable;
