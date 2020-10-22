$(document).ready(function() {

    /**
     * Prepare the lock for a original values of a property.
     */
    function prepareFieldLocked(field) {
        var rtpData = field.data('template-property-data') ? field.data('template-property-data') : {};
        if (rtpData.locked_value != true) {
            return;
        }

        // Some weird selectors are needed to manage all cases.
        field.find('.inputs .values .value:not(.default-value) .input-body').find('input, select, textarea').addClass('original-value');
        var originalValues = field.find('.input-body .original-value');
        originalValues
            .prop('readonly', 'readonly')
            .attr('readonly', 'readonly');
        originalValues.closest('.input-body').find('.o-icon-close').remove();
        originalValues.closest('.input-body').find('.button.resource-select').remove();
        originalValues.closest('div.value').find('.input-footer .remove-value').remove();

        // Disable some field is required separately: it can be still changed (numeric data type).
        originalValues.filter('[type=checkbox]:not([data-value-key])').attr('disabled', true);
        originalValues.filter('[type=radio]:not([data-value-key])').attr('disabled', true);
        originalValues.filter('select:not([data-value-key])').attr('disabled', true).trigger('chosen:updated');
        // Manage custom vocab with chosen.
        originalValues.filter('select[data-value-key]').each(function() {
            $(this).find('option[value!="' + $(this).val() + '"]').remove()
                .end().chosen('destroy');
        });
    }

    /**
     * Prepare the autocompletion for a property.
     */
    function prepareFieldAutocomplete(field) {
        var templateData = $('#resource-values').data('template-data') ;
        var rtpData = field.data('template-property-data') ? field.data('template-property-data') : {};

        // Reset autocomplete for all properties.
        $('.inputs .values textarea.input-value').prop('autocomplete', 'off');
        field.removeData('autocomplete');
        field.find('.inputs .values textarea.input-value.autocomplete').each(function() {
            var autocomp = $(this).autocomplete();
            if (autocomp) {
                autocomp.dispose();
            }
        });
        field.find('.inputs .values textarea.input-value').prop('autocomplete', 'off').removeClass('autocomplete');

        var autocomplete = templateData.autocomplete ? templateData.autocomplete : 'no';
        autocomplete = rtpData.autocomplete && $.inArray(rtpData.autocomplete, ['no', 'sw', 'in'])
            ? rtpData.autocomplete
            : autocomplete;
        if (autocomplete === 'sw' || autocomplete === 'in') {
            field.data('autocomplete', autocomplete);
            field.find('.inputs .values textarea.input-value').addClass('autocomplete');
            field.find('.inputs .values textarea.input-value.autocomplete').each(initAutocomplete);
        }
    }

    /**
     * Prepare the language for a property.
     */
    function prepareFieldLanguage(field) {
        // Add a specific datalist for the property. It replaces the previous one from another template.
        var templateData = $('#resource-values').data('template-data') ;
        var rtpData = field.data('template-property-data') ? field.data('template-property-data') : {};
        var listName = 'value-languages';
        var term = field.data('property-term');

        var datalist = $('#value-languages-template');
        if (datalist.length) {
            datalist.empty();
        } else {
            $('#value-languages').after('<datalist id="value-languages-template" class="value-languages"></datalist>');
            datalist = $('#value-languages-template');
        }
        if (templateData.value_languages && !$.isEmptyObject(templateData.value_languages)) {
            listName = 'value-languages-template';
            $.each(templateData.value_languages, function(code, label) {
                datalist.append($('<option>', { value: code, label: label.length ? label : code }));
            });
        }

        datalist = field.find('.values ~ datalist.value-languages');
        if (datalist.length) {
            datalist.empty();
        } else {
            field.find('.values').first().after('<datalist class="value-languages"></datalist>');
            datalist = field.find('.values ~ datalist.value-languages');
            datalist.attr('id', 'value-languages-' + term);
        }
        if (rtpData.value_languages && !$.isEmptyObject(rtpData.value_languages)) {
            listName = 'value-languages-' + term;
            $.each(rtpData.value_languages, function(code, label) {
                datalist.append($('<option>', { value: code, label: label.length ? label : code }));
            });
        }

        // Use the main datalist, or the template one, or the property one.
        var inputLanguage = field.find('.values input.value-language');
        inputLanguage.attr('list', listName);

        var noLanguage = !!(rtpData.use_language
            && (rtpData.use_language === 'no' || (rtpData.use_language !== 'yes' && templateData.no_language)));
        field.data('no-language', noLanguage);
        field.find('.inputs .values input.value-language').each(function() {
            initValueLanguage($(this), field);
        });
    }

    /**
     * Init the language input.
     */
    function initValueLanguage(languageInput, field) {
        var languageElement;
        var languageButton = languageInput.prev('a.value-language');
        var language = languageInput.val();
        if (field.data('no-language') == true) {
            language = '';
            languageButton.removeClass('active').addClass('no-language');
            languageInput.prop('disabled', true).removeClass('active');
        } else {
            languageButton.removeClass('no-language');
            languageInput.prop('disabled', false);
            languageElement = languageInput;
        }
        if (language !== '') {
            languageButton.addClass('active');
            languageElement.addClass('active');
        }
    }

    /**
     * Fill the default language.
     */
    function fillDefaultLanguage(value, valueObj, field) {
        value.find('input.value-language').each(function() {
            initValueLanguage($(this), field);
        });

        if (valueObj) {
            return;
        }
        if (field.data('no-language') == true) {
            return;
        }

        var templateData = $('#resource-values').data('template-data');
        var rtpData = field.data('template-property-data') ? field.data('template-property-data') : {};
        var defaultLanguage = templateData.default_language && templateData.default_language.length
            ? templateData.default_language
            : '';
        defaultLanguage = rtpData.default_language && rtpData.default_language.length
            ? rtpData.default_language
            : defaultLanguage;
        if (defaultLanguage.length) {
            value.find('input.value-language').val(defaultLanguage).addClass('active');
            value.find('a.value-language').addClass('active');
        }
    }

    /**
     * Make a new property field with data stored in the property selector.
     *
     * Copy of resource-form.js, not available here, except the trigger.
     * @see resource-form.js makeNewField()
     */
    var makeNewField = function(property, dataTypes) {
        // Prepare data type name of the field.
        if (!dataTypes || dataTypes.length < 1) {
            dataTypes = $('#properties').data('default-data-types').split(',');
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

        // field.trigger('o:property-added');
        return field;
    };

    /**
     * Make a new value.
     *
     * Copy of resource-form.js, not available here.
     * @see resource-form.js makeNewValue()
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
        value.data('term', term);

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

        valueObj = fixValueForDataType(valueObj, dataType);

        $(document).trigger('o:prepare-value', [dataType, value, valueObj]);

        return value;
    };

    /**
     * Fixes some values for some data types.
     *
     * Because resource-form.js listens event first, some fixes should be done
     * for some data types, for example for a timestamp with an incomplete date,
     * that can't' be managed by currently implemented twig features.
     *
     * @todo Implement missing twig features ("if empty", etc.).
     */
    function fixValueForDataType(valueObj, dataType) {
        if (!valueObj || !Object.keys(valueObj).length) {
            return valueObj;
        }
        if (dataType === 'numeric:timestamp') {
            // Remove missing parts of the end of the date.
            valueObj['@value'] = valueObj['@value'].replace(/[\s-]+$/g, '');
        }
        return valueObj;
    }

    /**
     * Fill a new value, that can be empty.
     *
     * @see makeNewValue() in resource-form.js: the same, except that the empty
     * value is already created in previous hook and may be filled.
     */
    function fillValue(value, term, dataType, valueObj) {
        // If defaultValue is undefined, it means that valueObj is filled.
        var v;
        var defaultValue = valueObj.default;
        var isSpecific = typeof defaultValue !== 'undefined';
        // Manage specific data type "resource".
        if (dataType.startsWith('resource')) {
            if (isSpecific && /^\d+$/.test(defaultValue)) {
                valueObj = {
                    display_title: Omeka.jsTranslate('Resource') + ' #' + defaultValue,
                    value_resource_id: defaultValue,
                    value_resource_name: 'resource',
                    url: '#',
                };
            }
            value.find('input.value[data-value-key="value_resource_id"]').val(valueObj['value_resource_id']);
            // TODO Get the title from the api (when authentication will be opened).
            value.find('span.default').hide();
            var resource = value.find('.selected-resource');
            resource.find('.o-title')
                .removeClass() // remove all classes
                .addClass('o-title ' + valueObj['value_resource_name'])
                .html($('<a>', {href: valueObj['url'], text: valueObj['display_title']}));
            if (typeof valueObj['thumbnail_url'] !== 'undefined') {
                resource.find('.o-title')
                    .prepend($('<img>', {src: valueObj['thumbnail_url']}));
            }
        }

        // Manage most common default values for other data types.
        if (isSpecific) {
            if (dataType === 'uri' || dataType.startsWith('valuesuggest')) {
                if (defaultValue.match(/^(\S+)\s(.*)/)) {
                    valueObj = defaultValue.match(/^(\S+)\s(.*)/).slice(1);
                    valueObj = {'@id': valueObj[0], 'o:label': valueObj[1]};
                } else {
                    valueObj = {'@id': defaultValue};
                }
            } else {
                valueObj = {'@value': defaultValue};
            }
        }

        // Prepare simple single-value form inputs using data-value-key.
        value.find(':input').each(function () {
            var valueKey = $(this).data('valueKey');
            if (!valueKey) {
                return;
            }
            $(this).removeAttr('name')
                .val(valueObj ? valueObj[valueKey] : null);
        });

        // @see custom-vocab.js
        if (dataType.startsWith('customvocab:')) {
            var selectTerms = value.find('select.terms');
            selectTerms.find('option[value="' + valueObj['@value'] + '"]').prop('selected', true);
            selectTerms.chosen({ width: '100%', });
            selectTerms.trigger('chosen:updated');
        }

        // @see numeric-data-types.js
        if (NumericDataTypes) {
            if (dataType === 'numeric:timestamp') {
                v = value.find('.numeric-datetime-value');
                // The class is used to init a field, but it doesn't have the value yet.
                v.val(defaultValue);
                v.closest('.numeric-timestamp').removeClass('numeric-enabled');
                NumericDataTypes.enableTimestamp(value);
            }
            if (dataType === 'numeric:interval') {
                v = value.find('.numeric-datetime-value');
                // The class is used to init a field, but it doesn't have the value yet.
                v.val(defaultValue);
                v.closest('.numeric-interval').removeClass('numeric-enabled');
                NumericDataTypes.enableInterval(value);
            }
            if (dataType === 'numeric:duration') {
                v = value.find('.numeric-duration-value');
                // The class is used to init a field, but it doesn't have the value yet.
                v.val(defaultValue);
                v.closest('.numeric-duration').removeClass('numeric-enabled');
                NumericDataTypes.enableDuration(value);
            }
            if (dataType === 'numeric:integer') {
                v = value.find('.numeric-integer-value');
                // The class is used to init a field, but it doesn't have the value yet.
                v.val(defaultValue);
                v.closest('.numeric-integer').removeClass('numeric-enabled');
                NumericDataTypes.enableInteger(value);
            }
        }

        // Value Suggest is a lot more complex. Sub-trigger value?
        // @see valuesuggest.js
        if (dataType.startsWith('valuesuggest')) {
            var thisValue = value;
            var suggestInput = thisValue.find('.valuesuggest-input');
            var labelInput = thisValue.find('input[data-value-key="o:label"]');
            var idInput = thisValue.find('input[data-value-key="@id"]');
            var valueInput = thisValue.find('input[data-value-key="@value"]');
            // var languageLabel = thisValue.find('.value-language.label');
            var languageInput = thisValue.find('input[data-value-key="@language"]');
            // var languageRemove = thisValue.find('.value-language.remove');
            var idContainer = thisValue.find('.valuesuggest-id-container');

            if (valueObj['o:label']) {
                labelInput.val(valueObj['o:label']);
            }
            if (valueObj['@id']) {
                idInput.val(valueObj['@id']);
            }
            if (valueObj['@value']) {
                valueInput.val(valueObj['@value']);
            }
            if (valueObj['@language']) {
                languageInput.val(valueObj['@language']);
            }

            // Literal is the default type.
            idInput.prop('disabled', true);
            labelInput.prop('disabled', true);
            valueInput.prop('disabled', false);
            idContainer.hide();

            // Set existing values during initial load.
            if (idInput.val()) {
                // Set value as URI type
                suggestInput.val(labelInput.val()).attr('placeholder', labelInput.val());
                idInput.prop('disabled', false);
                labelInput.prop('disabled', false);
                valueInput.prop('disabled', true);
                var link = $('<a>')
                    .attr('href', idInput.val())
                    .attr('target', '_blank')
                    .text(idInput.val());
                idContainer.show().find('.valuesuggest-id').html(link);
            } else if (valueInput.val()) {
                // Set value as Literal type
                suggestInput.val(valueInput.val()).attr('placeholder', valueInput.val());
                idInput.prop('disabled', true);
                labelInput.prop('disabled', true);
                valueInput.prop('disabled', false);
            }
        }
    }

    function prepareAutofiller() {
        $('#resource-values .non-properties .field.autofiller').remove();

        var templateData = $('#resource-values').data('template-data');
        if (!templateData || !templateData.autofillers || !templateData.autofillers.length) {
            return;
        }

        templateData.autofillers.forEach(function(autofillerName) {
            $.get(baseUrl + 'admin/autofiller/settings', {
                    service: autofillerName,
                    template: $('#resource-template-select').val(),
                })
                .done(function(data) {
                    var autofiller = data.data.autofiller;
                    var autofillerId = 'autofiller-' + autofillerName.replace(/[\W_]+/g,'-');
                    $('#resource-values .non-properties').append(`
<div class="field autofiller">
    <div class="field-meta">
        <label for="autofiller-input">${autofiller.label}</label>
    </div>
    <div class="inputs">
        <input type="text" class="autofiller" id="${autofillerId}">
    </div>
</div>`);
                    var autofillerField = $('#' + autofillerId);
                    autofillerField.autocomplete({
                        serviceUrl: baseUrl + 'admin/autofiller',
                        deferRequestBy: 200,
                        // minChars: 3,
                        dataType: 'json',
                        maxHeight: 600,
                        paramName: 'q',
                        params: {
                            service: autofillerName,
                            template: $('#resource-template-select').val(),
                        },
                        showNoSuggestionNotice: true,
                        noSuggestionNotice: Omeka.jsTranslate('No results'),
                        // Required, because when multiple characters are typed,
                        // the previous requests are stopped.
                        preventBadQueries: false,
                        transformResult: function(response) {
                            return response.data;
                        },
                        onSearchStart: function (params) {
                            $(this).css('cursor', 'progress');
                        },
                        onSearchComplete: function (query, suggestions) {
                            $(this).css('cursor', 'default');
                        },
                        onSearchError: function (query, jqXHR, textStatus, errorThrown) {
                            // If there is no response, the request is aborted for autocompletion.
                            if (jqXHR.responseJSON) {
                                if (jqXHR.responseJSON.status === 'fail') {
                                    alert(jqXHR.responseJSON.data.suggestions);
                                } else {
                                    alert(jqXHR.responseJSON.message);
                                }
                                autofillerField.autocomplete().dispose();
                            }
                        },
                        beforeRender: function (container, suggestions) {
                            container.children().each(function(index) {
                                if (Object.keys(suggestions[index].data).length) {
                                    var info = $(this).append('<div class="suggest-info autofill"><dl></dl></div>').find('.suggest-info dl');
                                    $.each(suggestions[index].data, function(term, value) {
                                        info.append(`<dt>${value[0].property_label ? value[0].property_label : term }</dt>`);
                                        value.forEach(function(val) {
                                            info.append(`<dd>${typeof val['@value'] === 'undefined' || String(val['@value']) && !String(val['@value']).length ? val['@id'] : val['@value'] }</dd>`);
                                        });
                                    });
                                }
                            });
                        },
                        onSelect: function (suggestion) {
                            autofill(suggestion.data);
                        },
                    });
                });
        });
    }

    function autofill(values) {
        Object.keys(values).forEach(function(term) {
            values[term].forEach(function(value) {
                var field = $('.resource-values.field[data-property-term="' + term + '"]').filter(function() { return $.inArray(value.type, $(this).data('data-types').split(',')) > -1; });
                if (!field.length) {
                    field = makeNewField(term);
                }
                // Check if the first field has an empty default value, so remove it.
                field.first().find('.values .value.default-value').remove();
                field.first().find('.values').append(makeNewValue(term, value.type, value));
            });
        });
    }

    function initAutocomplete() {
        var autocompleteField = $(this);
        autocompleteField.autocomplete({
            serviceUrl: baseUrl + 'admin/values',
            dataType: 'json',
            maxHeight: 600,
            paramName: 'q',
            params: {
                prop: autocompleteField.closest('.resource-values.field').data('property-id'),
                type: autocompleteField.closest('.resource-values.field').data('autocomplete'),
            },
            deferRequestBy: 200,
            // minChars: 3,
            // showNoSuggestionNotice: true,
            //. noSuggestionNotice: Omeka.jsTranslate('No results'),
            // Required, because when multiple characters are typed,
            // the previous requests are stopped.
            preventBadQueries: false,
            transformResult: function(response) {
                return response.data;
            },
            onSearchStart: function (params) {
                $(this).css('cursor', 'progress');
            },
            onSearchComplete: function (query, suggestions) {
                $(this).css('cursor', 'default');
            },
            onSearchError: function (query, jqXHR, textStatus, errorThrown) {
                // If there is no response, the request is aborted for autocompletion.
                if (jqXHR.responseJSON) {
                    if (jqXHR.responseJSON.status === 'fail') {
                        alert(jqXHR.responseJSON.data.suggestions);
                    } else {
                        alert(jqXHR.responseJSON.message);
                    }
                    autofillerField.autocomplete().dispose();
                }
            },
        });
    }

    function jsonDecodeObject(string) {
        try {
            var obj = JSON.parse(string);
            return obj && typeof obj === 'object' && Object.keys(obj).length ? obj : null;
        } catch (e) {
            return null;
        }
    }

    $(document).on('o:form-loaded', 'form.resource-form', function() {
        if (typeof $('#resource-values').data('is-loaded') === 'undefined') {
            prepareAutofiller();
            $('#resource-values').data('is-loaded', $('#resource-template-select').val());
        }
    });

    $(document).on('o:template-applied', 'form.resource-form', function() {
        var fields = $('#properties .resource-values.field');
        fields.each(function(index, field) {
            prepareFieldAutocomplete($(field));
            prepareFieldLanguage($(field));
        });

        if (!$('#resource-values').data('locked-ready')) {
            fields.each(function(index, field) {
                prepareFieldLocked($(field));
            });
            $('#resource-values').data('locked-ready', true);
        }

        if (typeof $('#resource-values').data('is-loaded') !== 'undefined') {
            prepareAutofiller();
        }
    });

    $(document).on('o:property-added', '.resource-values.field', function() {
        var field = $(this);
        prepareFieldAutocomplete($(field));
        prepareFieldLanguage(field);
    });

    $(document).on('o:prepare-value', function(e, dataType, value, valueObj) {
        var field = value.closest('.resource-values.field');
        var term = value.data('term');
        if (!field.length) {
            field = $('#properties [data-property-term="' + term + '"].field');
            if (!field.length) {
                return;
            }
        }
        var rtpData = field.data('template-property-data');
        if (!rtpData) {
            return;
        }

        if (field.data('autocomplete')) {
            value.find('textarea.input-value').addClass('autocomplete');
            value.find('textarea.input-value.autocomplete').each(initAutocomplete);
        }

        var templateData = $('#resource-values').data('template-data');
        var listName = templateData.value_languages && !$.isEmptyObject(templateData.value_languages)
            ? 'value-languages-template'
            : 'value-languages';
        listName = rtpData.value_languages && !$.isEmptyObject(rtpData.value_languages)
            ? 'value-languages-' + term
            : listName;
        value.find('input.value-language').attr('list', listName);

        fillDefaultLanguage(value, valueObj, field);

        // Fill a new value with the setting specified in the template.
        // This is not the same than the default value in resource-form.js, that is an empty value.
        if (!rtpData.default_value || !rtpData.default_value.trim().length
            // The value from the object is already managed.
            || valueObj
            // Don't add a value if this is an edition.
            || !$('body').hasClass('add')
            // Don't add a value if there is already a value.
            || field.find('.input-value').length > 0
            || field.find('input.value').length > 0
            || field.find('input.value.to-require').length > 0
            // Custom vocab.
            || field.find('select.terms option').length > 0
            // Numeric data types.
            || field.find('input.numeric-datetime-value').length > 0
            || field.find('input.numeric-duration-value').length > 0
            || field.find('input.numeric-integer-value').length > 0
            // Value suggest.
            || field.find('input.valuesuggest-input').length > 0
        ) {
            return;
        } else {
            var defaultValue = jsonDecodeObject(rtpData.default_value);
            fillValue(value, term, dataType, defaultValue === null ? {default: rtpData.default_value.trim()} : defaultValue);
        }
    });

    var modal;
    // Append the button to create a new resource.
    $(document).on('o:sidebar-content-loaded', 'body.sidebar-open', function(e) {
        var sidebar = $('#select-resource.sidebar');
        if (sidebar.find('.quick-add-resource').length || !sidebar.find('#sidebar-resource-search').length) {
            return;
        }
        // TODO Determine the resource type in a cleaner way (cf. fix #omeka/omeka-s/1655).
        var resourceType = sidebar.find('#sidebar-resource-search').data('search-url');
        resourceType = resourceType.substring(resourceType.lastIndexOf('/admin/') + 7, resourceType.lastIndexOf('/sidebar-select'));
        if (!resourceType || resourceType === 'media') {
            return;
        }
        var button = `<div data-data-type="resource:${resourceType}">
    <a class="o-icon-${resourceType}s button quick-add-resource" href="${baseUrl + 'admin/' + resourceType}/add?window=modal" target="_blank"> ${Omeka.jsTranslate('New ' + resourceType.replace('-', ' '))}</a>
</div>`;
        sidebar.find('.search-nav').after(button)
    });
    // Allow to create a new resource in a modal window during edition of another resource.
    $(document).on('click', '.quick-add-resource', function(e) {
        e.preventDefault();
        // Save the modal in local storage to allow recursive new resources.
        var d = new Date();
        var windowName = 'new resource ' + d.getTime();
        var windowFeatures = 'titlebar=no,menubar=no,location=no,resizable=yes,scrollbars=yes,status=yes,directories=no,fullscreen=no,top=90,left=120,width=830,height=700';
        modal = window.open(e.target.href, windowName, windowFeatures);
        window.localStorage.setItem('modal', modal);
        // Check if the modal is closed, then refresh the list of resources.
        var checkSidebarModal = setInterval(function() {
            if (modal && modal.closed) {
                clearInterval(checkSidebarModal);
                // Wait to let Omeka saves the new resource, if any.
                setTimeout(function() {
                    var s = $('#sidebar-resource-search');
                    Omeka.populateSidebarContent(s.closest('.sidebar'), s.data('search-url'), '');
                }, 2000);
            }
        }, 100);
        return false;
    });
    // Add a new resource on modal window.
    $(document).on('click', '.modal form.resource-form #page-actions button[type=submit]', function(e) {
        // Warning: the submit may not occur when the modal is not focus.
        $('form.resource-form').submit();
        // TODO Manage error after submission (via ajax post?).
        // To avoid most issues for now, tab "Media" and "Thumbnail" are hidden.
        // Anyway, the user is working on the main resource.
        if ($('form.resource-form').data('has-error') === true) {
            e.preventDefault();
        } else {
            window.localStorage.removeItem('modal');
            // Leave time to submit the form before closing form.
            setTimeout(function() {
                window.close();
            }, 1000);
        }
        return false;
    });
    // Cancel modal window.
    $(document).on('click', '.modal form.resource-form #page-actions a.cancel', function(e) {
        e.preventDefault();
        window.localStorage.removeItem('modal');
        window.close();
        return false;
    });

});
