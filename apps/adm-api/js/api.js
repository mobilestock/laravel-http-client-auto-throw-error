const api = axios.create();

api.interceptors.response.use(
  (response) => {
    return response;
  },
  (error) => {
    if (error.response.status === 401 && !!error?.config?.headers?.token) {
      window.location.href = "cliente-login.php";
    }
    return Promise.reject(error);
  }
);
window.api = api;
