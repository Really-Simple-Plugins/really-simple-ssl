import { __ } from '@wordpress/i18n';
import {
    Component,
} from '@wordpress/element';

import * as rsssl_api from "./utils/api";
import ProgressBlock from "./ProgressBlock";
import ProgressHeader from "./ProgressBlockHeader";
import SecurityFeaturesBlock from './SecurityFeaturesBlock';
import Placeholder from './Placeholder';


/**
 * using the gridbutton generates a button which will refresh the gridblock when clicked
 * The onclick action triggers the getBlockData method
 *
 */
class GridButton extends Component {
    constructor() {
        super( ...arguments );
    }
    render(){
        let disabled = this.props.disabled ? 'disabled' : '';
        return (
            <button className="button-primary" disabled={disabled} onClick={this.props.onClick}>{this.props.text}</button>
        );
    }
}

/**
 * Mapping of components, for use in the config array
 * @type {{SslLabs: JSX.Element}}
 */
var dynamicComponents = {
    "SecurityFeaturesBlock": SecurityFeaturesBlock,
    "ProgressBlock": ProgressBlock,
    "ProgressHeader": ProgressHeader,
};

class GridBlock extends Component {
    constructor() {
        super( ...arguments );
        this.footerHtml = this.props.block.footer.data;
        this.BlockProps=[];
        this.state = {
            isAPILoaded: false,
            content:'',
            testDisabled:false,
            footerHtml:this.props.block.footer.html,
            progress:0,
            testRunning:false,
            BlockProps:null,
        };
        this.dynamicComponents = {
            "getBlockData": this.getBlockData,
        };
        if (this.props.block.content.type==='test') {
            this.getBlockData('initial');
        } else {
            this.content = this.props.block.content.data;
        }
    }

    /**
     * Get block data for this grid block, in object format, as defined in settings/config/config.php
     * @param state
     * @returns {Promise<AxiosResponse<any>>}
     */
    getBlockData(state){
        let setState='clearcache';
        if (state==='initial' || state==='refresh') {
            setState = state;
        }
        let test = this.props.block.content.data;
        return rsssl_api.runTest(test, setState).then((response) => {
            this.content = response.data.html
            this.testDisabled = response.data.disabled
            this.progress = response.data.progress
            this.testRunning = this.progress<100
            this.footerHtml = response.data.footerHtml
            this.setState({
                testRunning:this.testRunning,
                content:this.content,
                testDisabled:this.testDisabled,
                footerHtml:this.footerHtml,
                progress:this.progress,
                isAPILoaded: true,
            })
        });
    }

    componentDidMount() {
        this.getBlockData = this.getBlockData.bind(this);
        this.highLightField = this.highLightField.bind(this);
        this.setBlockProps = this.setBlockProps.bind(this);
        if ( this.props.block.content.type==='html' || this.props.block.content.type==='react' ) {
            let content = this.props.block.content.data;
            this.content = content;
            this.setState({
                isAPILoaded: true,
                content:content,
                progress:100,
            })
        }
    }

    /**
     * Allow child blocks to set data on the gridblock
     * @param key
     * @param value
     */
    setBlockProps(key, value){
        this.BlockProps[key] = value;
        this.setState({
            BlockProps: this.BlockProps,
        })
    }

    highLightField(fieldId){
        this.props.highLightField(fieldId);
    }

    render(){
        let {
            isAPILoaded,
            content,
        } = this.state;
        let blockData = this.props.block;
        let className = "rsssl-grid-item rsssl-"+blockData.size+" rsssl-"+blockData.id;
        if ( this.props.block.content.type==='react') {
            content = this.props.block.content.data;
        }
        if ( this.testRunning ){
            const timer = setTimeout(() => {
                this.getBlockData('refresh');
            }, blockData.content.interval );
        }

        let DynamicBlockProps = { saveChangedFields: this.props.saveChangedFields, setBlockProps: this.setBlockProps, BlockProps: this.BlockProps, runTest: this.runTest, fields: this.props.fields, isApiLoaded: this.props.isApiLoaded, highLightField: this.highLightField };
        return (
            <div className={className}>
                <div className="rsssl-grid-item-header">
                    <h3 className="burst-grid-title rsssl-h4">{ blockData.title }</h3>
                    <div className="rsssl-grid-item-controls">
                        {blockData.header && blockData.header.type==='url' && <a href={blockData.header.data}>{__("Instructions", "really-simple-ssl")}</a>}
                        {blockData.header && blockData.header.type==='html' && <span className="rsssl-header-html" dangerouslySetInnerHTML={{__html: blockData.header.data}}></span>}
                        {blockData.header && blockData.header.type==='react' && wp.element.createElement(dynamicComponents[blockData.header.data], DynamicBlockProps)}
                    </div>
                </div>
                {!isAPILoaded && <Placeholder lines="4"></Placeholder>}
                {blockData.content.type!=='react' && <div className="rsssl-grid-item-content" dangerouslySetInnerHTML={{__html: content}}></div>}
                {blockData.content.type==='react' && <div className="rsssl-grid-item-content">{wp.element.createElement(dynamicComponents[content], DynamicBlockProps)}</div>}

                { blockData.footer.hasOwnProperty('button') && <div className="rsssl-grid-item-footer"><GridButton text={blockData.footer.button.text} onClick={this.getBlockData} disabled={this.testDisabled}/></div>}
                { blockData.footer.type==='html' && <div className="rsssl-grid-item-footer" dangerouslySetInnerHTML={{__html: this.footerHtml}}></div>}
            </div>
        );
    }
}

export default GridBlock;