import {__} from '@wordpress/i18n';
import {useEffect, useState, useRef} from '@wordpress/element';
import DataTable, {createTheme} from "react-data-table-component";
import useFields from "../FieldsData";
import TwoFaDataTableStore from "./TwoFaDataTableStore";
import FilterData from "../FilterData";
import RolesStore from "./RolesStore";

const DynamicDataTable = (props) => {
    const {
        hardResetUser,
        handleUsersTableFilter,
        DynamicDataTable,
        setDataLoaded,
        dataLoaded,
        fetchDynamicData,
        handleTableSort,
        processing
    } = TwoFaDataTableStore();

    const {
        roles,
        fetchRoles,
    } = RolesStore()

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
    const selectRef = useRef(null);
    const hiddenSelectRef = useRef(null);
    const [user_role, setUserRole] = useState('all');

    useEffect(() => {
        if (!dataLoaded) {
            fetchDynamicData(user_role);
        } else {
            setData(DynamicDataTable);
        }
    }, [dataLoaded, DynamicDataTable]);

    useEffect(() => {
        if (user_role !== 'all') {
            fetchDynamicData(user_role);
        }
    }, [user_role]);

    useEffect(() => {
        if (!roles.length) {
            fetchRoles();
        }
    }, [roles, fetchRoles]);

    useEffect(() => {
        if (hiddenSelectRef.current && selectRef.current) {
            const hiddenSelectWidth = hiddenSelectRef.current.offsetWidth;
            selectRef.current.style.width = `${hiddenSelectWidth}px`;
        }
    }, [roles]);

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
            handleUsersTableFilter('user_role', currentFilter);
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

    const usersCanBeReset = (rows) => {
        // count the selected users
        let rowsSelectedCount = rows.length;
        // count the users that can be reset
        let canResetCount = 0;
        rows.forEach(user => {
            if (user.can_reset) {
                canResetCount++;
            }
        })
        // if the selected users are equal to the users that can be reset, return true
        return rowsSelectedCount === canResetCount;
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

    const handleReset = async (users) => {
        if (Array.isArray(users)) {
            for (const user of users) {
                await hardResetUser(user.ID);
            }
        } else {
            await hardResetUser(users.ID);
        }

        // Clear selection
        setDataLoaded(false);
        setRowsSelected([]);
        // Toggle clearSelectedRows: set it to true then back to false
        setRowCleared(true);

        // Use a short timeout (or an effect) to reset rowCleared to false
        setTimeout(() => {
            setRowCleared(false);
        }, 0);
    };

    const handleSelection = (state) => {
        setRowsSelected(state.selectedRows);
    }

    const capitalizeFirstLetter = (string) => {

        //if the string is totp we capitlize it
        if (string === 'totp') {
            return string.toUpperCase();
        }
        // if the string turns out to be an array we capitalize the first letter of each element
        if (Array.isArray(string)) {
            return string.map((str) => {
                return str.charAt(0).toUpperCase() + str.slice(1);
            }).join(', ');
        }
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    let inputData = data ? [...new Set(data)] : [];

    const paginatedData = inputData.slice((currentPage - 1) * rowsPerPage, currentPage * rowsPerPage);
    let displayData = [];
    paginatedData.forEach(user => {
        let recordCopy = {...user}
        recordCopy.user = capitalizeFirstLetter(user.user);
        recordCopy.status_for_user = __(capitalizeFirstLetter(user.status_for_user), 'really-simple-ssl');
        recordCopy.rsssl_two_fa_providers = __(capitalizeFirstLetter(user.rsssl_two_fa_providers), 'really-simple-ssl');
        recordCopy.resetControl = <button disabled={recordCopy.can_reset === false}
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
            <div className="rsssl-container" style={{marginTop: '1em'}}>
                <div>
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
                <div style={{marginTop: '1em', marginBottom: '1em'}}>
                    <div className={"rsssl-multiselect-datatable-form rsssl-primary"}>
                        <div>
                            {__("You have selected %s users", "really-simple-ssl").replace("%s", rowsSelected.length)}
                        </div>
                        <div className="rsssl-action-buttons">
                            <div className="rsssl-action-buttons__inner">
                                <button disabled={usersCanBeReset(rowsSelected) === false}
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
                keyField={'ID'}
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
                paginationTotalRows={inputData.length}
                paginationRowsPerPageOptions={[5, 25, 50, 100]}
                paginationPerPage={rowsPerPage}
                progressPending={processing} // Show loading indicator
                progressComponent={<CustomLoader/>}
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