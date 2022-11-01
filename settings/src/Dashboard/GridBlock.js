import { __ } from '@wordpress/i18n';
import {
    Component,
} from '@wordpress/element';

import * as rsssl_api from "../utils/api";
import ProgressBlock from "./ProgressBlock";
import ProgressHeader from "./ProgressBlockHeader";
import ProgressFooter from "./ProgressFooter";
import SslLabs from "./SslLabs";
import SslLabsFooter from "./SslLabsFooter";
import OtherPlugins from "./OtherPlugins";
import SecurityFeaturesBlock from './SecurityFeaturesBlock/SecurityFeaturesBlock';
import SecurityFeaturesFooter from './SecurityFeaturesBlock/SecurityFeaturesFooter';
import Placeholder from '../Placeholder/Placeholder';

/*
 * Mapping of components, for use in the config array
 * @type {{SslLabs: JSX.Element}}
 */
var dynamicComponents = {
    "SecurityFeaturesBlock": SecurityFeaturesBlock,
    "SecurityFeaturesFooter": SecurityFeaturesFooter,
    "ProgressBlock": ProgressBlock,
    "ProgressHeader": ProgressHeader,
    "ProgressFooter": ProgressFooter,
    "SslLabs": SslLabs,
    "SslLabsFooter": SslLabsFooter,
    "OtherPlugins": OtherPlugins,
};

class GridBlock extends Component {
    constructor() {
        super( ...arguments );
        this.footerHtml = this.props.block.footer.data;
        this.highLightField = this.highLightField.bind(this);
        this.setBlockProps = this.setBlockProps.bind(this);
        let content = this.props.block.content.data;
        let footer = this.props.block.footer.data;
        this.state = {
            content:'',
            testDisabled:false,
            footerHtml:this.props.block.footer.html,
            BlockProps:[],
            content:content,
            footer:footer,
        };
    }

    /*
     * Allow child blocks to set data on the gridblock
     * @param key
     * @param value
     */
    setBlockProps(key, value){
        let {
                BlockProps,
            } = this.state;

        if (!BlockProps.hasOwnProperty(key) || BlockProps[key]!==value) {
            BlockProps[key] = value;
            this.setState({
                BlockProps: BlockProps,
            })
        }
    }

    highLightField(fieldId){
        this.props.highLightField(fieldId);
    }

    render(){
        let {
            content,
            footer,
            BlockProps,
        } = this.state;
        let blockData = this.props.block;
        let className = "rsssl-grid-item "+blockData.class+" rsssl-"+blockData.id;
        if ( this.props.block.content.type==='react') {
            content = this.props.block.content.data;
        }
        if ( this.props.block.footer.type==='react') {
            footer = this.props.block.footer.data;
        }

        let DynamicBlockProps = { saveChangedFields: this.props.saveChangedFields, setShowOnBoardingModal:this.props.setShowOnBoardingModal, setBlockProps: this.setBlockProps, BlockProps: BlockProps, runTest: this.runTest, fields: this.props.fields, isApiLoaded: this.props.isApiLoaded, highLightField: this.highLightField, selectMainMenu: this.props.selectMainMenu };
        return (
            <div className={className}>
                <div className="rsssl-grid-item-header">
                    <h3 className="rsssl-grid-title rsssl-h4">{ blockData.title }</h3>
                    <div className="rsssl-grid-item-controls">
                        {blockData.controls && blockData.controls.type==='url' && <a href={blockData.controls.data}>{__("Instructions", "really-simple-ssl")}</a>}
                        {blockData.controls && blockData.controls.type==='html' && <span className="rsssl-header-html" dangerouslySetInnerHTML={{__html: blockData.controls.data}}></span>}
                        {blockData.controls && blockData.controls.type==='react' && wp.element.createElement(dynamicComponents[blockData.controls.data], DynamicBlockProps)}
                    </div>
                </div>
                { blockData.content.type!=='react' && <div className="rsssl-grid-item-content" dangerouslySetInnerHTML={{__html: content}}></div>}
                { blockData.content.type==='react' && <div className="rsssl-grid-item-content">{wp.element.createElement(dynamicComponents[content], DynamicBlockProps)}</div>}

                { blockData.footer.type==='html' && <div className="rsssl-grid-item-footer" dangerouslySetInnerHTML={{__html: this.footerHtml}}></div>}
                { blockData.footer.type==='react' && <div className="rsssl-grid-item-footer">{wp.element.createElement(dynamicComponents[footer], DynamicBlockProps)}</div>}
            </div>
        );
    }
}

export default GridBlock;