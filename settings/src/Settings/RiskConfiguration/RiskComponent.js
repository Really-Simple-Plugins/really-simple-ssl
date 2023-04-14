import React, {useEffect} from 'react';
import UseRiskData from "./RiskData";
import DataTable from 'react-data-table-component';
import {SelectControl} from "@wordpress/components";
import sleeper from "../../utils/sleeper";
import {dispatch} from '@wordpress/data';
import {__} from "@wordpress/i18n";
import useFields from "../FieldsData";

const RiskComponent = (props) => {
    //first we put the data in a state
    const {riskData, dataLoaded, dataVulLoaded, vulnerabilities, fetchRiskData, fetchVulnerabilities, setData, updateRiskData} = UseRiskData();
    const { fields, fieldAlreadyEnabled} = useFields();

    useEffect(() => {
        if ( fieldAlreadyEnabled('enable_vulnerability_scanner')) {
            if (!dataLoaded) {
                fetchRiskData();
            }
            if (!dataVulLoaded) {
                fetchVulnerabilities();
            }
        }
    }, [fields]);

    //we only proceed if the data is loaded
    if (!dataLoaded) {
        return null;
    }
    if (dataVulLoaded) {

        //we add a help on the left side
        dispatch('core/notices').createNotice(
            'info',
            'This is a test',
            {
                isDismissible: true,
            },
        );
    }

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
    //we check if the property request_success exists if so we remove it
    if (riskData.hasOwnProperty('request_success')) {
        delete riskData.request_success;
    }

    //and we add the select control to the data
    Object.keys(riskData).forEach((item) => {
        riskData[item].riskSelection = <SelectControl
            id={riskData[item].id}
            name={riskData[item].name}
            value={riskData[item].value}
            options={options}
            label=''
            onChange={(fieldValue) => onChangeHandler(fieldValue, riskData[item])
            }
        />
    });

    return (
        <div>
            <DataTable
                columns={columns}
                data={Object.values(riskData)}
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
        // updateRiskData(item.id, fieldValue).then((response) => {
        //     dispachNotification(item.risk, 'success');
        // })
        //     .then((response) => {
        //         dispachNotification(item.risk, 'error');
        //     });
        // ;
    }

}

export default RiskComponent;