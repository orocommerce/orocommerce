import _ from 'underscore';
import FilterItemsHintView from 'oroproduct/js/app/views/filter-items-hint-view';
import filtersContainerTemplate from 'tpl-loader!oroproduct/templates/sidebar-filters/filters-container.html';

export default {
    processDatagridOptions(deferred, options) {
        options.filterContainerSelector = '[data-role="sidebar-filter-container"]';
        if (!options.metadata.options.filtersManager) {
            options.metadata.options.filtersManager = {};
        }
        Object.assign(options.metadata.options.filtersManager, {
            outerHintContainer: `[data-hint-container="${options.gridName}"]`,
            renderMode: 'toggle-mode',
            autoClose: false,
            enableMultiselectWidget: false,
            template: filtersContainerTemplate
        });

        options.metadata.filters.forEach(filter => {
            filter.outerHintContainer = `[data-hint-container="${options.gridName}"]`;
            filter.initiallyOpened = true;
            filter.autoClose = false;
        });

        const toolbarOptions = options.metadata.options.toolbarOptions;
        const toolbarClassNames = ['datagrid-toolbar--no-x-offset'];

        if (toolbarOptions.className) {
            toolbarClassNames.push(toolbarOptions.className );
        }
        toolbarOptions.className = _.uniq(toolbarClassNames).join(' ');
        return deferred.resolve();
    },

    init(deferred, options) {
        options.gridPromise.done(grid => {
            grid.once('filters:beforeRender', () => {
                const topToolbar = grid.toolbars.top;

                if (topToolbar && !topToolbar.disposed) {
                    const filterItemsHintView = new FilterItemsHintView({
                        renderMode: options.metadata.options.filtersManager.renderMode,
                        gridName: grid.name
                    });

                    topToolbar.el.after(filterItemsHintView.render().el);
                }
            });
        });
        return deferred.resolve();
    }
};
