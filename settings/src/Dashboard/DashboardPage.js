import {Component, Fragment} from "@wordpress/element";
import GridBlock from "./GridBlock";

class DashboardPage extends Component {
    constructor() {
        super( ...arguments );
    }

    render() {
        let blocks = rsssl_settings.blocks;
        return (
            <Fragment>
                {blocks.map((block, i) => <GridBlock key={i}
                                            block={block}
                                            setShowOnBoardingModal={this.props.setShowOnBoardingModal}
                                            isApiLoaded={this.props.isAPILoaded}
                                            fields={this.props.fields}
                                            highLightField={this.props.highLightField}
                                            selectMainMenu={this.props.selectMainMenu}
                                            />)}
            </Fragment>
        );
    }
}
export default DashboardPage