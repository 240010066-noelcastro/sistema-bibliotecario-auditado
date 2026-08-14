import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api',
  withCredentials: true,
  headers: {
    'Accept': 'application/json',
  }
});

api.interceptors.response.use(
  (response) => response,
  (error) => {
    // Si Laravel responde 401 (Sesión no válida o expirada)
    if (error.response && error.response.status === 401) {
      sessionStorage.clear();
      localStorage.clear();
      if (window.location.pathname !== '/') {
        window.location.href = '/';
      }
    }
    return Promise.reject(error);
  }
);

export default api;