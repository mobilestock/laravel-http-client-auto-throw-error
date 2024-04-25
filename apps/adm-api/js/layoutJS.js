class layoutJS
{
    constructor(div, header, params, page = 1, numRegPerPage = 25)
    {
        this.div = div;
        this.header = header;
        this.params = params;
        this.index = 1;
        this.page = page;
        this.numRegPerPage = numRegPerPage;
        this.div.innerHTML = `<i class="fa fa-circle-o-notch fa-spin"></i>`;
        this.pages = 0;
        this.filter = '';
        this.group = '';
        this.filterFields = '';
        this.divHeader = document.createElement('div');
        this.divHeader.className = 'header-table';
        this.divpagination = document.createElement('div');
        this.divpagination.className = 'pagination-table';
        this.divBody = document.createElement('div');
        this.divBody.className = 'body-table';
        this.dataFiltered = '';
    }

    setLoad(){
        this.div.innerHTML = `<i class="fa fa-circle-o-notch fa-spin"></i>`;
    }

    addFilter(filters){
        this.filterFields = filters;
    }

    addGroup(group){
        this.group = group;
    }

    setParams(params)
    {
        this.params = params;
    }

    async getMethod(uri)
    {
        //create get for request
        let url = uri+`?`+Object.keys(this.params).map(e=>{return `${e}=${this.params[e]}`}).join('&');
        if(this.filter.length>0 && this.inputSearch.length>0)
        {
            url += `&${this.filter}=${this.inputSearch}`;
        }
        let result = await fetch(url);
        let response = await result.json();

        this.div.innerHTML = "";
        this.div.append(this.divHeader);
        this.div.append(this.divpagination)
        this.div.append(this.divBody);
        
        this.dataOriginal = response;
        this.dataFiltered = this.dataOriginal;
        this.template(this.dataOriginal);
        return this.dataOriginal;
    }

    setData(data){
        this.template(data)
    }

    getData(){
        return this.dataOriginal;
    }

    template(data)
    {
        this.valueResult = [];
        let inPage = this.index*this.numRegPerPage-this.numRegPerPage;
        let outPage = inPage+this.numRegPerPage-1;
        let groupHeader = this.group.length>0 ? this.group.filter((e)=>e.index == 1): ``;
        let groupValue = ``;
        let val = 0;
        console.log(data);
        let html = `
        <table class="table table-rounded table-sm table-striped table-hover">
            <thead><tr>
            ${Object.keys(this.header).map(e=>{
                return `<th>${this.header[e].label}</th>`
            }).join('')}</tr>
            </thead>
            <tbody>
                ${Object.keys(data).map((linha,i)=>{
                    //index
                    let e = data[linha];
                    let htmlIn = ``;
                    if(inPage <= parseInt(linha) && parseInt(linha) <= outPage){
                        if(this.group.length>0){       

                            if(groupValue!=e[groupHeader[0].field]){
                                val = 0;
                                htmlIn += `<tr><td colspan="12"><div class="row mt-3 mb-0">`;

                                let label = this.group[0].label;
                                let value = e[this.group[0].field];
                                htmlIn += `<div class="col-sm-3"></div><div class="col-sm-3 d-flex justify-content-center"><h5>${label}: 
                                <strong><label class="date_${e[this.group[0].field]}">${value}</label>
                                </strong></h5></div>`;

                                if(this.group[1].fun=='sumValues'){
                                    htmlIn += `<div class="col-sm-3 d-flex justify-content-center"><h5>Total: 
                                    <strong><label class="total_${i}"></label>
                                    </strong></h5></div><div class="col-sm-3"></div>`;
                                }

                                htmlIn += `</div></td></tr>`;
                                groupValue = e[groupHeader[0].field];
                                this.valueResult.push({ index: i, date: e[groupHeader[0].field], value: 0 });
                            }

                            val += parseFloat(e[this.group[1].field]);
                            this.valueResult.map((u,i)=>{
                                u.date==e[groupHeader[0].field]?this.valueResult[i].value=val:'';
                            })
                        }
                        //return items between interval
                        htmlIn +=  `<tr>${ this.formatField(this.header, e)} </tr>`;
                    }
                    return htmlIn;
                }).join('')}
            </tbody>
        </table>
        <table class="table">
            <tr>
                <td>Registros: ${data.length}</td>
                <td>Registros por página: ${this.numRegPerPage}</td>
            </tr>
        </table>
        `;
        this.divBody.innerHTML = "";
        this.divBody.innerHTML = html;   
        this.templatePagination(data);
        this.createEvents();
        this.fillGroup();     
    }

    formatFieldGroup(line, values)
    {
        let html = ``;
        if(line.fun!='')
        {   
            let fun = line.fun;
            let val = line.values.map(e=>values[e]);
            html += `${fun(val[0])}`
        }else{
            html += `${values[line.field]}`;
        }
        return html;

        function sumValues(value)
        {
            this.valueResult+=value;
            return this.valueResult;
        }
    }

    templatePagination(data)
    {
        let numReg = data.length;
        this.pages = Math.ceil(numReg/this.numRegPerPage);
        let html = `
        <nav>
        <ul class="pagination">
            <li class="page-item">
            <a class="page-link" href="#" aria-label="Previous" id="previous">
                <span aria-hidden="true">&laquo;</span>
                <span class="sr-only">Previous</span>
            </a>
            </li>`;
            for(let i=1; i <= this.pages; i++){
                if(this.pages > 1 && i < 10){
                    html += `<li class="page-item"><a class="page-link" id="page${i}">${i}</a></li>`;
                }
            }
            html += `
            <li class="page-item">
            <a class="page-link" href="#" aria-label="Next" id="next">
                <span aria-hidden="true">&raquo;</span>
                <span class="sr-only">Next</span>
            </a>
            </li>
        </ul>
        </nav>`;
        this.divpagination.innerHTML = "";
        this.divpagination.innerHTML = html;
    }

    fillGroup()
    {
        if(this.valueResult.length>0){
            this.valueResult.map((e)=>{
                document.querySelector(`.total_${e.index}`).textContent = e.value.toLocaleString('pt-br',{style: 'currency', currency: 'BRL'});
            })
        }
    }

    createEvents(){

        for(let i=1; i<= this.pages; i++){
            if(this.pages>1 && i<10){
                document.querySelector(`#page${i}`).addEventListener("click", ()=>{
                    this.changePage(i);
                });
            }
        }
    }

    clearSearch()
    {
        if(this.filterFields.length>0){
            Object.keys(this.filterFields).map(e=>{
                let filter = this.filterFields[e];
                document.querySelector(`#${filter.field}`).value = "";
            });
        }
        this.updateTemplate(this.dataOriginal);
    }

    search()
    {
        let newData = this.dataOriginal;
        this.filterFields.forEach(e => {
            let value = document.querySelector(`#${e.field}`).value;
            if(value!=''){
                value = this.validateFormat(value);
                newData = newData.filter(i=>{
                    return this.validateFormat(i[e.field]).includes(value)
                }
                );
            }
        });
        this.index = 1;
        this.dataFiltered = newData;
        this.updateTemplate(this.dataFiltered);
    }

    validateFormat(value)
    {
        //valida data yyyy-mm-dd
        if(/^\d{4}([./-])\d{2}\1\d{2}$/.test(value))
        {
            let array = value.split('-');
            value = `${array[2]}/${array[1]}/${array[0]}`;
        }

        //valida data dd/mm/yyyy hh:mm:ss
        if(/^([1-9]|([012][0-9])|(3[01]))\/([0]{0,1}[1-9]|1[012])\/\d\d\d\d (20|21|22|23|[0-1]?\d):[0-5]?\d:[0-5]?\d$/.test(value))
        {
            let array = value.split(' ');
            value = `${array[0]}`;
        }

        //valida valor R$ 0,00
        if(/^[0-9.,]$/.test(value))
        {
            let array = value.split(' ');
            value = `${array[1]}`
        }
        return value;
    }

    previousPage()
    {
        if(this.index>1){
            this.index--;
            this.updateTemplate();
        }
    }

    nextPage()
    {
        if(this.index<this.pages){
            this.index++;
            this.updateTemplate();
        }
    }

    changePage(pageSelected)
    {
        this.index = pageSelected;
        this.updateTemplate(this.dataFiltered);
    }

    formatField(header, linha)
    {
        let html = '';
        header.forEach(a => {
            if(linha.hasOwnProperty(a.field)){
                if(a.function!='')
                {
                    this.func = a.function;
                    let values = a.values.map(e=>linha[e]);
                    html += `<td class="table-cel"><span class="cel_${a.field}" value="${linha[a.field]}">${this.func(...values)}</span></td>`
                }else{
                    html += `<td class="table-cel"><span class="cel_${a.field}" value="${linha[a.field]}">${linha[a.field]}</td>`;
                }
            }
        });   
        return html;
    }

    formatLabel(label){
        return label.split('_').map(e=>
            e.charAt(0).toUpperCase()+e.slice(1)
        ).join(' ');
    }
}