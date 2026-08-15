import React, { useState, useEffect } from 'react';
import { IonContent, IonPage, IonIcon } from '@ionic/react';
import { GoogleOAuthProvider, GoogleLogin } from '@react-oauth/google'; 
// @ts-ignore
import api from '../../services/api';
import './Login.css';

const Login: React.FC = () => {
  const [loading, setLoading] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');

  // Redirección si ya hay sesión activa
  useEffect(() => {
    const usuarioStr = sessionStorage.getItem('usuario');
    if (usuarioStr) {
      const usuario = JSON.parse(usuarioStr);
      if (usuario.Rol_ID === 1) window.location.href = '/dashboard';
      else window.location.href = '/portal';
    }
  }, []);

  // 2. LOGIN CON GOOGLE
  const handleGoogleSuccess = async (credentialResponse: any) => {
    setErrorMsg('');
    setLoading(true);

    try {
      const response = await api.post('/login-google', {
        credential: credentialResponse.credential,
      });

      if (response.data.success) {
        if (response.data.es_nuevo) {
          sessionStorage.setItem('registro_token', response.data.registro_token);
          window.location.href = '/completar-registro'; 
        } else {
          const usuario = response.data.usuario;
          const esAdmin = usuario.Rol_ID === 1;

          // Guardamos datos de perfil en memoria/sesión, pero el token queda en la cookie HttpOnly
          sessionStorage.setItem('usuario', JSON.stringify(usuario));
          sessionStorage.setItem('rol', esAdmin ? 'admin' : 'usuario');
          
          if (esAdmin) {
            window.location.href = '/dashboard';
          } else {
            window.location.href = '/portal';
          }
        }
      }
    } catch (error: any) {
      setErrorMsg(error.response?.data?.message || 'Error al autenticar con Google Workspace.');
      setLoading(false);
    }
  };

  return (
    <GoogleOAuthProvider clientId="996518638404-ko9ds937m5lnt72eubph72ri1kc1rq7a.apps.googleusercontent.com">
      <IonPage>
        <IonContent className="unified-login-bg">
          <div className="unified-container">
            <div className="logo-container">
              <img src="/assets/UPVE_Logo.png" alt="Logo UPVE" className="logo" />
            </div>

            <div className="unified-grid">
              <div className="unified-left">
                <h1 className="main-title">Biblioteca Universitaria</h1>
                <p className="main-description">
                  Bienvenido al Sistema de Gestión Bibliotecaria. Un espacio digital diseñado para facilitar el acceso a la información y apoyar el desarrollo académico de nuestra comunidad.
                </p>
              </div>

              <div className="unified-right">
                <div className="form-wrapper">
                  <h2 className="form-title">Iniciar Sesión</h2>
                  <p className="form-subtitle">Ingresa con tus credenciales o cuenta institucional.</p>

                  {errorMsg && <div className="error-alert">{errorMsg}</div>}

                  <div className="google-btn-box" style={{ marginTop: '30px' }}>
                    <GoogleLogin
                      onSuccess={handleGoogleSuccess}
                      onError={() => setErrorMsg('Error de conexión con Google.')}
                      theme="outline"
                      size="large"
                      text="signin_with"
                      width="360"
                    />
                  </div>

                </div>
              </div>
            </div>
          </div>
        </IonContent>
      </IonPage>
    </GoogleOAuthProvider>
  );
};

export default Login;