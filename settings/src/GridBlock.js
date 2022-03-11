import {
    Placeholder,
} from '@wordpress/components';

import {
    Component,
} from '@wordpress/element';
import * as rsssl_api from "./utils/api";



class GridBlock extends Component {
    constructor() {
        super( ...arguments );
        this.state = {
            blockData:'',
            isAPILoaded: false,
        };

        let block = this.props.block;
        this.getBlock(block).then(( response ) => {
            this.setState({
                isAPILoaded: true,
                blockData: response,
            });
        });
    }

    getBlock(block){
        return rsssl_api.getBlock(block).then( ( response ) => {
            return response.data;
        });
    }
    componentDidMount() {
        this.getBlock = this.getBlock.bind(this);
        this.setState({
            isAPILoaded: true,
        });
    }

    render(){
        const {
            blockData,
            isAPILoaded,
        } = this.state;
        if ( ! isAPILoaded ) {
            return (
                <Placeholder></Placeholder>
            );
        }
        let className = "rsssl-item rsssl-"+blockData.size+" rsssl-"+blockData.id;
        return (
            <div className={className}>
                <div className="item-container">
                    <div className="rsssl-grid-item-header">
                        <h3>{ blockData.title }</h3>
                        Header
                    </div>
                    <div className="rsssl-grid-item-content" dangerouslySetInnerHTML={{__html: blockData.html}}></div>
                    <div className="rsssl-grid-item-footer" dangerouslySetInnerHTML={{__html: blockData.footer}}></div>
                </div>
            </div>
        );
    }
}

export default GridBlock;