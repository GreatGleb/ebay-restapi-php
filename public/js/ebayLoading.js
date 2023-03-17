let submits = document.querySelectorAll('button[type="submit"]');

console.log(submits)
for(let submit of submits) {
    submit.addEventListener('click', startPrepareLoading);
}

function send(url, params, finishFunction) {
    var xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function() {
        if (xhr.readyState == XMLHttpRequest.DONE) {
            return finishFunction(xhr.responseText, params, url);
        }
    }
    xhr.open("POST", url, true);
    // xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.send(params);
}

let itsFinishLoad = 0;
let itsFinishPreLoad = 0;

function startPrepareLoading(event) {
    let background = document.querySelector(".store-container");
    let loadingAnimation = document.querySelector(".loading");

    background.className += ' open';
    loadingAnimation.className += ' open';
    document.body.style.backgroundColor = '#ddd';

    let time = Date.now();

    let params = new FormData();
    params.append("file", this.parentNode.querySelector('input[type="file"]').files[0]);
    let checkboxes = this.parentNode.querySelectorAll('input[type="checkbox"]:checked');
    for(let checkbox of checkboxes) {
        params.append(checkbox.getAttribute('name'), checkbox.value);
    }
    params.append('date', time);

    let link = this.parentNode.parentNode.parentNode.dataset.action;

    // console.log(params);
    let result = send(link, params, showPreparedItems);
    // console.log(result)

    // if(this.parentNode.querySelector('.update_details') == null) {
        let paramsCheck = new FormData();
        paramsCheck.append('date', time);

        setTimeout(function () {
            send("http://domain/api/ebay/checkImportLoading", paramsCheck, updateLoadCounter);
        }, 500);

        console.log(itsFinishPreLoad)

        let timer = setInterval(function () {
            if (itsFinishPreLoad == 1) {
                clearInterval(timer);
            }

            send("http://domain/api/ebay/checkImportLoading", paramsCheck, updateLoadCounter);
        }, 1000);
    // }
}

function updateLoadCounter(data) {
    data = JSON.parse(data);

    if(data['earlier'] === undefined) {
        let loadImage = document.querySelector('.store-container');
        if(loadImage.classList.contains('open')) {
            let loadCounter = document.querySelector('div.loadCounter');
            if (loadCounter == null) {
                loadCounter = document.createElement('div');
                loadCounter.className = 'loadCounter';
                loadCounter.innerHTML = 'Loaded: ';
                document.body.append(loadCounter);
            } else {
                loadCounter.innerHTML = 'Loaded: ' + data[1] + ' / ' + data[0];
            }
        }
    }
}

function showResults(data) {
    let loadImage = document.querySelector('.store-container');
    loadImage.classList.remove('open');
    let loadCounter = document.querySelector('.loadCounter');
    loadCounter.style.display = 'none';
    itsFinishLoad = 1;

    let divFinish = document.createElement('div');
    divFinish.className = 'finish';

    let h1Finish = document.createElement('h1');
    h1Finish.innerText = 'Import completed';
    divFinish.append(h1Finish);

    let divResume = document.createElement('div');
    divResume.className = 'resume';

    let h2Resume = document.createElement('h2');
    h2Resume.innerText = 'Resume:';
    divResume.append(h2Resume);

    data = JSON.parse(data);

    if(data['result'] !== undefined && data['result'] != null) {
        let keys = Object.keys(data['result']);
        for (let k = 0; k < keys.length; k++) {
            let key = keys[k];
            let color = 'black';
            if(key == 'ItsAlreadyWas' || key == 'ItsYetWasnt') {
                color = 'darkorange';
            } else if(key == 'Failure') {
                color = 'red';
            } else if(key == 'Success') {
                color = 'green';
            }

            let span1 = document.createElement('span');
            span1.innerText = ' ' + key;
            span1.style.color = color;

            let span2 = document.createElement('span');
            span2.innerText = ': ' + data['result'][key];
            if(k == keys.length - 1) {
                span2.innerText += '.';
            } else {
                span2.innerText += ';';
            }

            divResume.append(span1);
            divResume.append(span2);
        }

        divFinish.append(divResume);
    }

    let keys = Object.keys(data);

    if(keys.length > 1) {
        let tableContainer = document.createElement('div');
        tableContainer.className = 'result_table_container';
        let table = document.createElement('table');
        let thead = document.createElement('thead');
        let trth = document.createElement('tr');
        let th = document.createElement('th');
        th.innerHTML = 'number';
        trth.append(th);
        th = document.createElement('th');
        th.innerHTML = 'status';
        trth.append(th);
        th = document.createElement('th');
        th.innerHTML = 'sku';
        trth.append(th);
        th = document.createElement('th');
        th.innerHTML = 'title';
        trth.append(th);

        let isErr = 0;

        for(let r = 0; r < keys.length; r++) {
            let key = keys[r];
            if(data[key] !== undefined && data[key] != null && key !== 'result') {
                let objKeys = Object.keys(data[key]);
                console.log(data[key])
                for(let objKey in objKeys) {
                    console.log(objKeys[objKey])
                    if(objKeys[objKey] == 'Errors') {
                        isErr = 1;
                    }
                }
            }
        }

        if(isErr) {
            th = document.createElement('th');
            th.innerHTML = 'error';
            trth.append(th);
        }

        thead.append(trth);
        table.append(thead);

        let tbody = document.createElement('tbody');

        for(let r = 0; r < keys.length; r++) {
            let key = keys[r];
            let obj = data[key];
            if(obj !== undefined && obj != null && key !== 'result') {
                console.log(key, data[key])
                let tr = document.createElement('tr');
                let td = document.createElement('td');
                if(obj['numberInFile'] !== undefined && obj['numberInFile'] != null) {
                    td.innerHTML = obj['numberInFile'];
                }
                tr.append(td);
                td = document.createElement('td');
                if(obj['Ack'] !== undefined && obj['Ack'] != null) {
                    td.innerHTML = obj['Ack'];
                    td.setAttribute('status', obj['Ack']);
                }
                tr.append(td);
                td = document.createElement('td');
                if(obj['SKU'] !== undefined && obj['SKU'] != null) {
                    td.innerHTML = obj['SKU'];
                }
                tr.append(td);
                td = document.createElement('td');
                if(obj['Title'] !== undefined && obj['Title'] != null) {
                    td.innerHTML = obj['Title'];
                }
                tr.append(td);

                console.log('isErr', isErr)
                if(isErr) {
                    td = document.createElement('td');
                    td.className = 'err';
                    if(obj['Errors'] !== undefined && obj['Errors'] != null) {
                        console.log(obj['Errors'])
                        for(let error of obj['Errors']) {
                            let div = document.createElement('div');
                            div.setAttribute('status', error['code']);
                            let divErrStatus = document.createElement('div');
                            let divErrDefinition = document.createElement('div');
                            divErrStatus.innerHTML = error['code'];
                            divErrDefinition.innerHTML = error['name'];
                            div.append(divErrStatus);
                            div.append(divErrDefinition);
                            td.append(div);
                        }
                    }
                    tr.append(td);
                }
                tbody.append(tr);
            }
        }
        table.append(tbody);

        tableContainer.append(table);
        divFinish.append(tableContainer);
        document.body.append(divFinish);
    }

    setTimeout(function() {
        divFinish = document.querySelector('div.finish');
        divFinish.className += ' open';
    }, 40);

    console.log(data)
    return 'a'
}

function showPreparedItems(data, params, link) {
    let loadImage = document.querySelector('.store-container');
    loadImage.classList.remove('open');
    let loadCounter = document.querySelector('.loadCounter');
    if(loadCounter !== null) {
        loadCounter = document.querySelector('.loadCounter');
    }
    itsFinishPreLoad = 1;

    let divFinish = document.createElement('div');
    divFinish.className = 'finish';

    let h1Finish = document.createElement('h1');
    h1Finish.innerText = 'Prepared Import';
    divFinish.append(h1Finish);

    data = JSON.parse(data);

    let keys = Object.keys(data);
    console.log('aaaaaaaaaa')
    console.log(keys)
    console.log(data[0]['headers'])

    if(keys.length > 0) {
        let tableContainer = document.createElement('div');
        tableContainer.className = 'result_table_container';
        let table = document.createElement('table');
        let thead = document.createElement('thead');
        let trth = document.createElement('tr');
        let th = document.createElement('th');
        th.innerHTML = 'number';
        trth.append(th);
        th = document.createElement('th');
        th.innerHTML = 'sku';
        trth.append(th);
        th = document.createElement('th');
        th.innerHTML = 'title';
        trth.append(th);
        for(let header in data[0]['headers']) {
            if(data[0]['headers'][header] == 'sku') {
                continue;
            }
            if(data[0]['headers'][header] == 'title') {
                continue;
            }
            if(data[0]['headers'][header] == 'specifications') {
                for(let spec in data[0][header]) {
                    th = document.createElement('th');
                    th.innerHTML = spec;
                    trth.append(th);
                }
            } else {
                th = document.createElement('th');
                th.innerHTML = data[0]['headers'][header];
                trth.append(th);
            }
        }

        thead.append(trth);
        table.append(thead);

        let tbody = document.createElement('tbody');

        for(let r = 0; r < keys.length; r++) {
            let key = keys[r];
            let obj = data[key];
            if(obj !== undefined && obj != null) {
                console.log(key, data[key])
                let tr = document.createElement('tr');
                let td = document.createElement('td');
                td.innerHTML = Number(key) + 1;
                tr.append(td);
                td = document.createElement('td');
                for(let header in data[0]['headers']) {
                    if(data[0]['headers'][header] == 'sku') {
                        td.innerHTML = obj[header];
                    }
                }
                tr.append(td);
                td = document.createElement('td');
                if(obj['title'] !== undefined && obj['title'] != null) {
                    td.innerHTML = obj['title'];
                }
                tr.append(td);

                for(let header in data[0]['headers']) {
                    if(data[0]['headers'][header] == 'title') {
                        continue;
                    }
                    if(data[0]['headers'][header] == 'sku') {
                        continue;
                    }
                    if(data[0]['headers'][header] == 'specifications') {
                        for(let spec in data[0][header]) {
                            td = document.createElement('td');
                            td.innerHTML = obj[header][spec];
                            tr.append(td);
                        }
                    } else if(data[0]['headers'][header] == 'descriptionReplace' || data[0]['headers'][header] == 'description') {
                        td = document.createElement('td');
                        td.innerHTML = obj[header];
                        tr.append(td);
                        let desc = tr.querySelector('td > .container');
                        if(desc) {
                            td.innerHTML = desc.innerHTML;
                        }
                    } else {
                        td = document.createElement('td');
                        td.innerHTML = obj[header];
                        tr.append(td);
                    }
                }
                tbody.append(tr);
            }
        }
        table.append(tbody);

        tableContainer.append(table);
        divFinish.append(tableContainer);
    }

    console.log(data)

    data = JSON.stringify(data);

    let ebayLoadButton = document.createElement('div');
    ebayLoadButton.className = 'ebayLoadButton';
    ebayLoadButton.innerHTML = 'Load to Ebay';
    ebayLoadButton.addEventListener('click', {handleEvent: startLoading, loadData: data, params: params, link: link});
    divFinish.append(ebayLoadButton);

    document.body.append(divFinish);

    setTimeout(function() {
        divFinish = document.querySelector('div.finish');
        divFinish.className += ' open';
    }, 40);

    console.log(data)
    return 'a'
}

function startLoading() {
    let divFinish = document.querySelector('div.finish');
    divFinish.remove();

    let background = document.querySelector(".store-container");
    let loadingAnimation = document.querySelector(".loading");

    background.className += ' open';
    loadingAnimation.className += ' open';

    this.params.set('file', this.loadData);
    console.log(this.params.get('date'))
    console.log('loadData')
    console.log(this.link)

    let result = send(this.link, this.params, showResults);
    console.log(result)

    let paramsCheck = new FormData();
    paramsCheck.append('date', this.params.get('date'));

    setTimeout(function() {
        send("http://domain/api/ebay/checkImportLoading", paramsCheck, updateLoadCounter);
    }, 500);

    let timer = setInterval(function () {
        if(itsFinishLoad == 1) {
            clearInterval(timer);
        }

        send("http://domain/api/ebay/checkImportLoading", paramsCheck, updateLoadCounter);
    }, 1000);
}
