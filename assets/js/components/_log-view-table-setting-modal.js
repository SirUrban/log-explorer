import React, {Component} from 'react';
import PropTypes from 'prop-types';
import {Checkbox, Modal, Button, Colors} from '.';
import {LogViewActions} from '../actions';

export class LogViewTableSettingModal extends Component {
    constructor(props) {
        super(props);
        this.state = {
            selectedTable: null,
            tableColumnList: []
        };

        this.onShow = this.onShow.bind(this);
        this.onChange = this.onChange.bind(this);
        this.toggleAll = this.toggleAll.bind(this);
    }

    componentDidUpdate(prevProps, prevState, snapshot) {
        const prevShow = prevProps.show;
        const {show} = this.props;

        if (show && show !== prevShow) {
            this.onShow();
        }
    }

    onShow() {
        const {selectedTable} = this.props;
        const that = this;
        return LogViewActions.getColumnSetting(selectedTable.uuid).then(response => {
            const {data, error} = response;

            if (error) {
                return;
            }

            that.setState({tableColumnList: data});
        });
    }

    onChange(event) {
        const name = event.target.name;
        if (!name) {
            return;
        }

        const that = this;
        const {onSave, selectedTable} = this.props;

        LogViewActions.updateColumnSetting(selectedTable.uuid, name, event.target.checked).then(response => {
            const {data, error} = response;

            if (error) {
                return;
            }

            that.onShow().then(() => {
                if (typeof onSave === 'function') {
                    onSave(data);
                }
            });
        });
    }

    toggleAll() {
        const {tableColumnList} = this.state;
        let currentState = true;

        tableColumnList.map((item) => {
            if (!item.visible) {
                currentState = false;
            }
        });

        const that = this;
        const {onSave, selectedTable} = this.props;

        LogViewActions.updateAllColumnsSetting(selectedTable.uuid, !currentState).then(response => {
            const {data, error} = response;

            if (error) {
                return;
            }

            that.onShow().then(() => {
                if (typeof onSave === 'function') {
                    onSave(data);
                }
            });
        });
    }

    render() {
        const {show, onHidden, onSave, showSaveButton = true} = this.props;
        const {tableColumnList} = this.state;

        return (
            <Modal title={'Table Setting'} id={'table-setting'} saveButtonTitle={'Save'}
                show={show}
                saveButtonAction={onSave}
                showSaveButton={showSaveButton}
                onHidden={onHidden}>
                <div className="row">
                    <Button onClick={this.toggleAll}
                            type={'button'}
                            className={'btn-sm mb-2'}
                            color={Colors.default}>
                        Toggle All
                    </Button>
                </div>
                {tableColumnList && tableColumnList.length > 0 &&
                    <div className={'row'}>
                        {tableColumnList.map((item, row) => {
                            return <div key={row} className={'col-4'}>
                                <Checkbox name={item.name}
                                    label={item.title}
                                    id={`checkbox-${item.name}`}
                                    checked={item.visible}
                                    onChange={this.onChange}
                                    value="1"
                                    className={'table-column'}/>
                            </div>;
                        })}
                    </div>}
            </Modal>
        );
    }
}

LogViewTableSettingModal.propTypes = {
    show: PropTypes.bool,
    selectedTable: PropTypes.object,
    onHidden: PropTypes.func,
    onSave: PropTypes.func,
    showSaveButton: PropTypes.bool
};
