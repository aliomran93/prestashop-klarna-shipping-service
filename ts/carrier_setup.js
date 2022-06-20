"use strict";

(function () {
  var featureActionClass = ['call_to_action', 'badge', 'info'];
  var badgeOptions = ['eco-friendly', 'carbon-compensated', 'evening', 'express', 'recommended'];
  var callToActionOptions = ['size-guide', 'learn-more'];
  var infoOptions = ['distributor-will-contact', 'package-removal', 'room-delivery'];

  var displayKlarnaShippingServiceFeatures = function displayKlarnaShippingServiceFeatures(e) {
    var addFeatureButton = e.target;
    addFeatureButton.disabled = true;
    var selectFeature = document.createElement('select');
    selectFeature.setAttribute('data-input', 'feature_class');
    var placeholderOption = document.createElement('option');
    placeholderOption.innerText = 'Select feature action';
    selectFeature.appendChild(placeholderOption);

    for (var i = 0; i < featureActionClass.length; i++) {
      selectFeature.appendChild(displayValidFeatureSelect(featureActionClass[i], selectFeature));
    }

    var featureContainer = createElement('div', {
      class: "feature-container well"
    }, [createElement('a', {
      class: "btn removeFeature"
    }, [createElement('i', {
      class: "icon-trash"
    })]), createElement('div', {
      class: "form-group"
    }, [createElement('label', {
      class: "col-lg-3 control-label"
    }, ["Type of feature"]), createElement('div', {
      class: "col-lg-9"
    }, [selectFeature])]), createElement('a', {
      class: "btn btn-success pull-right submit-feature"
    }, ["Add"])]);
    var mainFeatureContainer = document.getElementById('displayFeatures');
    mainFeatureContainer.append(featureContainer);
    var stepContainer = document.querySelectorAll('.stepContainer');

    for (var _i = 0, stepContainer_1 = stepContainer; _i < stepContainer_1.length; _i++) {
      var div = stepContainer_1[_i];
      div.style.height = div.clientHeight + featureContainer.clientHeight + 'px';
    }

    setFeatureElementsEvents(featureContainer);
  };

  var featureSelectListener = function featureSelectListener(e) {
    var targetElement = e.target;
    var featureContainer = getParentFeatureContainer(targetElement);
    var addFeatureButton = featureContainer.querySelector('.submit-feature');
    var featureWrapper = featureContainer.querySelectorAll('.feature-wrapper');

    for (var _i = 0, featureWrapper_1 = featureWrapper; _i < featureWrapper_1.length; _i++) {
      var divElement = featureWrapper_1[_i];
      divElement.remove();
    }

    switch (targetElement.value) {
      case 'call_to_action':
        featureContainer.insertBefore(renderCallToAction(), addFeatureButton);
        break;

      case 'badge':
        featureContainer.insertBefore(renderBadge(), addFeatureButton);
        break;

      case 'info':
        featureContainer.insertBefore(renderInfo(), addFeatureButton);
        break;

      default:
        break;
    }

    setFeatureElementsEvents(featureContainer);
  }; //TODO: all the render function can be refactored into one function since most of the elements generated are similar.


  var renderBadge = function renderBadge() {
    var selectOptions = renderTypeSelect(badgeOptions);
    var options = selectOptions.children;
    var tBody = document.querySelector('#addedFeatures > tbody');

    for (var _i = 0, options_1 = options; _i < options_1.length; _i++) {
      var option = options_1[_i];
      var usedOption = tBody.querySelectorAll(':scope > tr > td[value="' + option.value + '"]');
      option.disabled = usedOption.length > 0;
      option;
    }

    return createElement('div', {
      class: "feature-wrapper"
    }, [createElement('div', {
      class: "form-group"
    }, [createElement('label', {
      class: "col-lg-3 control-label"
    }, ["Badge"]), createElement('div', {
      class: "col-lg-9"
    }, [selectOptions])])]);
  };

  var renderCallToAction = function renderCallToAction() {
    var selectOptions = renderTypeSelect(callToActionOptions);
    return createElement('div', {
      class: "feature-wrapper"
    }, [createElement('div', {
      class: "form-group"
    }, [createElement('label', {
      class: "col-lg-3 control-label"
    }, ["info type"]), createElement('div', {
      class: "col-lg-9"
    }, [selectOptions])]), createElement('div', {
      class: "form-group"
    }, [createElement('label', {
      class: "col-lg-3 control-label required"
    }, ["url"]), createElement('div', {
      class: "col-lg-9"
    }, [createElement('input', {
      type: "text",
      class: "required",
      'data-input': "feature_url"
    })])])]);
  };

  var renderInfo = function renderInfo() {
    var selectOptions = renderTypeSelect(infoOptions);
    return createElement('div', {
      class: "feature-wrapper"
    }, [createElement('div', {
      class: "form-group"
    }, [createElement('label', {
      class: "col-lg-3 control-label"
    }, ["Info type"]), createElement('div', {
      class: "col-lg-9"
    }, [selectOptions])])]);
  };

  var renderTypeSelect = function renderTypeSelect(contentArray) {
    var options = [createElement('option', null, ["Select option"])];

    for (var _i = 0, contentArray_1 = contentArray; _i < contentArray_1.length; _i++) {
      var value = contentArray_1[_i];
      options.push(createElement('option', {
        value: value
      }, [value]));
    }

    return createElement('select', {
      'data-input': "feature_type"
    }, [options]);
  };

  var displayValidFeatureSelect = function displayValidFeatureSelect(featureActionClass, selectFeature) {
    var optionFeature = document.createElement('option');
    optionFeature.setAttribute('value', featureActionClass);
    optionFeature.innerText = featureActionClass;
    var addedFeatures = document.querySelectorAll('td[value="' + featureActionClass + '"]');
    console.log(addedFeatures);
    var countOptions = 0;

    for (var _i = 0, addedFeatures_1 = addedFeatures; _i < addedFeatures_1.length; _i++) {
      var feature = addedFeatures_1[_i];

      if (feature.getAttribute('value') === featureActionClass) {
        countOptions++;
      }
    }

    switch (featureActionClass) {
      case 'call_to_action':
        optionFeature.disabled = countOptions >= 2;
        return optionFeature;

      case 'badge':
        optionFeature.disabled = countOptions >= 5;
        return optionFeature;

      case 'info':
        optionFeature.disabled = countOptions >= 1;
        return optionFeature;

      default:
        return optionFeature;
    }
  };

  var removeFeatureContainer = function removeFeatureContainer(e) {
    var featureContainer = getParentFeatureContainer(e.target);
    var stepContainer = document.querySelectorAll('.stepContainer');
    var addFeatureButton = document.getElementById('addFeature');
    addFeatureButton.disabled = false;

    for (var _i = 0, stepContainer_2 = stepContainer; _i < stepContainer_2.length; _i++) {
      var div = stepContainer_2[_i];
      var newStepContainerHeight = div.clientHeight - featureContainer.clientHeight;
      div.style.height = newStepContainerHeight + 'px';
    }

    featureContainer.remove();
  };

  var addFeature = function addFeature(e) {
    var featureContainer = getParentFeatureContainer(e.target);
    var selectedType = featureContainer.querySelector('select[data-input="feature_class"]');

    if (selectedType.selectedIndex === 0) {
      alert('You must select a feature.');
      return;
    }

    var typeSelect = featureContainer.querySelector('[data-input="feature_type"]');

    if (typeSelect !== null && typeSelect.selectedIndex === 0) {
      alert('You must select a feature type');
      return;
    }

    var urlField = featureContainer.querySelector('[data-input="feature_url"]');

    if (urlField !== null && urlField.value == '') {
      alert('You must specify a url');
      return;
    }

    var inputFields = featureContainer.querySelectorAll('[data-input]');
    var tr = document.createElement('tr');
    var tableRows = document.querySelectorAll('#addedFeatures tbody tr');
    var rowsCount = tableRows.length;

    for (var _i = 0, inputFields_1 = inputFields; _i < inputFields_1.length; _i++) {
      var inputField = inputFields_1[_i];
      var th = document.querySelector('[data-column-index="' + inputField.getAttribute('data-input') + '"]');
      var cell = tr.insertCell(th.cellIndex);
      cell.setAttribute('value', inputField.value);
      cell.innerText = inputField.value;
      var inputName = 'kss_features[' + rowsCount + '][' + inputField.getAttribute('data-input') + ']';
      var hiddenInput = createElement('input', {
        type: "hidden",
        name: inputName,
        value: inputField.value
      });
      cell.appendChild(hiddenInput);
    }

    if (inputFields.length < 3) {
      tr.insertCell(-1);
    }

    var deleteButton = tr.insertCell(-1);
    deleteButton.innerHTML = '<a class="btn removeFeatureInput"><i class="icon-trash"></i></a>';
    deleteButton.addEventListener('click', removeFeatureInput);
    var featureTable = document.querySelector('#addedFeatures tbody');
    featureTable.appendChild(tr);
    featureContainer.remove();
    var addFeatureButton = document.getElementById('addFeature');
    addFeatureButton.disabled = false;
  };

  var removeFeatureInput = function removeFeatureInput(e) {
    var targetElement = e.target;
    var inputRow = targetElement.closest('tr');
    inputRow.remove();
    recalculateInputIndexes();
  };

  var recalculateInputIndexes = function recalculateInputIndexes() {
    var inputTableRows = document.querySelectorAll('#addedFeatures tbody tr');
    var counter = 0;

    for (var _i = 0, inputTableRows_1 = inputTableRows; _i < inputTableRows_1.length; _i++) {
      var inputRow = inputTableRows_1[_i];
      var inputFields = inputRow.querySelectorAll('input');

      for (var _a = 0, inputFields_2 = inputFields; _a < inputFields_2.length; _a++) {
        var inputField = inputFields_2[_a];
        inputField.name = inputField.name.replace(/\[[0-9]*\]/, '[' + counter + ']');
        console.log(inputField.name);
      }

      counter++;
    }
  };

  var getParentFeatureContainer = function getParentFeatureContainer(element) {
    return element.closest('.feature-container');
  };

  var setFeatureElementsEvents = function setFeatureElementsEvents(featureContainer) {
    var removeButton = document.querySelectorAll('.removeFeature');
    var selectFeatureElement = featureContainer.querySelectorAll('select[data-input="feature_class"]');
    var addButton = featureContainer.querySelector('.submit-feature');
    addButton.addEventListener('click', addFeature);

    for (var _i = 0, selectFeatureElement_1 = selectFeatureElement; _i < selectFeatureElement_1.length; _i++) {
      var select = selectFeatureElement_1[_i];
      select.addEventListener('change', featureSelectListener);
    }

    for (var _a = 0, removeButton_1 = removeButton; _a < removeButton_1.length; _a++) {
      var anchor = removeButton_1[_a];
      anchor.addEventListener('click', removeFeatureContainer);
    }
  };

  var init = function init() {
    var removeInputButton = document.querySelectorAll('.removeFeatureInput');

    for (var _i = 0, removeInputButton_1 = removeInputButton; _i < removeInputButton_1.length; _i++) {
      var anchor = removeInputButton_1[_i];
      anchor.addEventListener('click', removeFeatureInput);
    }

    var addFeatureButton = document.getElementById('addFeature');
    addFeatureButton.addEventListener('click', displayKlarnaShippingServiceFeatures);
  };

  addEventListener('DOMContentLoaded', init);
})();
//# sourceMappingURL=carrier_setup.js.map
