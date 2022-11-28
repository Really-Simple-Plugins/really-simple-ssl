import {Component} from "@wordpress/element";
import { __ } from '@wordpress/i18n';
import * as rsssl_api from "../utils/api";
import Icon from "../utils/Icon";

class Modal extends Component {
    constructor() {
        super( ...arguments );
        this.state = {
            data:[],
            buttonsDisabled:false,

        };
    }

    dismissModal(dropItem){
        this.props.handleModal(false, null, dropItem);
    }
    componentDidMount() {
        this.setState({
            data:this.props.data,
            buttonsDisabled:false,
        });
    }

    handleFix(e){
        //set to disabled
        let action = this.props.data.action;
        this.setState({
            buttonsDisabled:true
        });
        rsssl_api.runTest(action, 'refresh', this.props.data ).then( ( response ) => {

            let {
                data,
            } = this.state;
            data.description = response.data.msg;
            data.subtitle = '';
            this.setState({
                data: data,
            });
            let item = this.props.data;
            if (response.data.success) {
                this.dismissModal(this.props.data);
            }
        });
    }

    render(){
        const {
            data,
            buttonsDisabled,
        } = this.state;
        let disabled = buttonsDisabled ? 'disabled' : '';
        let description = data.description;
        if ( !Array.isArray(description) ) {
            description = [description];
        }

        return (
            <div>
                <div className="rsssl-modal-backdrop" onClick={ (e) => this.dismissModal(e) }>&nbsp;</div>
                <div className="rsssl-modal" id="{id}">
                    <div className="rsssl-modal-header">
                        <h2 className="modal-title">
                            {data.title}
                        </h2>
                        <button type="button" className="rsssl-modal-close" data-dismiss="modal" aria-label="Close" onClick={ (e) => this.dismissModal(e) }>
                            <Icon name='times' />
                        </button>
                    </div>
                    <div className="rsssl-modal-content">
                        { data.subtitle && <div className="rsssl-modal-subtitle">{data.subtitle}</div>}
                        { Array.isArray(description) && description.map((s, i) => <div key={i} className="rsssl-modal-description">{s}</div>) }
                    </div>
                    <div className="rsssl-modal-footer">
                        { data.edit && <a href={data.edit} target="_blank" className="button button-secondary">{__("Edit", "really-simple-ssl")}</a>}
                        { data.help && <a href={data.help} target="_blank"  className="button rsssl-button-help">{__("Help", "really-simple-ssl")}</a>}
                        { (!data.ignored && data.action==='ignore_url') && <button disabled={disabled} className="button button-primary" onClick={ (e) => this.handleFix(e) }>{ __("Ignore", "really-simple-ssl")}</button>}
                        { data.action!=='ignore_url' &&  <button disabled={disabled} className="button button-primary" onClick={ (e) => this.handleFix(e) }>{__("Fix", "really-simple-ssl")}</button> }
                    </div>
                </div>
            </div>
        )
    }
}

export default Modal;