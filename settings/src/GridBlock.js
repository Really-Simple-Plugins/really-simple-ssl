import {
    Placeholder,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import {
    Component,
} from '@wordpress/element';
import * as rsssl_api from "./utils/api";
class GridButton extends Component {
    constructor() {
        super( ...arguments );
    }
    render(){

        return (
            <button className="button-primary" onClick={this.props.onClick}>{this.props.text}</button>
        );
    }
}

/**
 * Mapping of components, for use in the config array
 * @type {{SslLabs: JSX.Element}}
 */
var dynamicComponents = {
    // "runTest": runTest,
};

class GridBlock extends Component {
    constructor() {
        super( ...arguments );
        this.state = {
            isAPILoaded: false,
            content:'',
            progress:0,
            testRunning:false,
        };
        this.dynamicComponents = {
            "runTest": this.runTest,
        };
        if (this.props.block.content.type==='test') {
            this.runTest('initial');
        } else {
            this.content = this.props.block.content.data;
        }
    }

    runTest(state){
        let setState='clearcache';
        if (state==='initial' || state==='refresh') {
            setState = state;
        }

        let test = this.props.block.content.data;
        return rsssl_api.runTest(test, setState).then((response) => {
            console.log(response);
            let progress = response.data.progress;
            let content = response.data.html;
            let testRunning = false;
            if (progress<100) {
                testRunning = true;
            }
            this.content = content
            this.progress = progress
            this.testRunning = testRunning
            this.setState({
                testRunning:testRunning,
                content:content,
                progress:progress,
                isAPILoaded: true,

            })
        });
    }

    componentDidMount() {
        this.runTest = this.runTest.bind(this);
        if (this.props.block.content.type==='html' || this.props.block.content.type==='react') {
            let content = this.props.block.content.data;
            this.content = content;
            this.setState({
                isAPILoaded: true,
                content:content,
                progress:100,
            })
        }
    }

    render(){
        let {
            isAPILoaded,
            content,
        } = this.state;
        let blockData = this.props.block;
        let className = "rsssl-item rsssl-"+blockData.size+" rsssl-"+blockData.id;
        if ( this.props.block.content.type==='react') {
            content = this.props.block.content.data;
        }
        if ( this.testRunning ){
            const timer = setTimeout(() => {
                this.runTest('refresh');
            }, blockData.content.interval );
        }
        return (
            <div className={className}>
                <div className="item-container">
                    <div className="rsssl-grid-item-header">
                        <h3>{ blockData.title }</h3>
                        {blockData.url && <a href={blockData.url}>{__("Instructions", "really-simple-ssl")}</a>}
                    </div>
                    {!isAPILoaded && <Placeholder></Placeholder>}
                    {blockData.content.type!=='react' && <div className="rsssl-grid-item-content" dangerouslySetInnerHTML={{__html: content}}></div>}
                    {blockData.content.type==='react' && <div className="rsssl-grid-item-content">{wp.element.createElement(dynamicComponents[blockData.content])}</div>}
                    <div className="rsssl-grid-item-footer">
                        { blockData.footer.hasOwnProperty('button') && <GridButton text={blockData.footer.button.text} onClick={this.runTest} action={blockData.footer.button.action} disabled={blockData.footer.button.disabled}/>}
                        { blockData.footer.hasOwnProperty('html') && <span className="rsssl-footer-html" dangerouslySetInnerHTML={{__html: blockData.footer.html}}></span>}
                    </div>
                </div>
            </div>
        );
    }
}

export default GridBlock;