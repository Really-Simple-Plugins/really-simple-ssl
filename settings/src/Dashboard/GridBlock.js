const GridBlock = (props) => {
    const footer =props.block.footer ? props.block.footer : false;
    const blockData = props.block;
    let className = "rsssl-grid-item "+blockData.class+" rsssl-"+blockData.id;
    return (
        <div key={"block-"+blockData.id} className={className}>
            <div key={"header-"+blockData.id} className="rsssl-grid-item-header">
                { blockData.header && wp.element.createElement(blockData.header) }
                { !blockData.header && <>
                        <h3 className="rsssl-grid-title rsssl-h4">{ blockData.title }</h3>
                        <div className="rsssl-grid-item-controls"></div>
                    </>
                }
            </div>
            <div key={"content-"+blockData.id} className="rsssl-grid-item-content">{wp.element.createElement(props.block.content)}</div>
            { !footer && <div key={"footer-"+blockData.id} className="rsssl-grid-item-footer"></div>}
            { footer && <div key={"footer-"+blockData.id} className="rsssl-grid-item-footer">{wp.element.createElement(footer)}</div>}
        </div>
    );
}

export default GridBlock;