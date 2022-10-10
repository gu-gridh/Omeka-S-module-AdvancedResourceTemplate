(function($) {

    $(document).ready( function() {

        // In some cases (other modules), this js is used without properties.
        if (!$('div#properties').length) {
            // $('body').append('<div id="properties" style="display:none;">');
            return;
        }

        // Store the default data types.
        if (!$('div#properties').data('default-data-types') || !$('div#properties').data('default-data-types').length) {
            $('div#properties').data('default-data-types', 'literal,resource,uri');
        }

        let annotatingValue;
        const vaSidebar = $('#value-annotation-sidebar');
        const vaContainer = $('#value-annotation-container');
        const vaTypeSelect = $('#value-annotation-type-select');
        const vaPropertySelect = $('#value-annotation-property-select');
        const vaAddButton = $('#value-annotation-add');
        const vaSetButton = $('#value-annotation-set');
        const vaTemplates = vaContainer.data('valueAnnotationTemplates');
        // Make a value annotation jQuery/DOM object.
        const makeValueAnnotation = function(dataTypeName, value) {
            const valueAnnotation = $($.parseHTML(vaTemplates[dataTypeName]));
            const propertyLabel = vaPropertySelect.find(`option[value="${value.property_id}"]`).text();
            // Set the translated property label as the value annotation heading.
            valueAnnotation.find('.value-annotation-heading').text(propertyLabel);
            hydrateValueAnnotation(valueAnnotation, value);
            $(document).trigger('o:prepare-value-annotation', [dataTypeName, valueAnnotation, value]);
            return valueAnnotation;
        };
        // Hydrate value annotation inputs by mapping the value object to the
        // data-value-key attribute. Always call this before triggering the
        // o:prepare-value-annotation event.
        const hydrateValueAnnotation = function(valueAnnotation, value) {
            valueAnnotation.find(':input').each(function() {
                const thisInput = $(this);
                const valueKey = thisInput.data('valueKey');
                if (!valueKey) return;
                thisInput.removeAttr('name').val(value ? value[valueKey] : null);
                if ('is_public' === valueKey) {
                    // Prepare the visibility icon and value.
                    const visibilityIcon = thisInput.closest('.value').find('.value-annotation-visibility');
                    if (0 == value['is_public']) {
                        value['is_public'] = 0; // Cast false and "0" to 0
                        visibilityIcon.removeClass('o-icon-public')
                            .addClass('o-icon-private')
                            .attr('aria-label', Omeka.jsTranslate('Make public'))
                            .attr('title', Omeka.jsTranslate('Make public'));
                    } else {
                        value['is_public'] = 1; // Cast true and "1" to 1
                        visibilityIcon.removeClass('o-icon-private')
                            .addClass('o-icon-public')
                            .attr('aria-label', Omeka.jsTranslate('Make private'))
                            .attr('title', Omeka.jsTranslate('Make private'));
                    }
                }
            });
        };
        // Prepare the value annotation markup.
        $(document).on('o:prepare-value-annotation', function(e, dataTypeName, valueAnnotation, value) {
            // Set the display title for resource types.
            if (['resource:item', 'resource:itemset', 'resource:media'].includes(dataTypeName)) {
                let thumbnail = '';
                if (value.thumbnail_url) {
                    thumbnail = $('<img>', {src: value.thumbnail_url});
                }
                const resourceLink = $('<a>', {
                    text: value.display_title,
                    href: value.url,
                    target: '_blank',
                });
                if (value.value_resource_id !== undefined) {
                    valueAnnotation.find('.default').hide();
                }
                valueAnnotation.find('.o-title').append(thumbnail, resourceLink);
                valueAnnotation.find('.display_title').val(value.display_title);
                valueAnnotation.find('.url').val(value.url);
            }
        });
        // Handle "Annotate value" click.
        $(document).on('click', '.value-annotation-annotate', function(e) {
            e.preventDefault();
            annotatingValue = $(this).closest('.value');
            vaContainer.empty();
            $.each(annotatingValue.data('valueAnnotations'), function(propertyTerm, values) {
                $.each(values, function(index, value) {
                    value.property_term = propertyTerm;
                    const valueAnnotation = makeValueAnnotation(value.type, value);
                    vaContainer.append(valueAnnotation);
                });
            });
            Omeka.openSidebar(vaSidebar);
        });
        // Enable/disable "Add annotation" button.
        vaPropertySelect.on('change', function(e) {
            e.preventDefault();
            vaAddButton.prop('disabled', '' === vaPropertySelect.val() ? true : false);
        });
        // Handle "Add annotation" click.
        vaAddButton.on('click', function(e) {
            e.preventDefault();
            const dataTypeName = vaTypeSelect.val();
            const value = {
                is_public: 1,
                type: dataTypeName,
                property_id: vaPropertySelect.val(),
                property_term: vaPropertySelect.find('option:selected').data('term')
            };
            const valueAnnotation = makeValueAnnotation(dataTypeName, value);
            vaContainer.append(valueAnnotation);
        });
        // Handle "Set annotations" click.
        vaSetButton.on('click', function(e) {
            e.preventDefault();
            const values = {};
            vaContainer.find('.value-annotation').each(function() {
                const thisValueAnnotation = $(this);
                if (thisValueAnnotation.data('removed')) {
                    // This annotation was flagged for removal.
                    return;
                }
                const value = {};
                // Map the the data-value-key attributes to the values object.
                thisValueAnnotation.find(':input').each(function() {
                    const thisInput = $(this);
                    const valueKey = thisInput.data('valueKey');
                    if (!valueKey) return;
                    value[valueKey] = thisInput.val();
                });
                const propertyTerm = thisValueAnnotation.find('.property_term').val();
                if (!values.hasOwnProperty(propertyTerm)) {
                    values[propertyTerm] = [];
                }
                values[propertyTerm].push(value);
            });
            annotatingValue.data('valueAnnotations', values);
            Omeka.closeSidebar(vaSidebar);
        });
        // Handle "Remove value" click.
        $(document).on('click', '.value-annotation-remove', function(e) {
            e.preventDefault();
            const thisRemove = $(this);
            const valueAnnotation = thisRemove.closest('.value-annotation');
            valueAnnotation.data('removed', true); // Flag annotation for removal
            thisRemove.hide();
            valueAnnotation.find(':input').prop('disabled', true);
            valueAnnotation.find('.value').addClass('delete');
            valueAnnotation.find('.value-annotation-restore').show();
        });
        // Handle "Restore value" click.
        $(document).on('click', '.value-annotation-restore', function(e) {
            e.preventDefault();
            const thisRestore = $(this);
            const valueAnnotation = thisRestore.closest('.value-annotation');
            valueAnnotation.removeData('removed'); // Un-flag annotation for removal
            thisRestore.hide();
            valueAnnotation.find(':input').prop('disabled', false);
            valueAnnotation.find('.value').removeClass('delete');
            valueAnnotation.find('.value-annotation-remove').show();
        });
        $(document).on('click', '.value-annotation-resource-select', function(e) {
            e.preventDefault();
            const thisButton = $(this);
            const selectResourceSidebar = $('#select-resource');
            $('.selecting-resource').removeClass('selecting-resource');
            thisButton.closest('.value-annotation').addClass('selecting-resource');
            Omeka.populateSidebarContent(selectResourceSidebar, thisButton.data('sidebar-content-url'));
            Omeka.openSidebar(selectResourceSidebar);
        });
        // Handle value visibility click.
        $(document).on('click', '.value-annotation-visibility', function(e) {
            e.preventDefault();
            const thisVisibilityIcon = $(this);
            const isPublicInput = thisVisibilityIcon.closest('.value').find('input.is_public');
            isPublicInput.val(thisVisibilityIcon.hasClass('o-icon-public') ? 1 : 0);
        });

        // Select property
        $('#property-selector li.selector-child').on('click', function(e) {
            e.stopPropagation();
            var property = $(this);
            var term = property.data('property-term');
            var field = $('[data-property-term = "' + term + '"].field');
            if (!field.length) {
                field = makeNewField(property);
                field.addClass('user-added');
            }
            $('#property-selector').removeClass('mobile');
            Omeka.scrollTo(field);
        });

        $('#resource-template-select').on('change', function(e) {
            // Restore the original property label and comment.
            $('.alternate').remove();
            $('.field-label, .field-description').show();
            applyResourceTemplate(true);
        });


        $('#resource-values').on('click', 'a.value-language', function(e) {
            e.preventDefault();
            var languageButton = $(this);
            var languageInput = languageButton.next('input.value-language');
            languageButton.toggleClass('active');
            languageInput.toggleClass('active');
            if (languageInput.hasClass('active')) {
                languageInput.focus();
            }
        });

        $('input.value-language').on('keyup, change', function(e) {
            if ('' === this.value || Omeka.langIsValid(this.value)) {
                this.setCustomValidity('');
            } else {
                this.setCustomValidity(Omeka.jsTranslate('Please enter a valid language tag'));
            }
        });

        $('.o-icon-more').on('click', function(e) {
            e.preventDefault();
            $(this).parent('.more-actions').toggleClass('active');
        });

        // Make new value inputs whenever "add value" button clicked.
        $('#properties').on('click', '.add-value', function(e) {
            e.preventDefault();
            var typeButton = $(this);
            var field = typeButton.closest('.resource-values.field');
            var value = makeNewValue(field.data('property-term'), typeButton.data('type'));
            field.find('.values').append(value);
        });

        // Remove value.
        $('a.remove-value').on('click', function(e) {
            e.preventDefault();
            var thisButton = $(this);
            var value = thisButton.closest('.value');
            // Disable all form controls.
            value.find(':input').prop('disabled', true);
            value.addClass('delete');
            value.find('a.restore-value').show().focus();
            thisButton.hide();
        });

        // Restore a removed value
        $('a.restore-value').on('click', function(e) {
            e.preventDefault();
            var thisButton = $(this);
            var value = thisButton.closest('.value');
            // Enable all form controls.
            value.find('*').filter(':input').prop('disabled', false);
            value.removeClass('delete');
            value.find('a.remove-value').show().focus();
            thisButton.hide();
        });

        // Open or close item set
        $('a.o-icon-lock, a.o-icon-unlock').click(function(e) {
            e.preventDefault();
            var isOpenIcon = $(this);
            $(this).toggleClass('o-icon-lock').toggleClass('o-icon-unlock');
            var isOpenHiddenValue = $('input[name="o:is_open"]');
            if (isOpenHiddenValue.val() == 0) {
                isOpenIcon.attr('aria-label', Omeka.jsTranslate('Close item set'));
                isOpenIcon.attr('title', Omeka.jsTranslate('Close item set'));
                isOpenHiddenValue.attr('value', 1);
            } else {
                isOpenHiddenValue.attr('value', 0);
                isOpenIcon.attr('aria-label', Omeka.jsTranslate('Open item set'));
                isOpenIcon.attr('title', Omeka.jsTranslate('Open item set'));
            }
        });

        $('#select-item a').on('o:resource-selected', function (e) {
            var valueObj = $('.resource-details').data('resource-values');
            var value = $('.value.selecting-resource');
            if (value.hasClass('value')) {
                const dataTypeNames = {items: 'resource:item', item_sets: 'resource:itemset', media: 'resource:media'};
                const dataTypeName = dataTypeNames[valueObj.value_resource_name] ? dataTypeNames[valueObj.value_resource_name] : 'resource';
                $(document).trigger('o:prepare-value', [dataTypeName, value, valueObj]);
            } else if (value.hasClass('value-annotation')) {
                const dataTypeName = value.find('input.data_type').val();
                valueObj.type = dataTypeName;
                valueObj.is_public = value.find('input.is_public').val();
                valueObj.property_id = value.find('input.property_id').val();
                valueObj.property_term = value.find('input.property_term').val();
                hydrateValueAnnotation(value, valueObj);
                $(document).trigger('o:prepare-value-annotation', [dataTypeName, value, valueObj]);
            }
            Omeka.closeSidebar($('#select-resource'));
        });

        // Prevent resource details from opening when quick add is toggled on.
        $('#select-resource').on('click', '.quick-select-toggle', function() {
            $('#item-results').find('a.select-resource').each(function() {
                $(this).toggleClass('sidebar-content');
            });
        });

        $('#select-resource').on('o:resources-selected', '.select-resources-button', function(e) {
            var value = $('.value.selecting-resource');
            if (value.hasClass('value')) {
                var field = value.closest('.resource-values.field');
                $('#item-results').find('.resource')
                    .has('input.select-resource-checkbox:checked').each(function(index) {
                        var valueObj = $(this).data('resource-values');
                        const dataTypeNames = {items: 'resource:item', item_sets: 'resource:itemset', media: 'resource:media'};
                        const dataTypeName = dataTypeNames[valueObj.value_resource_name] ? dataTypeNames[valueObj.value_resource_name] : 'resource';
                        if (0 < index) {
                            value = makeNewValue(field.data('property-term'), dataTypeName);
                            field.find('.values').append(value);
                        }
                        $(document).trigger('o:prepare-value', [dataTypeName, value, valueObj]);
                    });
            } else if (value.hasClass('value-annotation')) {
                const dataTypeName = value.find('input.data_type').val();
                $('#item-results').find('.resource').has('input.select-resource-checkbox:checked').each(function(index) {
                    const valueObj = $(this).data('resource-values');
                    valueObj.type = dataTypeName;
                    valueObj.is_public = value.find('input.is_public').val();
                    valueObj.property_id = value.find('input.property_id').val();
                    valueObj.property_term = value.find('input.property_term').val();
                    if (0 === index) {
                        hydrateValueAnnotation(value, valueObj);
                        $(document).trigger('o:prepare-value-annotation', [dataTypeName, value, valueObj]);
                    } else {
                        newValue = makeValueAnnotation(dataTypeName, valueObj);
                        value.after(newValue);
                    }
                });
            }
        });

        $('.button.resource-select').on('click', function(e) {
            e.preventDefault();
            var selectButton = $(this);
            var sidebar = $('#select-resource');
            var term = selectButton.closest('.resource-values').data('property-term');
            $('.selecting-resource').removeClass('selecting-resource');
            selectButton.closest('.value').addClass('selecting-resource');
            $('#select-item a').data('property-term', term);

            // Copy template property data in sidebar to be able to respond to sidebar events.
            const templatePropertyData = selectButton.closest('.resource-values').data('template-property-data');
            sidebar.data('term', term);
            sidebar.data('template-property-data', templatePropertyData);

            // Manage first filtered search query if set in resource template property.
            // Next queries will use it automaticaly (see sidebar-select.phtml).
            const resourceQuery = typeof templatePropertyData === 'object' ? templatePropertyData.resource_query : null;

            Omeka.populateSidebarContent(
                sidebar,
                selectButton.data('sidebar-content-url'),
                resourceQuery
            );
            Omeka.openSidebar(sidebar);
        });

        $('.visibility [type="checkbox"]').on('click', function() {
            var publicCheck = $(this);
            if (publicCheck.prop("checked")) {
                publicCheck.attr('checked','checked');
            } else {
                publicCheck.removeAttr('checked');
            }
        });

        // Handle validation for required metadata, included properties.
        $('form.resource-form').on('submit', function(e) {

            var thisForm = $(this);
            var errors = [];

            // Manage value suggest options from Advanced resource template.
            // TODO Add an event before submit.
            const templateData = $('#resource-values').data('template-data');
            $('#properties .resource-values.field').find('.values > .value[data-data-type^="valuesuggest"]').each(function () {
                const value = $(this);
                const field = value.closest('.resource-values.field');
                const rtpData = field.data('template-property-data')
                    ? field.data('template-property-data')
                    : { value_suggest_keep_original_label: 'no', value_suggest_require_uri: 'no' };
                const valueSuggestKeepOriginalLabel = rtpData.value_suggest_keep_original_label === 'yes'
                    || (rtpData.value_suggest_keep_original_label !== 'no' && templateData.value_suggest_keep_original_label == 'yes');
                if (valueSuggestKeepOriginalLabel) {
                    value.find(':input[data-value-key="o:label"]')
                        // .val(value.find(':input[data-value-key="@value"]').val())
                        .val(value.find('.valuesuggest-input').prop('placeholder'))
                        .prop('disabled', false);
                }
                const valueSuggestRequireUri = rtpData.value_suggest_require_uri === 'yes'
                    || (rtpData.value_suggest_require_uri !== 'no' && templateData.value_suggest_require_uri == 'yes');
                if (valueSuggestRequireUri) {
                    value.find(':input[data-value-key="@id"]')
                        .prop('disabled', false);
                }
            });

            // Check for a required resource class.
            var resourceClassSelect = $('#resource-values #resource-class-select');
            var resourceClassId = Number(resourceClassSelect.val());
            if (!resourceClassId && resourceClassSelect.attr('require')) {
                errors.push('The template requires a resource class.');
            }

            // Iterate all required properties.
            var requiredProps = thisForm.find('.resource-values.required');
            requiredProps.each(function() {

                var thisProp = $(this);
                var propIsCompleted = false;

                // Iterate all values for this required property.
                var requiredValues = $(this).find('.value').not('.delete');
                requiredValues.each(function() {

                    var thisValue = $(this);
                    var valueIsCompleted = true;

                    // All inputs of this value with the "to-require" class must
                    // be completed when the property is required.
                    var toRequire = thisValue.find('.to-require');
                    toRequire.each(function() {
                        if ('' === $.trim($(this).val())) {
                            // Found an incomplete input.
                            valueIsCompleted = false;
                        }
                    });
                    if (valueIsCompleted) {
                        // There's at least one completed value of this required
                        // property. Consider the requirement satisfied.
                        propIsCompleted = true;
                        return false; // break out of each
                    }
                });
                if (!propIsCompleted) {
                    // No completed values found for this required property.
                    var propLabel = thisProp.find('.field-label').text();
                    errors.push('The following field is required: ' + propLabel);
                }
            });
            thisForm.data('has-error', errors.length > 0);
            if (errors.length) {
                e.preventDefault();
                alert(errors.join("\n"));
            }

            $('#values-json').val(JSON.stringify(collectValues()));
        });

        Omeka.initializeSelector('#item-sites', '#site-selector');
        Omeka.initializeSelector('#item-item-sets', '#item-set-selector');

        initPage();
    });

    var collectValues = function () {
        var values = {};
        $('#properties').children().each(function () {
            var propertyValues = [];
            var property = $(this);
            var propertyTerm = property.data('propertyTerm');
            var propertyId = property.data('propertyId');
            property.find('.values > .value').each(function () {
                var valueData = {}
                var value = $(this);
                if (value.hasClass('delete')) {
                    return;
                }
                valueData['property_id'] = propertyId;
                valueData['type'] = value.data('dataType');
                valueData['is_public'] = value.find('input.is_public').val();
                valueData['@annotation'] = value.data('valueAnnotations');
                value.find(':input[data-value-key]').each(function () {
                    var input = $(this);
                    var valueKey = input.data('valueKey');
                    if (!valueKey || input.prop('disabled')) {
                        return;
                    }
                    valueData[valueKey] = input.val();
                });
                propertyValues.push(valueData);
            });
            if (propertyValues.length) {
                values[propertyTerm] = values.hasOwnProperty(propertyTerm)
                    ? values[propertyTerm].concat(propertyValues)
                    : propertyValues;
            }
        });
        return values;
    };

    var makeDefaultValue = function (term, dataType) {
        return makeNewValue(term, dataType)
            .addClass('default-value')
            .one('change', '*', function (event) {
                $(event.delegateTarget).removeClass('default-value');
            });
    };

    /**
     * Make a new value.
     */
    var makeNewValue = function(term, dataType, valueObj) {
        var field = $('.resource-values.field[data-property-term="' + term + '"]');
        // Get the value node from the templates.
        if (!dataType || typeof dataType !== 'string') {
            dataType = valueObj ? valueObj['type'] : field.find('.add-value:visible:first').data('type');
        }
        var fieldForDataType = field.filter(function() { return $.inArray(dataType, $(this).data('data-types').split(',')) > -1; });
        field = fieldForDataType.length ? fieldForDataType.first() : field.first();
        var value = $('.value.template[data-data-type="' + dataType + '"]').clone(true);
        value.removeClass('template');
        value.attr('data-term', term);
        value.data('valueAnnotations', valueObj ? valueObj['@annotation'] : null);

        // Get and display the value's visibility.
        var isPublic = true; // values are public by default
        if (field.hasClass('private') || (valueObj && false === valueObj['is_public'])) {
            isPublic = false;
        }
        var valueVisibilityButton = value.find('a.value-visibility');
        if (isPublic) {
            valueVisibilityButton.removeClass('o-icon-private').addClass('o-icon-public');
            valueVisibilityButton.attr('aria-label', Omeka.jsTranslate('Make private'));
            valueVisibilityButton.attr('title', Omeka.jsTranslate('Make private'));
        } else {
            valueVisibilityButton.removeClass('o-icon-public').addClass('o-icon-private');
            valueVisibilityButton.attr('aria-label', Omeka.jsTranslate('Make public'));
            valueVisibilityButton.attr('title', Omeka.jsTranslate('Make public'));
        }

        // Prepare the value node.
        var valueLabelID = 'property-' + field.data('property-id') + '-label';
        value.find('input.is_public')
            .val(isPublic ? 1 : 0);
        value.find('span.label')
            .attr('id', valueLabelID);
        value.find('textarea.input-value')
            .attr('aria-labelledby', valueLabelID);
        value.attr('aria-labelledby', valueLabelID);
        $(document).trigger('o:prepare-value', [dataType, value, valueObj]);
        return value;
    };

    /**
     * Prepare the markup for the default data types.
     */
    $(document).on('o:prepare-value', function(e, dataType, value, valueObj) {
        // Prepare simple single-value form inputs using data-value-key
        value.find(':input').each(function () {
            var valueKey = $(this).data('valueKey');
            if (!valueKey) {
                return;
            }
            $(this).removeAttr('name')
                .val(valueObj ? valueObj[valueKey] : null);
        });

        // Prepare the markup for the resource data types.
        var resourceDataTypes = [
            'resource',
            'resource:item',
            'resource:itemset',
            'resource:media',
        ];
        if (valueObj && -1 !== resourceDataTypes.indexOf(dataType)) {
            value.find('span.default').hide();
            var resource = value.find('.selected-resource');
            if (typeof valueObj['display_title'] === 'undefined') {
                valueObj['display_title'] = Omeka.jsTranslate('[Untitled]');
            }
            resource.find('.o-title')
                .removeClass() // remove all classes
                .addClass('o-title ' + valueObj['value_resource_name'])
                .html($('<a>', {href: valueObj['url'], text: valueObj['display_title']}));
            if (typeof valueObj['thumbnail_url'] !== 'undefined') {
                resource.find('.o-title')
                    .prepend($('<img>', {src: valueObj['thumbnail_url']}));
            }
        }
    });

    /**
     * Make a new property field with data stored in the property selector.
     */
    var makeNewField = function(property, dataTypes) {
        // Prepare data type name of the field.
        if (!dataTypes || dataTypes.length < 1) {
            dataTypes = $('div#properties').data('default-data-types').split(',');
        }

        // Sort out whether property is the LI that holds data, or the id.
        var propertyLi, propertyId;
        switch (typeof property) {
            case 'object':
                propertyLi = property;
                propertyId = propertyLi.data('property-id');
            break;

            case 'number':
                propertyId = property;
                propertyLi = $('#property-selector').find("li[data-property-id='" + propertyId + "']");
            break;

            case 'string':
                propertyLi = $('#property-selector').find("li[data-property-term='" + property + "']");
                propertyId = propertyLi.data('property-id');
            break;

            default:
                return null;
        }

        var term = propertyLi.data('property-term');
        var field = $('.resource-values.field.template').clone(true);
        field.removeClass('template');
        field.find('.field-label').text(propertyLi.data('child-search')).attr('id', 'property-' + propertyId + '-label');
        field.find('.field-term').text(term);
        field.find('.field-description').prepend(propertyLi.find('.field-comment').text());
        field.data('property-term', term);
        field.data('property-id', propertyId);
        field.data('data-types', dataTypes.join(','));
        // Adding the attr because selectors need them to find the correct field
        // and count when adding more.
        field.attr('data-property-term', term);
        field.attr('data-property-id', propertyId);
        field.attr('data-data-types', dataTypes.join(','));
        field.attr('aria-labelledby', 'property-' + propertyId + '-label');
        $('div#properties').append(field);

        new Sortable(field.find('.values')[0], {
            draggable: '.value',
            handle: '.sortable-handle'
        });

        field.trigger('o:property-added');
        return field;
    };

    /**
     * Rewrite an existing property field, or create a new one, following the
     * rules defined by the selected resource property template.
     */
    var rewritePropertyField = function(templateProperty) {
        var templateId = $('#resource-template-select').val();
        var properties = $('div#properties');
        var propertyId = templateProperty['o:property']['o:id'];
        var dataTypes = templateProperty['o:data_type'] && templateProperty['o:data_type'].length
            ? templateProperty['o:data_type']
            : $('div#properties').data('default-data-types').split(',');

        // Check if an existing field exists in order to update it and to avoid duplication.
        // Since fields can have the same property but only different data types, a filter is used.
        var field = properties.find('[data-property-id="' + propertyId + '"]')
            .filter(function() { return dataTypes.sort().join(',') === $(this).data('data-types').split(',').sort().join(','); })
            .first().data('data-types', dataTypes.join(','));
        if (!field.length) {
            field = makeNewField(propertyId, dataTypes);
        }

        var originalLabel = field.find('.field-label');
        var originalDescription = field.find('.field-description');
        var defaultSelector = field.find('div.default-selector');
        var multipleSelector = field.find('div.multiple-selector');
        var singleSelector = field.find('div.single-selector');

        if (templateProperty['o:is_required']) {
            field.addClass('required');
        }
        if (templateProperty['o:is_private']) {
            field.addClass('private');
        }
        if (templateProperty['o:alternate_label']) {
            var altLabel = originalLabel.clone();
            var altLabelId = 'property-' + propertyId + '-' + dataTypes.join('-') + '-label';
            altLabel.addClass('alternate');
            altLabel.text(templateProperty['o:alternate_label']);
            altLabel.insertAfter(originalLabel);
            altLabel.attr('id', altLabelId);
            field.attr('aria-labelledby', altLabelId);
            originalLabel.hide();
        }
        if (templateProperty['o:alternate_comment']) {
            var altDescription = originalDescription.clone();
            altDescription.addClass('alternate');
            altDescription.text(templateProperty['o:alternate_comment']);
            altDescription.insertAfter(originalDescription);
            originalDescription.hide();
        }

        // Store specific data of this template property.
        field.attr('data-template-id', templateId);
        field.data('template-property-data', templateProperty['o:data'] ? templateProperty['o:data'] : {});

        // Remove any unchanged default values for this property so we start fresh.
        field.find('.value.default-value').remove();

        // Change value selector (multiple, single, or default) and add empty value if needed.
        var selector = 'default';
        if (templateProperty['o:data_type'].length > 1) {
            selector = 'multiple';
            defaultSelector.hide();
            singleSelector.hide();
            if (!multipleSelector.find('.add-value').length) {
                multipleSelector.append(prepareMultipleSelector(templateProperty['o:data_type']));
            }
            multipleSelector.show();
        } else if (templateProperty['o:data_type'].length === 1) {
            selector = 'single';
            defaultSelector.hide();
            multipleSelector.hide();
            singleSelector.find('a.add-value.button').data('type', templateProperty['o:data_type'][0]);
            singleSelector.show();
        } else {
            multipleSelector.hide();
            singleSelector.hide();
            defaultSelector.show();
        }

        // Unlike omeka js, the empty first value is added during template preparation.

        field.data('selector', selector);

        properties.prepend(field);
    };

    var makeDefaultTemplate = function() {
        var defaultDataType = $('div#properties').data('default-data-types').substring(0, ($('div#properties').data('default-data-types') + ',').indexOf(','));
        makeNewField('dcterms:title').find('.values')
            .append(makeDefaultValue('dcterms:title', defaultDataType));
        makeNewField('dcterms:description').find('.values')
            .append(makeDefaultValue('dcterms:description', defaultDataType));
    };

    /**
     * Prepare a selector (usually a html list of buttons) from a list of data types.
     *
     * @param array dataTypes
     * @return string
     */
    var prepareMultipleSelector = function(dataTypes) {
        var html = '';
        dataTypes.forEach(function(dataType) {
            var dataTypeTemplate = $('.template.value[data-data-type="' + dataType + '"]');
            var label = dataTypeTemplate.data('data-type-label') ? dataTypeTemplate.data('data-type-label') : Omeka.jsTranslate('Add value');
            var icon = dataTypeTemplate.data('data-type-icon') ? dataTypeTemplate.data('data-type-icon') : dataType.substring(0, (dataType + ':').indexOf(':'));
            html += dataTypeTemplate.data('data-type-button')
                ? dataTypeTemplate.data('data-type-button') + ' '
                : '<a href="#" class="add-value button o-icon-' + icon + '" data-type="' + dataType + '">' + label + '</a> ';
        });
        return html;
    };

    /**
     * Apply the selected resource template to the form.
     *
     * @param bool changeClass Whether to change the suggested class on new resource.
     *   Also, the class may be changed if the new resource template requires a specific class.
     */
    var applyResourceTemplate = function(changeClass) {
        var templateSelect = $('#resource-template-select');
        var templateId = templateSelect.val();
        var fields = $('#properties .resource-values');

        // Reset data of the previous template.
        $('#resource-values').data('template-data', {});
        fields.attr('data-template-id', '');
        fields.data('template-property-data', {});

        // Fieldsets may have been marked as required or private in a previous state.
        fields.removeClass('required');
        fields.removeClass('private');

        // Reset all properties to the default selector.
        fields.find('div.multiple-selector').hide();
        fields.find('div.single-selector').hide();
        fields.find('div.default-selector').show();

        // All properties should uses the default data types.
        fields.data('data-types', $('div#properties').data('default-data-types'));
        fields.attr('data-data-types', $('div#properties').data('default-data-types'));

        // Merge all duplicate properties, keeping order of values when possible.
        fields.each(function() {
            var propertyId = $(this).attr('data-property-id');
            // Deduplicate only first properties, that are not already processed.
            if ($(this).prevAll('[data-property-id="' + propertyId + '"]').length < 1) {
                var duplicatedFields = $('div#properties').find('[data-property-id="' + propertyId + '"]');
                var duplicatedFieldFirst = duplicatedFields.first();
                duplicatedFields.each(function(index) {
                    if (index > 0) {
                        duplicatedFieldFirst.find('.inputs .values').append($(this).find('.inputs .values > .value'));
                        $(this).remove();
                    }
                });
            }
        });

        if (templateId) {
            var url = templateSelect.data('api-base-url') + '/' + templateId;
            $.get(url)
                .done(function(data) {
                    // Store global data of the template,
                    // included the resource class for non advanced templates..
                    var templateData = data['o:data'] ? data['o:data'] : {};
                    templateData['o:resource_class'] = data['o:resource_class'];
                    $('#resource-values').data('template-data', templateData);

                    if (changeClass) {
                        // Change the resource class.
                        var classSelect = $('#resource-class-select');
                        if (data['o:resource_class'] && classSelect.val() === '') {
                            classSelect.val(data['o:resource_class']['o:id']);
                            classSelect.trigger('chosen:updated');
                        }
                    }

                    data = prepareTemplateDataBefore(data);

                    // Rewrite every property field defined by the template. We
                    // reverse the order so property fields on page that are not
                    // defined by the template are ultimately appended.
                    data['o:resource_template_property']
                        .reverse().map(function(templateProperty) {
                            rewritePropertyField(templateProperty);
                        });

                    prepareFieldsAfter();
                })
                .fail(function() {
                    console.log('Failed loading resource template from API');
                })
                .always(finalize);
        } else {
            finalize();
        }

        function finalize() {
            // Remove empty fields, except the templates and user added ones, to avoid mix of templates fields.
            fields = templateId ? $('#properties .resource-values[data-template-id!="' + templateId + '"]') : $('#properties .resource-values');
            fields.not('.user-added').each(function() {
                if ($(this).find('.inputs .values > .value').length === $(this).find('.inputs .values > .value.default-value').length) {
                    $(this).remove();
                }
            });

            // Add default fields if none.
            if (!$('#properties .resource-values').length) {
                makeDefaultTemplate();
            }

            // Add a default empty value if none already exist in the property.
            fields = $('#properties .resource-values');
            fields.each(function(index, field) {
                field = $(field);
                if (!field.find('.value').length) {
                    field.find('.inputs .values').append(
                        makeDefaultValue(field.data('property-term'), field.find('.add-value:visible:first').data('type'))
                    );
                }
            });

            fields.find('input.value-language').each(initValueLanguage);

            $('#properties').closest('form').trigger('o:template-applied');
        };
    }

    var initValueLanguage = function() {
        var languageInput = $(this);
        if (languageInput.val() !== '') {
            languageInput.addClass('active');
            languageInput.prev('a.value-language').addClass('active');
        }
    }

    /**
     * Initialize the page.
     */
    var initPage = function() {
        // Prepare the form with values if any, else an empty template will be displayed.
        if (typeof valuesJson !== 'undefined') {
            $.each(valuesJson, function(term, valueObj) {
                var field = makeNewField(term);
                $.each(valueObj.values, function(index, value) {
                    field.find('.values').append(makeNewValue(term, null, value));
                });
            });
        }

        // Adapt the form for the template, if any.
        // The class is set for a new item (default omeka), or if the new template
        // requires a class (option specific to the module Advanced Resource Template).
        var applyTemplateClass = $('body').hasClass('add');
        $.when(applyResourceTemplate(applyTemplateClass))
            .done(function () {
                $('#properties').closest('form').trigger('o:form-loaded');
            });
    };

    var prepareTemplateDataBefore = function(data) {
        // Divide all template properties with the same property according to data,
        // and do some cleaning.
        // @see view/omeka/admin/resource-template/show.phtml in the module.
        var rtps = [];

        // But first, manage an exception: remove the data types of the default template
        // property that are used in other fields. When all its data types are already
        // managed by other fields, the default field is removed.
        data['o:resource_template_property'].forEach(function(rtp, index) {
            var dataTypesForProperty = [];
            // First, list all specified data types (skip properties with only one data).
            (rtp['o:data'] && rtp['o:data'].length > 1 ? rtp['o:data'] : []).forEach(function(rtpData) {
                var isDefaultField = !rtpData['o:data_type'] || !rtpData['o:data_type'].length;
                if (!isDefaultField) {
                    dataTypesForProperty = dataTypesForProperty.concat(rtpData['o:data_type']);
                }
            });
            // Second, remove data types in the default field.
            (rtp['o:data'] && rtp['o:data'].length > 1 ? rtp['o:data'] : []).forEach(function(rtpData, indexData) {
                var isDefaultField = !rtpData['o:data_type'] || !rtpData['o:data_type'].length;
                if (isDefaultField) {
                    var dataTypes = $('div#properties').data('default-data-types').split(',').filter(function(value) {
                        return dataTypesForProperty.indexOf(value) < 0;
                    });
                    if (dataTypes.length) {
                        data['o:resource_template_property'][index]['o:data'][indexData]['o:data_type'] = dataTypes;
                    } else {
                        delete data['o:resource_template_property'][index]['o:data'][indexData];
                    }
                }
            });
        });

        data['o:resource_template_property'].forEach(function(rtp) {
            (rtp['o:data'] && rtp['o:data'].length > 0 ? rtp['o:data'] : [{}]).forEach(function(rtpData) {
                // Use only core template property keys.
                var rtpd = {}
                rtpd['o:property'] = rtp['o:property'];
                rtpd['o:alternate_label'] = rtpData['o:alternate_label'] == '' ? null : rtpData['o:alternate_label'];
                rtpd['o:alternate_comment'] = rtpData['o:alternate_comment'] == '' ? null : rtpData['o:alternate_comment'];
                rtpd['o:is_private'] = rtpData['o:is_private'] === true || rtpData['o:is_private'] == '1';
                rtpd['o:is_required'] = rtpData['o:is_required'] === true || rtpData['o:is_required'] == '1';
                rtpd['o:data_type'] = rtpData['o:data_type'] ? rtpData['o:data_type'] : [];
                // Remove core property keys in data.
                Object.keys(rtp).forEach(function (key) {
                    delete rtpData[key];
                });
                rtpd['o:data'] = rtpData;
                rtps.push(rtpd);
            });
        });
        data['o:resource_template_property'] = rtps;
        return data;
    }

    var prepareFieldsAfter = function() {
        // Furthermore, the values are moved to the property row according to their
        // data type when there are template properties with the same property.
        fields = $('#properties .resource-values');
        if (fields.length > 0) {
            // Prepare the list of data types one time and make easier to fill specific rows first.
            var dataTypesByProperty = {};
            var checkDefaultDataType = $('div#properties').data('default-data-types').split(',').sort().join(',');
            fields.each(function() {
                var fieldTemplateId = $(this).data('template-id');
                var propertyId = $(this).data('property-id');
                var dataTypes = $(this).data('data-types');
                if (!dataTypesByProperty.hasOwnProperty(propertyId)) {
                    dataTypesByProperty[propertyId] = {};
                }
                // Skip field with default data types.
                var isDefaultField = dataTypes.split(',').sort().join(',') === checkDefaultDataType;
                if (!isDefaultField && fieldTemplateId && dataTypes.split(',').length) {
                    dataTypes.split(',').forEach(function(dataType) {
                        // Manage exception for resource values.
                        if (dataType === 'resource') {
                            dataTypesByProperty[propertyId]['resource'] = dataTypes;
                            dataTypesByProperty[propertyId]['resource:item'] = dataTypes;
                            dataTypesByProperty[propertyId]['resource:itemset'] = dataTypes;
                            dataTypesByProperty[propertyId]['resource:media'] = dataTypes;
                        } else {
                            dataTypesByProperty[propertyId][dataType] = dataTypes;
                        }
                    });
                } else {
                    dataTypesByProperty[propertyId]['default'] = $('div#properties').data('default-data-types');
                }
            });
            fields.each(function() {
                var propertyId = $(this).data('property-id');
                $(this).find('.inputs .values > .value').each(function() {
                    var valueDataType = $(this).data('data-type');
                    if (!dataTypesByProperty[propertyId].hasOwnProperty(valueDataType)) {
                        if (!dataTypesByProperty[propertyId].hasOwnProperty('default')) {
                            return;
                        }
                        valueDataType = 'default';
                    }
                    fields
                        .filter('[data-property-id="' + propertyId + '"][data-data-types="' + dataTypesByProperty[propertyId][valueDataType] + '"]')
                        .find('.inputs .values')
                        .append($(this));
                });
            });
        }
    }

})(jQuery);
