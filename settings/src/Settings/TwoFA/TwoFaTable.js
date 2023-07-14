import { __ } from '@wordpress/i18n';
import React, { useEffect, useState } from 'react';
import DataTable, { createTheme } from 'react-data-table-component';
import FilterComponent from 'react-data-table-component';
import apiFetch from '@wordpress/api-fetch'; // If you're fetching data from the WordPress API

const TwoFaTable = (props) => {
    // Initialize the state for the users data
    const [users, setUsers] = useState([]);
    let field = props.field;
    const [twoFAMethods, setTwoFAMethods] = useState({});
    const [filterText, setFilterText] = React.useState('');
    const [resetPaginationToggle, setResetPaginationToggle] = React.useState(false);
    const [filteredItems, setFilteredItems] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        if (filterText) {
            setFilteredItems(
                users.filter(
                    item => item.user && item.user.toLowerCase().includes(filterText.toLowerCase()),
                )
            );
        } else {
            setFilteredItems(users);
        }
    }, [users, filterText]);

    useEffect(() => {
        apiFetch({ path: '/wp/v2/users?context=edit&_fields=id,name,roles,meta' })
            .then((data) => {
                const formattedData = data.map(user => ({
                    id: user.id,
                    user: user.name,
                    rsssl_two_fa_method: user.meta.rsssl_two_fa_method || 'disabled',
                    user_role: user.roles[0]
                }));

                const initialTwoFAMethods = data.reduce((methods, user) => {
                    methods[user.id] = user.meta.rsssl_two_fa_method || 'disabled';
                    return methods;
                }, {});
                setTwoFAMethods(initialTwoFAMethods);

                setUsers(formattedData);

                setLoading(false);

            })
            .catch((error) => {
                console.error('Error fetching users:', error);

                setLoading(false);
            });
    }, []);

    function handleTwoFAMethodChange(userId, newMethod) {
        setTwoFAMethods(prevMethods => ({
            ...prevMethods,
            [userId]: newMethod,
        }));

        // Find the user object
        const user = users.find(user => user.id === userId);

        // Update the user meta
        apiFetch({
            path: `/wp/v2/users/${user.id}`,
            method: 'POST',
            data: {
                meta: {
                    ...user.meta,
                    rsssl_two_fa_method: newMethod,
                },
            },
        })
        .then((response) => {
            // console.log('Reponse', response); // This will log the updated user object
        })
        .catch((error) => {
            console.error('Error updating user meta:', error);
        });
    }

    function buildColumn(column) {
        return {
            name: column.name,
            sortable: column.sortable,
            width: column.width,
            visible: column.visible,
            selector: row => row[column.key],
        };
    }

    let columns = [];

    field.columns.forEach(function (item, i) {
        let newItem = { ...item, key: item.column };
        newItem = buildColumn(newItem);
        newItem.visible = newItem.visible ?? true; // If `visible` is undefined, set it to true

        if (newItem.name === '2FA') {
            newItem.cell = row => (
                <select
                    value={twoFAMethods[row.id] || 'disabled'}
                    onChange={event => handleTwoFAMethodChange(row.id, event.target.value)}
                >
                    <option value="disabled">Disabled</option>
                    <option value="email">Email</option>
                </select>
            );
        }

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

    const subHeaderComponentMemo = React.useMemo(() => {
        const handleClear = () => {
            if (filterText) {
                setResetPaginationToggle(!resetPaginationToggle);
                setFilterText('');
            }
        };

        return (
            <FilterComponent onFilter={e => setFilterText(e.target.value)} onClear={handleClear} filterText={filterText} />
        );
    }, [filterText, resetPaginationToggle]);

    createTheme('really-simple-plugins', {
        divider: {
            default: 'transparent',
        },
    }, 'light');

    if (loading) {
        return <div>Loading...</div>;
    } else {
        return (
            <DataTable
                columns={columns}
                data={filteredItems}
                subHeader
                subHeaderComponent={subHeaderComponentMemo}
                dense
                pagination
                noDataComponent={__("No results", "really-simple-ssl")}
                persistTableHead
                theme="really-simple-plugins"
                customStyles={customStyles}
            />
        );
    }

};

export default TwoFaTable;
