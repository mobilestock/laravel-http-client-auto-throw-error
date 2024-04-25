

let json = {
    "id": "<string>",
    "status": "<string>",
    "resource": "<string>",
    "type": "<string>",
    "account_balance": "<number>",
    "current_balance": "<number>",
    "fiscal_responsibility": "<string>",

        "first_name": "<string>",
        "last_name": "<string>",
        "email": "<string>",
        "phone_number": "<string>",
        "taxpayer_id": "<string>",
        "birthdate": "<string>",

    "description": "<string>",
    "business_name": "<string>",
    "business_phone": "<string>",
    "business_email": "<string>",
    "business_website": "<string>",
    "business_description": "<string>",
    "business_facebook": "<string>",
    "business_twitter": "<string>",
    "ein": "<string>",

        "b_line1": "<string>",
        "b_line2": "<string>",
        "b_line3": "<string>",
        "b_neighborhood": "<string>",
        "b_city": "<string>",
        "b_state": "<string>",
        "b_postal_code": "<string>",
        "b_country_code": "<string>",

    "business_opening_date": "<date>",

        "line1": "<string>",
        "line2": "<string>",
        "line3": "<string>",
        "neighborhood": "<string>",
        "city": "<string>",
        "state": "<string>",
        "postal_code": "<string>",
        "country_code": "<string>",
    
    "delinquent": "<boolean>",
    "default_debit": "<string>",
    "default_credit": "<string>",
    "mcc": "<string>",
    "metadata": "<object>",
    "created_at": "<dateTime>",
    "updated_at": "<dateTime>"
};

let lista = document.querySelector("#lista");
let html = '';
for(j in json){
    html += `<div class="col-md-4 mb-3">
    <label for="${j}">${j}</label>
    <input type="text" name="${j}" class="form-control input-novo" value="<?= $fornecedor['${j}']; ?>" id="${j}">
</div>`;
}

lista.innerHTML = html;