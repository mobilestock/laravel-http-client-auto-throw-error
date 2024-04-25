<?php
session_start();
extract($_POST);

$str = filter_var($first_name, FILTER_SANITIZE_STRING);
if (filter_var($first_name, FILTER_SANITIZE_STRING) === false || filter_var($last_name, FILTER_SANITIZE_STRING) === false) {
    redirect('Nome inválido');
}

if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    redirect('Email inválido');
}


if (filter_var($phone_number, FILTER_SANITIZE_STRING) === false) {
    redirect('Número inválido');
}

if (filter_var($taxpayer_id, FILTER_SANITIZE_STRING) === false) {
    redirect('CPF INVÁLIDO');
}

if ($birthdate === '') {
    redirect('Data inválida');
}

if (filter_var($statement_descriptor, FILTER_SANITIZE_STRING) === false) {
    redirect('Nome comercial inválido');
}

if (filter_var($mcc, FILTER_SANITIZE_NUMBER_INT) === false) {
    redirect('Atividade de negócio inválida');
}

$body = [
    'first_name' => $first_name,
    'last_name' => $last_name,
    'email' => $email,
    'website' => $website,
    'phone_number' => $phone_number,
    'taxpayer_id' => $taxpayer_id,
    'birthdate' => $birthdate,
    'statement_descriptor' => $statement_descriptor,
    'description' => $description,
    'address' => [
        'line1' => $line1,
        'line2' => $line2,
        'line3' => $line3,
        'neighborhood' => $neighborhood,
        'city' => $city,
        'state' => $state,
        'postal_code' => $postal_code,
        'country_code' => $country_code,
    ],
    'mcc' => $mcc,
];

function redirect($err = null)
{
    if ($err !== null) {
        $_SESSION['danger'] = $err;
    }

    // echo '<script>
    //         localStorage.setItem("danger", "' . $err . '");
    //         history.back();
    //     </script>';
    exit();
}

?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js" integrity="sha512-bLT0Qm9VnAYZDflyKcBaQ2gg0hSYNQrJ8RilYldYQ1FxQYoCLtUjuuRuZo+fjqhx/qtq/1itJ0C2ejDxltZVFg==" crossorigin="anonymous"></script>
<script>
    const body = JSON.parse('<?= json_encode($body) ?>');


    formData = new FormData();
    i = 0;

    for (index in body) {

        formData.append(index, body[index]);
    }

    $.ajax({
        url: 'localhost:3000/sellersindividual',
        method: 'POST',
        headers: new Headers({
            'Content-Type': 'application/json',
            'Access-Control-Allow-Origin': '*',
            'Access-Control-Allow-Methods': 'GET,POST,PUT,HEAD,DELETE,OPTIONS',
            'Access-Control-Allow-Headers': 'content-Type,x-requested-with'
        })
    }).done(r => {
        console.log(r);
    })
</script>