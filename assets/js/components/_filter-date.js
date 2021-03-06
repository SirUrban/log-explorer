import React, {Component} from 'react';
import PropTypes from 'prop-types';
import 'admin-lte/plugins/daterangepicker/daterangepicker.css';
import 'admin-lte/plugins/daterangepicker/daterangepicker';
import moment from 'moment';

export class FilterDate extends Component {
    constructor(props) {
        super(props);
        const {from = '60', to = ''} = this.props;

        this.state = {
            from,
            to,
            // Default select option
            dateRangeValue: '1 hour'
        };
    }

    onFilterChanged(from, to, dateRangeValue) {
        const {onDateRangeChanged} = this.props;
        this.setState({
            from,
            to,
            dateRangeValue
        });
        if (onDateRangeChanged) {
            onDateRangeChanged(from, to);
        }
    }

    componentDidMount() {
        const that = this;
        $(() => {
            $('#date-range').daterangepicker(
                {
                    ranges: {
                        '1 hour': [moment().subtract(1, 'hour'), moment()],
                        '12 hours': [moment().subtract(12, 'hours'), moment()],
                        '1 day': [moment().subtract(24, 'hours'), moment()],
                        '7 days': [moment().subtract(7, 'days'), moment()],
                        Today: [moment(), moment()],
                        Yesterday: [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        'This Month': [moment().startOf('month'), moment().endOf('month')],
                        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                    },
                    timePicker: true,
                    timePicker24Hour: true,
                    opens: 'center',
                    autoApply: true,
                    startDate: moment().subtract(1, 'hour'),
                    endDate: moment()
                },
                (start, end, label) => {
                    switch (label) {
                        case '1 hour':
                            that.onFilterChanged(60, '', label);
                            break;
                        case '12 hours':
                            that.onFilterChanged(720, '', label);
                            break;
                        case '1 day':
                            that.onFilterChanged(1440, '', label);
                            break;
                        case '7 days':
                            that.onFilterChanged(10080, '', label);
                            break;
                        case 'Custom Range':
                            that.onFilterChanged(start.format('YYYY-MM-DD HH:mm:00'), end.format('YYYY-MM-DD HH:mm:59'),
                                start.format('YYYY-MM-DD HH:MM') + ' - ' + end.format('YYYY-MM-DD HH:MM'));
                            break;
                        default:
                            that.onFilterChanged(start.format('YYYY-MM-DD 00:00:00'), end.format('YYYY-MM-DD 23:59:59'), label);
                            break;
                    }
                }
            );
        });
    }

    render() {
        const {onDateRangeChanged, label, ...rest} = this.props;
        const {from, to, dateRangeValue} = this.state;

        return (
            <div {...rest}>
                <div>
                    <p className="float-left mb-2">{label}</p>
                </div>
                <button type="button" className="btn btn-default w-100" id="date-range">
                    <>
                        <i className="far fa-calendar-alt mr-2"></i>
                        <span>{dateRangeValue}</span>
                        <i className="fas fa-caret-down ml-2"></i>
                    </>
                </button>
                <input type="hidden" id="date-range-from" value={from}/>
                <input type="hidden" id="date-range-to" value={to}/>
            </div>
        );
    }
}

FilterDate.propTypes = {
    className: PropTypes.string,
    label: PropTypes.string,
    from: PropTypes.any,
    to: PropTypes.string,
    onDateRangeChanged: PropTypes.func
};
