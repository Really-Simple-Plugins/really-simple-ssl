import TaskElement from "../../Dashboard/TaskElement";
import * as rsssl_api from "../../utils/api";
import { __ } from '@wordpress/i18n';
import useFields from "./../FieldsData";
import useLicense from "./LicenseData";
import {useEffect} from "@wordpress/element";
const License = ({field, isOnboarding}) => {
    const {fields, setChangedField, updateField} = useFields();
    const {toggleActivation, licenseStatus, setLicenseStatus, notices, setNotices, setLoadingState} = useLicense();

    useEffect(() => {
        setLoadingState();
    }, []);
    const getLicenseNotices = () => {
        return rsssl_api.runTest('licenseNotices', 'refresh').then( ( response ) => {
            return response;
        });
    }

    useEffect( () => {
        getLicenseNotices().then(( response ) => {
            setLicenseStatus(response.licenseStatus);
            setNotices(response.notices);
        });
    }, [fields] );

    const onChangeHandler = (fieldValue) => {
        setChangedField( field.id, fieldValue )
        updateField(field.id, fieldValue);
    }

    return (
        <div className="components-base-control">
            <div className="components-base-control__field">
                { !isOnboarding && <label className="components-base-control__label" htmlFor={field.id}>
                    {field.label}
                </label> }
                <div className="rsssl-license-field">
                    <input
                        className="components-text-control__input"
                        type="password"
                        id={field.id}
                        value={field.value}
                        onChange={(e) => onChangeHandler(e.target.value)}
                    />
                    { !isOnboarding &&
                        <button className="button button-default" onClick={() => toggleActivation(field.value)}>
                            {licenseStatus === 'valid' && <>{__('Deactivate', 'really-simple-ssl')}</>}
                            {licenseStatus !== 'valid' && <>{__('Activate', 'really-simple-ssl')}</>}
                        </button>
                    }
                </div>
            </div>
            { notices.map((notice, i) => (
                <TaskElement key={'task-' + i} index={i} notice={notice} highLightField="" />
            ))}
        </div>
    );
}

export default License;