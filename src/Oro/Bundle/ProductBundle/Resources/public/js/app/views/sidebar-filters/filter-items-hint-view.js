import BaseView from 'oroui/js/app/views/base/view';
import _ from 'underscore';
import mediator from 'oroui/js/mediator';
import template from 'tpl-loader!oroproduct/templates/sidebar-filters/filter-items-hint.html';

const FilterItemsHintView = BaseView.extend({
    /**
     * Specific datagrid name
     * @property {string}
     */
    gridName: '',

    /**
     * Extra class name witch used to add separate styles for different modes
     * @property {string} 'dropdown-mode' | 'toggle-mode'
     */
    renderMode: '',

    /**
     * @inheritDoc
     */
    template: template,

    /**
     * @inheritDoc
     */
    events: {
        'click .reset-filter-button': 'resetAllFilters'
    },

    /**
     * @inheritDoc
     */
    attributes: {
        'class': 'filter-box',
        'data-sticky-target': 'top-sticky-panel',
        'data-sticky': JSON.stringify({
            isSticky: true,
            autoWidth: true,
            toggleClass: 'datagrid-toolbar-sticky-container',
            placeholderId: 'sticky_element_toolbar'
        })
    },

    /**
     * @inheritDoc
     */
    constructor: function FilterItemsHintView(options) {
        FilterItemsHintView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritDoc
     */
    initialize(options) {
        _.extend(this, _.pick(options, ['renderMode', 'gridName']));

        FilterItemsHintView.__super__.initialize.call(this, options);
    },

    /**
     * Click handler
     * @param e
     */
    resetAllFilters(e) {
        mediator.trigger('filters:reset', e);
    },

    /**
     * @inheritDoc
     */
    render() {
        FilterItemsHintView.__super__.render.call(this);
        this.$el.addClass(this.renderMode);
        this.$el.attr('data-hint-container', this.gridName);
        return this;
    }
});

export default FilterItemsHintView;

