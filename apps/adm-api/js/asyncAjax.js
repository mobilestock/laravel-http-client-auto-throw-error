async function fetchAsync(URL, method, data){

  var myHeaders = new Headers();
  myHeaders.append("Content-Type", "application/json");

    var options = {
        method: method,
        headers: myHeaders,
        body: data,
        redirect: 'follow'
      };

    let response = await fetch(`${URL}`,options);
    let result = await response.json();
    return result;
}