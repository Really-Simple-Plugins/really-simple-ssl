import RssslModal from "../Modal/RssslModal";
const { __ } = wp.i18n;
const { useState, useEffect } = wp.element;

const DeactivationModal = () => {
    const [isOpen, setOpen] = useState(true);
    onConfirm = () => {
        const targetPluginLink = document.getElementById('deactivate-really-simple-ssl');
        //click the targetPluginLink
        targetPluginLink.click();
    }

    useEffect(() => {
        // Add an event listener to elements with the "my-link" class

        const handleClick = (event) => {
            event.preventDefault();
            setOpen(true);
        };

        // Attach the click event listener to each link element
        const targetPluginLink = document.getElementById('deactivate-really-simple-ssl');
        targetPluginLink.addEventListener('click', handleClick);

        // Clean up the event listeners when the component unmounts
        return () => {
            targetPluginLink.removeEventListener('click', handleClick);
        };
    }, []);

    let content = "TEST";
    return (
        <>
            <RssslModal title={__("Are you sure?", "really-simple-ssl")} onConfirm={ onConfirm() } content={content} isOpen={isOpen} setOpen={setOpen}/>
        </>
    );
}
export default DeactivationModal;