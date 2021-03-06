import React, {Component} from 'react';
import PropTypes from 'prop-types';
import {CardHeader, CardTool, DropdownItem, JsGridTable, LogViewTableSettingModal, QueryInfo} from '.';
import {LogTableActions} from '../actions';

export class LogViewTable extends Component {
    constructor(props) {
        super(props);
        this.state = {
            showTableSettingModal: false,
            fields: [],
            queryInfo: {}
        };

        this.showTableSettingModal = this.showTableSettingModal.bind(this);
        this.hideTableSettingModal = this.hideTableSettingModal.bind(this);
        this.onTableSettingModalChanged = this.onTableSettingModalChanged.bind(this);
        this.onDataLoaded = this.onDataLoaded.bind(this);
    }

    componentDidUpdate(prevProps) {
        const {selectedTable} = this.props;
        const prevSelectedTable = prevProps.selectedTable;

        if (selectedTable && selectedTable !== prevSelectedTable) {
            this.loadColumns();
        }
    }

    componentDidMount() {
        this.loadColumns();
    }

    loadColumns() {
        const {selectedTable} = this.props;

        if (!selectedTable) {
            return;
        }

        LogTableActions.getColumns(selectedTable.uuid).then(response => {
            const {data, error} = response;
            if (error) {
                return;
            }

            this.setState({
                fields: data
            });
        });
    }

    showTableSettingModal(event) {
        event.preventDefault();
        this.setState({showTableSettingModal: true});
    }

    hideTableSettingModal(event) {
        event.preventDefault();
        this.setState({showTableSettingModal: false});
    }

    onTableSettingModalChanged() {
        this.loadColumns();
    }

    onDataLoaded(res) {
        const {itemsCount, data, queryInfo} = res;
        queryInfo.total = itemsCount;
        queryInfo.current = data.length;
        this.setState({queryInfo});
    }

    render() {
        const {selectedTable} = this.props;
        const {fields, showTableSettingModal, queryInfo} = this.state;

        return (
            (fields && fields.length > 0 && <div className="col-12 col-md-auto">
                <LogViewTableSettingModal show={showTableSettingModal}
                    selectedTable={selectedTable}
                    onSave={this.onTableSettingModalChanged}
                    onHidden={this.hideTableSettingModal}/>
                <div className="card">
                    <CardHeader title="Home Page">
                        <CardTool>
                            <DropdownItem onClick={this.showTableSettingModal}>
                                Setting
                            </DropdownItem>
                        </CardTool>
                    </CardHeader>
                    <div className="card-body pt-0">
                        <div className={'row mb-3'}>
                            <QueryInfo queryInfo={queryInfo} className={'col-12'}/>
                        </div>
                        {fields && fields.length > 0 &&
                        <JsGridTable
                            height='auto'
                            logview={selectedTable}
                            fields={fields}
                            pageSize={100}
                            onDataLoaded={this.onDataLoaded}
                        />}
                    </div>
                </div>
            </div>)
        );
    }
}

LogViewTable.propTypes = {
    selectedTable: PropTypes.object
};
