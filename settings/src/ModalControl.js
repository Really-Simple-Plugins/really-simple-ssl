import {Component} from "@wordpress/element";

class ModalControl extends Component{
    constructor() {
        super( ...arguments );
    }
    componentDidMount() {
        this.onClickHandler = this.onClickHandler.bind(this);
    }

    onClickHandler(){
        this.props.handleModal(true, this.props.modalData );
    }

    render(){
        return (
            <button onClick={ (e) => this.onClickHandler(e) }>{this.props.btnText}</button>
        )
    }
}
export default ModalControl