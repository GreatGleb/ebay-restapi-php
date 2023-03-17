let labels = document.querySelectorAll('.update_details label');
let checkBoxes = document.querySelectorAll('.update_details input[type="checkbox"]');

for(let label of labels) {
    label.addEventListener('click', labelClick);
}

for(let checkBox of checkBoxes) {
    checkBox.addEventListener('click', checkboxChange);
}

function labelClick() {
    let labelName = this.getAttribute('for');
    let checkBox = this.parentNode.querySelector('input[name="' + labelName + '"]');
    let isChecked = checkBox.getAttribute('checked');
    if(isChecked === '1') {
        checkBox.setAttribute('checked', '0');
        checkBox.checked = 0;
    } else {
        checkBox.setAttribute('checked', '1');
        checkBox.checked = 1;
    }

    if(labelName == 'all') {
        clickUpdateAllCheckboxes(checkBox.checked);
    }

    if(labelName == 'specifications') {
        clickUpdateSpecCheckboxes(checkBox.checked);
    }

    changingOneCheckbox();

    // console.log(checkBox)
    // console.log(isChecked == true)
    // console.log(checkBox.checked)
}

function checkboxChange() {
    console.log(this)
    if(this.checked) {
        this.setAttribute('checked', '1');
    } else {
        this.setAttribute('checked', '0');
    }

    if(this.getAttribute('name') == 'all') {
        clickUpdateAllCheckboxes(this.checked);
    }

    if(this.getAttribute('name') == 'specifications') {
        clickUpdateSpecCheckboxes(this.checked);
    }

    changingOneCheckbox();
}

function changingOneCheckbox() {
    let checkBoxes = document.querySelectorAll('.update_details input[type="checkbox"]:not(input[name="all"])');
    let checkBoxAll = document.querySelector('.update_details input[name="all"]');

    let isAllChecked = 1;

    for(let checkBox of checkBoxes) {
        if(!checkBox.checked) {
            isAllChecked = 0;
        }
    }

    if(checkBoxAll.checked && !isAllChecked) {
        checkBoxAll.checked = 0;
        checkBoxAll.setAttribute('checked', '0');
    } else if (!checkBoxAll.checked && isAllChecked) {
        checkBoxAll.checked = 1;
        checkBoxAll.setAttribute('checked', '1');
    }

    let checkBoxesSpec = document.querySelectorAll('' +
        '.update_details input[type="checkbox"][name="hersteller"],' +
        '.update_details input[type="checkbox"][name="produkttyp"], ' +
        '.update_details input[type="checkbox"][name="garantie"], ' +
        '.update_details input[type="checkbox"][name="oe"], ' +
        '.update_details input[type="checkbox"][name="nummer"], ' +
        '.update_details input[type="checkbox"][name="ean"], ' +
        '.update_details input[type="checkbox"][name="country"], ' +
        '.update_details input[type="checkbox"][name="length"], ' +
        '.update_details input[type="checkbox"][name="position"], ' +
        '.update_details input[type="checkbox"][name="keywords"]');
    let checkBoxSpec = document.querySelector('.update_details input[name="specifications"]');

    isAllChecked = 1;

    for(let checkBox of checkBoxesSpec) {
        if(!checkBox.checked) {
            isAllChecked = 0;
        }
    }

    console.log(checkBoxesSpec)

    if(checkBoxSpec.checked && !isAllChecked) {
        checkBoxSpec.checked = 0;
        checkBoxSpec.setAttribute('checked', '0');
    } else if (!checkBoxSpec.checked && isAllChecked) {
        checkBoxSpec.checked = 1;
        checkBoxSpec.setAttribute('checked', '1');
    }
}

function clickUpdateAllCheckboxes(isChecked) {
    if(isChecked) {
        for(let checkBox of checkBoxes) {
            checkBox.checked = 1;
            checkBox.setAttribute('checked', '1');
        }
    } else {
        for(let checkBox of checkBoxes) {
            checkBox.checked = 0;
            checkBox.setAttribute('checked', '0');
        }
    }
}

function clickUpdateSpecCheckboxes(isChecked) {
    let checkBoxes = document.querySelectorAll('.update_details input[type="checkbox"][name="specifications"], ' +
        '.update_details input[type="checkbox"][name="hersteller"], ' +
        '.update_details input[type="checkbox"][name="produkttyp"], ' +
        '.update_details input[type="checkbox"][name="garantie"], ' +
        '.update_details input[type="checkbox"][name="oe"], ' +
        '.update_details input[type="checkbox"][name="nummer"], ' +
        '.update_details input[type="checkbox"][name="ean"], ' +
        '.update_details input[type="checkbox"][name="country"], ' +
        '.update_details input[type="checkbox"][name="length"], ' +
        '.update_details input[type="checkbox"][name="position"], ' +
        '.update_details input[type="checkbox"][name="keywords"]');
    console.log(checkBoxes)
    if(isChecked) {
        for(let checkBox of checkBoxes) {
            checkBox.checked = 1;
            checkBox.setAttribute('checked', '1');
        }
    } else {
        for(let checkBox of checkBoxes) {
            checkBox.checked = 0;
            checkBox.setAttribute('checked', '0');
        }
    }
}
