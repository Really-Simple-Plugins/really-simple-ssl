import {
    Component,
} from '@wordpress/element';
import * as rsssl_api from "./utils/api";

class GridBlock extends Component {
    constructor() {
        super( ...arguments );
        this.state = {
            block:this.props.block,
            isAPILoaded: false,
        };

        console.log(this.props.block);
        let block = this.props.block;
        this.getBlock(block).then(( response ) => {
            console.log(response);
            this.setState({
                isAPILoaded: true,
                fields: fields,
                menu: menu,
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
    }

    render(){
        // if ( ! isAPILoaded ) {
        //     return (
        //         <div className="rsssl-item {class}"><div className="item-container"><Placeholder></Placeholder></div></div>
        //     );
        // }
        return (
            <div className="rsssl-item {class}">
                <div className="item-container">
                    <div className="rsssl-grid-item-header">
                        <h3>Title</h3>
                        Header
                    </div>
                    <div className="rsssl-grid-item-content">
                        content
                    </div>
                    <div className="rsssl-grid-item-footer">
                        footer
                    </div>
                </div>
            </div>
        );
    }
}

export default GridBlock;