import useModal from "./ModalData";

const ModalControl = (props) => {
    const {handleModal} = useModal();

    const onClickHandler = () => {
        handleModal(true, props.modalData, props.item );
    }

    return (
        <button className={"button button-" + props.btnStyle} onClick={ (e) => onClickHandler(e) }>{props.btnText}</button>
    )
}
export default ModalControl