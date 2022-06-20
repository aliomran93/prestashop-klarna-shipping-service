(() => {
    const featureActionClass = [
        'call_to_action',
        'badge',
        'info',
    ];

    const badgeOptions = [
        'eco-friendly',
        'carbon-compensated',
        'evening',
        'express',
        'recommended',
    ];

    const callToActionOptions = [
        'size-guide',
        'learn-more'
    ];

    const infoOptions = [
        'distributor-will-contact',
        'package-removal',
        'room-delivery',
    ];

    const displayKlarnaShippingServiceFeatures = (e: Event) => {
        const addFeatureButton = e.target as HTMLButtonElement;
        addFeatureButton.disabled = true;
        const selectFeature = document.createElement('select');
        selectFeature.setAttribute('data-input', 'feature_class');
        const placeholderOption = document.createElement('option');
        placeholderOption.innerText = 'Select feature action';
        selectFeature.appendChild(placeholderOption);
        for (let i = 0; i < featureActionClass.length; i++) {
            selectFeature.appendChild(displayValidFeatureSelect(featureActionClass[i], selectFeature));
        }
        const featureContainer = (
            <div class="feature-container well">
                <a class="btn removeFeature"><i class="icon-trash"></i></a>
                <div class="form-group">
                    <label class="col-lg-3 control-label">
                        Type of feature
                    </label>
                    <div class="col-lg-9">  
                        {selectFeature}
                    </div>
                </div>
                <a class="btn btn-success pull-right submit-feature">Add</a> 
            </div>
        ) as HTMLDivElement; 

        const mainFeatureContainer = document.getElementById('displayFeatures');
        mainFeatureContainer.append(featureContainer);
        const stepContainer = document.querySelectorAll('.stepContainer') as any as HTMLDivElement[];
        for (const div of stepContainer) {
            div.style.height = div.clientHeight + featureContainer.clientHeight + 'px';
        }
        setFeatureElementsEvents(featureContainer);
    };

    const featureSelectListener = (e: Event) => {
        const targetElement = e.target as HTMLSelectElement;
        const featureContainer = getParentFeatureContainer(targetElement);
        const addFeatureButton = featureContainer.querySelector('.submit-feature') as any as HTMLAnchorElement;
        const featureWrapper = featureContainer.querySelectorAll('.feature-wrapper') as any as HTMLDivElement[];
        for(const divElement of featureWrapper) {
            divElement.remove();
        }
        switch(targetElement.value) {
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
    };
    //TODO: all the render function can be refactored into one function since most of the elements generated are similar.
    const renderBadge = () => {
        const selectOptions = renderTypeSelect(badgeOptions);
        const options = selectOptions.children as any as HTMLOptionElement[];
        const tBody = document.querySelector('#addedFeatures > tbody') as HTMLTableSectionElement;
        for(const option of options) {
            let usedOption = tBody.querySelectorAll(':scope > tr > td[value="' + option.value + '"]') as any as HTMLTableCellElement[];
            option.disabled = usedOption.length > 0;
            option
        }
        return (
            <div class="feature-wrapper">
                <div class="form-group">
                    <label class="col-lg-3 control-label">
                        Badge
                    </label>
                    <div class="col-lg-9">  
                        {selectOptions}
                    </div>
                </div>
            </div>
        ) as HTMLDivElement;
    };

    const renderCallToAction = () => {
        const selectOptions = renderTypeSelect(callToActionOptions);
        return (
            <div class="feature-wrapper">
                <div class="form-group">
                    <label class="col-lg-3 control-label">
                        info type
                    </label>
                    <div class="col-lg-9">  
                        {selectOptions}
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-3 control-label required">
                        url
                    </label>
                    <div class="col-lg-9">  
                        <input type="text" class="required" data-input="feature_url"></input>
                    </div>
                </div>
            </div>
        ) as HTMLDivElement;
    };

    const renderInfo = () => {
        const selectOptions = renderTypeSelect(infoOptions);
        return (
            <div class="feature-wrapper">
                <div class="form-group">
                    <label class="col-lg-3 control-label">
                        Info type
                    </label>
                    <div class="col-lg-9">  
                        {selectOptions}
                    </div>
                </div>
                
            </div>
        ) as HTMLDivElement;

    };

    const renderTypeSelect = (contentArray: string[]) => {
        const options = [(<option>Select option</option>)];
        for (const value of contentArray) {
            options.push((<option value={value}>{value}</option>));
        }
        return (<select data-input="feature_type">{options}</select>);
    };

    const displayValidFeatureSelect = (featureActionClass: string, selectFeature: HTMLSelectElement): HTMLOptionElement => {
        let optionFeature = document.createElement('option');
        optionFeature.setAttribute('value', featureActionClass);
        optionFeature.innerText = featureActionClass;
        const addedFeatures = document.querySelectorAll('td[value="' + featureActionClass + '"]') as any as HTMLTableCellElement[];
        console.log(addedFeatures);
        let countOptions = 0;
        for(const feature of addedFeatures) {
            if(feature.getAttribute('value') === featureActionClass) {
                countOptions++;
            }
        }
        switch(featureActionClass) {
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

    const removeFeatureContainer = (e: Event) => {
        const featureContainer = getParentFeatureContainer(e.target as HTMLAnchorElement);
        const stepContainer = document.querySelectorAll('.stepContainer') as any as HTMLDivElement[];
        const addFeatureButton = document.getElementById('addFeature') as HTMLButtonElement;
        addFeatureButton.disabled = false;
        for (const div of stepContainer) {
            let newStepContainerHeight = div.clientHeight - featureContainer.clientHeight;
            div.style.height = newStepContainerHeight + 'px';
        }
        featureContainer.remove();
    };

    const addFeature = (e: Event) => {
        const featureContainer = getParentFeatureContainer(e.target as HTMLAnchorElement);
        const selectedType = featureContainer.querySelector('select[data-input="feature_class"]') as HTMLSelectElement;
        if (selectedType.selectedIndex === 0) {
            alert('You must select a feature.');
            return;
        }
        const typeSelect = featureContainer.querySelector('[data-input="feature_type"]') as HTMLSelectElement;
        if (typeSelect !== null && typeSelect.selectedIndex === 0) {
            alert('You must select a feature type');
            return;
        }
        const urlField = featureContainer.querySelector('[data-input="feature_url"]') as HTMLInputElement;
        if (urlField !== null && urlField.value == '') {
            alert('You must specify a url');
            return;
        }
        const inputFields = featureContainer.querySelectorAll('[data-input]') as any as  HTMLInputElement[];
        const tr = document.createElement('tr');
        const tableRows = document.querySelectorAll('#addedFeatures tbody tr') as any as HTMLTableRowElement[];
        const rowsCount = tableRows.length;
        for(const inputField of inputFields) {
            let th = document.querySelector('[data-column-index="' + inputField.getAttribute('data-input') + '"]') as HTMLTableCellElement;
            let cell = tr.insertCell(th.cellIndex);
            cell.setAttribute('value', inputField.value);
            cell.innerText = inputField.value;
            let inputName = 'kss_features[' + rowsCount + '][' + inputField.getAttribute('data-input') + ']';
            const hiddenInput = (
                <input type="hidden" name={inputName} value={inputField.value}/>
            ) as HTMLInputElement;
            cell.appendChild(hiddenInput);
        }
        if(inputFields.length < 3) {
            tr.insertCell(-1);
        }
        const deleteButton = tr.insertCell(-1);
        deleteButton.innerHTML = '<a class="btn removeFeatureInput"><i class="icon-trash"></i></a>';
        deleteButton.addEventListener('click', removeFeatureInput);
        const featureTable = document.querySelector('#addedFeatures tbody') as HTMLTableElement;
        featureTable.appendChild(tr);
        featureContainer.remove();
        const addFeatureButton = document.getElementById('addFeature') as HTMLButtonElement;
        addFeatureButton.disabled = false;
    };

    const removeFeatureInput = (e: Event) => {
        const targetElement = e.target as HTMLAnchorElement;
        const inputRow = targetElement.closest('tr') as HTMLTableRowElement;
        inputRow.remove();
        recalculateInputIndexes();
    };

    const recalculateInputIndexes = () => {
        const inputTableRows = document.querySelectorAll('#addedFeatures tbody tr') as any as HTMLTableRowElement[];
        let counter = 0;
        for(const inputRow of inputTableRows) {
            let inputFields = inputRow.querySelectorAll('input') as any as HTMLInputElement[];
            for(const inputField of inputFields) {
                inputField.name = inputField.name.replace(/\[[0-9]*\]/, '[' + counter + ']');
                console.log(inputField.name);
            }
            counter++;
        }
    };

    const getParentFeatureContainer = (element: HTMLElement): HTMLDivElement => {
        return element.closest('.feature-container') as HTMLDivElement;
    };

    const setFeatureElementsEvents = (featureContainer: HTMLDivElement) => {
        const removeButton = document.querySelectorAll('.removeFeature') as any as HTMLAnchorElement[];
        const selectFeatureElement = featureContainer.querySelectorAll('select[data-input="feature_class"]') as any as HTMLSelectElement[];
        const addButton = featureContainer.querySelector('.submit-feature') as any as HTMLButtonElement;
        addButton.addEventListener('click', addFeature);

        for (const select of selectFeatureElement) {
            select.addEventListener('change', featureSelectListener);
        }
        for (const anchor of removeButton) {
            anchor.addEventListener('click', removeFeatureContainer);
        }
    };

    const init = () => {
        const removeInputButton = document.querySelectorAll('.removeFeatureInput') as any as HTMLAnchorElement[];
        for (const anchor of removeInputButton) {
            anchor.addEventListener('click', removeFeatureInput);
        }
        const addFeatureButton = document.getElementById('addFeature');
        addFeatureButton.addEventListener('click', displayKlarnaShippingServiceFeatures);
    };

    addEventListener('DOMContentLoaded', init);
})();