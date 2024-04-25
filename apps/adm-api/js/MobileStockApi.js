/**
 * @deprecated
 * Fazer requisições pelo axios
 */
function MobileStockApi(a, b = {}) {
  if (!b.headers)
    b.headers = new Headers({
      token: cabecalhoVue.user.token,
    });

  else if (b.headers instanceof Headers) {
    b.headers.append('token', cabecalhoVue.user.token);
  }

  return fetch(a, b).then((r) => {
    if (r.status === 401) window.location.href = "cliente-login.php";

    return r;
  });
}
