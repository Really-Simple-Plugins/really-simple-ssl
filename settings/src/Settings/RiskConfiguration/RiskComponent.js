import React, {useEffect, useState} from 'react';
import UseRiskData from "./RiskData";
import {SelectControl} from "@wordpress/components";
import sleeper from "../../utils/sleeper";
import {dispatch} from '@wordpress/data';
import {__} from "@wordpress/i18n";
import useFields from "../FieldsData";

const RiskComponent = (props) => {
    //first we put the data in a state
    const {riskData, dataLoaded, fetchVulnerabilities, updateRiskData} = UseRiskData();
    const { fields, fieldAlreadyEnabled} = useFields();
    const [DataTable, setDataTable] = useState(null);
    useEffect( () => {
        import('react-data-table-component').then(({ default: DataTable, createTheme }) => {
            setDataTable(() => DataTable);
        });

    }, []);
    useEffect(() => {
        if ( fieldAlreadyEnabled('enable_vulnerability_scanner')) {
            if (!dataLoaded) {
                fetchVulnerabilities();
            }
        }
    }, [fields]);

    //we only proceed if the data is loaded
    if (!dataLoaded) {
        return null;
    }
    if (!DataTable) return null;

    //we create the columns
    let columns = [];
    //getting the fields from the props
    let field = props.field;
    //we loop through the fields
    field.columns.forEach(function (item, i) {
        let newItem = buildColumn(item)
        columns.push(newItem);
    });

    //now we get the options for the select control
    let options = props.field.options;

    //we divide the key into label and the value into value
    options = Object.entries(options).map((item) => {
        return {label: item[1], value: item[0]};
    });

    //and we add the select control to the data

    let data = [...riskData];
    for (const key in data) {
        let dataItem = {...data[key]}
        dataItem.riskSelection = <SelectControl
            id={dataItem.id}
            name={dataItem.name}
            value={dataItem.value}
            options={options}
            label=''
            onChange={(fieldValue) => onChangeHandler(fieldValue, dataItem)
            }
        />
        data[key] = dataItem;
    }

    return (
        <div>
            <DataTable
                columns={columns}
                data={Object.values(data)}
            />
        </div>
    )

    function buildColumn(column) {
        return {
            name: column.name,
            sortable: column.sortable,
            width: column.width,
            selector: row => row[column.column],
            grow: column.grow,
        };
    }

    function dispachNotification( risk, type ) {
        let text = __( 'Measure was set for ' + risk, 'really-simple-ssl' );
        dispatch('core/notices').createNotice(
            type,
            text,
            {
                __unstableHTML: true,
                id: 'rsssl_settings_saved',
                type: 'snackbar',
                isDismissible: false,
            }
        ).then(sleeper(2000)).then(( response ) => {
            dispatch('core/notices').removeNotice('rsssl_settings_saved');
        });
    }

    function onChangeHandler(fieldValue, item) {
        function update () {
            return new Promise((resolve, reject) => {
                updateRiskData(item.id, fieldValue).then((response) => {
                    dispachNotification(item.risk, 'success');
                    resolve();
                })
                    .catch((response) => {
                        dispachNotification(item.risk, 'error');
                        reject();
                    });
            });
        }
        update();
    }

}

export default RiskComponent;