import {__} from '@wordpress/i18n';
import {useEffect, useState} from '@wordpress/element';
import DataTable, {createTheme} from "react-data-table-component";
import useFields from "../FieldsData";
import TwoFaDataTableStore from "./TwoFaDataTableStore";
import FilterData from "../FilterData";

const DynamicDataTable = (props) => {
    const {
        resetUserMethod,
        hardResetUser,
        handleUsersTableFilter,
        totalRecords,
        DynamicDataTable,
        setDataLoaded,
        dataLoaded,
        fetchDynamicData,
        handleTableSort,
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
    const {fields, getFieldValue, changedFields} = useFields();
    const [rowsSelected, setRowsSelected] = useState([]);
    const [rowCleared, setRowCleared] = useState(false);
    const [data, setData] = useState([]);
    const [currentPage, setCurrentPage] = useState(1);
    const [rowsPerPage, setRowsPerPage] = useState(5);

    useEffect(() => {
        if (!dataLoaded) {
            fetchDynamicData();
        } else {
            setData(DynamicDataTable);
        }
    }, [dataLoaded, DynamicDataTable]);

    useEffect(() => {
        setReloadWhenSaved(true);
        setDataLoaded(false);
    }, [getFieldValue('two_fa_forced_roles'), getFieldValue('two_fa_optional_roles'), getFieldValue('two_fa_forced_roles_totp'), getFieldValue('two_fa_optional_roles_totp')]);

    useEffect(() => {
        if (reloadWhenSaved) {
            if (changedFields.length === 0) {
                setDataLoaded(false);
                setReloadWhenSaved(false);
            }
        }
    }, [reloadWhenSaved]);

    const handleTableSearch = (value, columns) => {
        const search = value.toLowerCase();
        const searchColumns = columns;
        const filteredData = DynamicDataTable.filter((item) => {
            return searchColumns.some((column) => {
                return item[column].toString().toLowerCase().includes(search);
            });
        });
        setData(filteredData);
    }

    useEffect(() => {
        if (dataLoaded) {
            const currentFilter = getCurrentFilter(moduleName);
            if (!currentFilter) {
                setSelectedFilter('all', moduleName);
            }
            setRowCleared(true);
            handleUsersTableFilter('rsssl_two_fa_status', currentFilter);
        }
    }, [getCurrentFilter(moduleName)]);

    useEffect(() => {
        let enabledEmailRoles = getFieldValue('two_fa_enabled_roles_email');
        let enabledTotpRoles = getFieldValue('two_fa_enabled_roles_totp');
        let enabledRoles = enabledEmailRoles.concat(enabledTotpRoles);
        setEnabled(getFieldValue('login_protection_enabled'));
    }, [fields]);

    useEffect(() => {
        if (!dataLoaded || enabled !== (getFieldValue('two_fa_enabled_email') || getFieldValue('two_fa_enabled_totp'))) {
            setDataLoaded(false);
        }
    }, [getFieldValue('two_fa_enabled'), getFieldValue('two_fa_enabled_totp')]);

    const allAreForced = (users) => {
        let forcedRoles = getFieldValue('two_fa_forced_roles');
        let forcedRolesTotp = getFieldValue('two_fa_forced_roles_totp');
        if (!Array.isArray(forcedRoles)) {
            forcedRoles = [];
        }
        if (!Array.isArray(forcedRolesTotp)) {
            forcedRolesTotp = [];
        }
        if (Array.isArray(users)) {
            for (const user of users) {
                if (user.user_role === undefined) {
                    return true;
                }
                if (user.rsssl_two_fa_providers.toLowerCase() === 'none') {
                    return true;
                }
                if (user.status_for_user.toLowerCase() === 'active' || user.status_for_user.toLowerCase() === 'disabled' || user.status_for_user.toLowerCase() === 'expired') {
                    return false;
                }
                if (!forcedRoles.includes(user.user_role.toLowerCase()) && !forcedRolesTotp.includes(user.user_role.toLowerCase())) {
                    return false;
                }
            }
            return true;
        } else {
            if (users.user_role === undefined) {
                return true;
            }
            if (users.status_for_user.toLowerCase() === 'active' || users.status_for_user.toLowerCase() === 'disabled' || users.status_for_user.toLowerCase() === 'expired') {
                return false;
            }
            return (forcedRoles.includes(users.user_role.toLowerCase()) || forcedRolesTotp.includes(users.user_role.toLowerCase()));
        }
    }

    const allAreOpen = (users) => {
        if (Array.isArray(users)) {
            for (const user of users) {
                if (user.status_for_user.toLowerCase() !== 'open') {
                    return false;
                }
            }
            return true;
        } else {
            return users.status_for_user.toLowerCase() === 'open';
        }
    }

    const buildColumn = (column) => {
        return {
            name: column.name,
            column: column.column,
            sortable: column.sortable,
            searchable: column.searchable,
            width: column.width,
            visible: column.visible,
            selector: row => row[column.column],
        };
    }

    let columns = [];

    field.columns.forEach(function (item, i) {
        let newItem = { ...item, key: item.column };
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

    const handleReset = async (users) => {
        let resetRolesEmail = getFieldValue('two_fa_forced_roles_email');
        let resetRolesTotp = getFieldValue('two_fa_forced_roles_totp');
        resetRolesEmail = Array.isArray(resetRolesEmail) ? resetRolesEmail : [resetRolesEmail];
        resetRolesTotp = Array.isArray(resetRolesTotp) ? resetRolesTotp : [resetRolesTotp];

        const resetRoles = resetRolesEmail.concat(resetRolesTotp);

        if (Array.isArray(users)) {
            for (const user of users) {
                await hardResetUser(user.id, resetRoles, user.user_role.toLowerCase());
            }
        } else {
            await hardResetUser(users.id, resetRoles, users.user_role.toLowerCase());
        }

        setDataLoaded(false);
        setRowsSelected([]);
        setRowCleared(true);
    }

    const handleSelection = (state) => {
        setRowsSelected(state.selectedRows);
    }

    const capitalizeFirstLetter = (string) => {
        //if the string is totp we capitlize it
        if (string === 'totp') {
            return string.toUpperCase();
        }
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    let resetDisabled = allAreForced(rowsSelected) || allAreOpen(rowsSelected);
    let inputData = data ? data : [];
    const paginatedData = inputData.slice((currentPage - 1) * rowsPerPage, currentPage * rowsPerPage);
    let displayData = [];
    paginatedData.forEach(user => {
        let recordCopy = { ...user }
        recordCopy.user = capitalizeFirstLetter(user.user);
        recordCopy.user_role = capitalizeFirstLetter(user.user_role);
        recordCopy.status_for_user = __(capitalizeFirstLetter(user.status_for_user), 'really-simple-ssl');
        recordCopy.rsssl_two_fa_providers = __(capitalizeFirstLetter(user.rsssl_two_fa_providers), 'really-simple-ssl');
        let btnDisabled = allAreForced(user) || allAreOpen(user);
        recordCopy.resetControl = <button disabled={btnDisabled}
                                          className="button button-red rsssl-action-buttons__button"
                                          onClick={() => handleReset(user)}
        >
            {__("Reset", "really-simple-ssl")}
        </button>
        displayData.push(recordCopy);
    });
    const CustomLoader = () => (
        <div className="custom-loader">
            <div className="dot"></div>
            <div className="dot"></div>
            <div className="dot"></div>
        </div>
    );

    return (
        <>
            <div className="rsssl-container" style={{ marginTop: "20px" }}>
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
                <div style={{ marginTop: '1em', marginBottom: '1em' }}>
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

            <DataTable
                    columns={columns}
                    data={displayData}
                    dense
                    pagination
                    paginationServer={true}
                    onChangePage={page => {
                        setCurrentPage(page);
                    }}
                    onChangeRowsPerPage={rows => {
                        setRowsPerPage(rows);
                        setCurrentPage(1);
                    }}
                    paginationTotalRows={data.length}
                    paginationRowsPerPageOptions={[5, 25, 50, 100]}
                    paginationPerPage={rowsPerPage}
                    progressPending={processing} // Show loading indicator
                    progressComponent={<CustomLoader />}
                    onSort={handleTableSort}
                    noDataComponent={__("No results", "really-simple-ssl")}
                    persistTableHead
                    selectableRows
                    selectableRowsHighlight={true}
                    onSelectedRowsChange={handleSelection}
                    clearSelectedRows={rowCleared}
                    theme="really-simple-plugins"
                    customStyles={customStyles}
                />
            {!enabled &&
                <div className="rsssl-locked">
                    <div className="rsssl-locked-overlay">
                        <span className="rsssl-task-status rsssl-open">{__('Disabled', 'really-simple-ssl')}</span>
                        <span>{__('Activate Two-Factor Authentication and one method to enable this block.', 'really-simple-ssl')}</span>
                    </div>
                </div>
            }
        </>
    );
}
export default DynamicDataTable;