import React from 'react';
import PropTypes from 'prop-types';
import {Button} from './_button';
import {FilterDate, FilterText} from '.';
import '../../styles/component/_advanced-search.scss';

export default class AdvancedSearch extends React.Component {
    render() {
        const {onDateRangeChanged} = this.props;
        return (
            <div className="advanced-search col-12">
                <div className="card">
                    <div className="card-body">
                        <div className="row">
                            <div className="col-12 col-md-6">
                                <p>What are you looking for ? </p>
                                <FilterText
                                    placeholder="status = 200 AND url LIKE '%product%'"
                                />
                            </div>
                            <div className="input-search col-12 col-md-4">
                                <p>Date Range </p>
                                <FilterDate
                                    className="d-inline"
                                    onDateRangeChanged={onDateRangeChanged}
                                />
                            </div>
                            <div className="col-12 col-md-2 btn-action-group">
                                <Button className="btn-search ml-2 w-100">SEARCH</Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        );
    }
}

AdvancedSearch.propTypes = {
    onDateRangeChanged: PropTypes.func
};