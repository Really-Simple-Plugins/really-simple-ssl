import {
    Placeholder,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import {
    Component,
} from '@wordpress/element';
import * as rsssl_api from "./utils/api";


class Labs extends Component {
    render(){
        return (
          <div>SSL Labs block</div>
        );
    }
}

class GridBlock extends Component {
    constructor() {
        super( ...arguments );
        this.state = {
            isAPILoaded: false,
            content:'',
        };
        if (this.props.block.content.type==='test') {
            console.log("block is test");

            this.runTest().then(( response ) => {
                let content = response;
                this.content = content;
                this.setState({
                    isAPILoaded: true,
                    content:content,
                })
            });
        } else {
            this.content = this.props.block.content.data;
        }
    }

    runTest(){
        let test = this.props.block.content.data;
        return rsssl_api.runTest(test).then((response) => {
            return response.data;
        });
    }

    componentDidMount() {
        this.runTest = this.runTest.bind(this);
        console.log("did mount");
        console.log(this.props.block);
        if (this.props.block.content.type==='html') {
            console.log("block is html");

            let content = this.props.block.content.data;
            this.content = content;
            this.setState({
                isAPILoaded: true,
                content:content,
            })
        } else if ( this.props.block.content.type==='react' ) {
            console.log("block is react");
            let content = this.props.block.content.data;
            this.content = content;
            this.setState({
                isAPILoaded: true,
                content:content,
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
        console.log("loading block");
        console.log(content);
        return (
            <div className={className}>
                <div className="item-container">
                    <div className="rsssl-grid-item-header">
                        <h3>{ blockData.title }</h3>
                        {blockData.url && <a href={blockData.url}>{__("Instructions", "really-simple-ssl")}</a>}
                    </div>
                    {!isAPILoaded && <Placeholder></Placeholder>}
                    {this.props.block.content.type==='react' && wp.element.createElement( Labs, null, null)}
                    { (this.props.block.content.type==='html' || this.props.block.content.type==='test') && <div className="rsssl-grid-item-content" dangerouslySetInnerHTML={{__html: content}}></div>}
                    <div className="rsssl-grid-item-footer" dangerouslySetInnerHTML={{__html: blockData.footer}}></div>
                </div>
            </div>
        );
    }
}

export default GridBlock;